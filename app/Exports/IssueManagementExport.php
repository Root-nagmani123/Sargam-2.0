<?php

namespace App\Exports;

use App\Models\IssueLogManagement;
use App\Models\EmployeeMaster;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class IssueManagementExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithCustomChunkSize, ShouldAutoSize
{
    protected array $filters;

    protected int $rowIndex = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Build and return the filtered query (chunked for Excel, used by PDF too).
     */
    public function query(): Builder
    {
        $query = IssueLogManagement::with([
            'category',
            'priority',
            'creator.designation',
            'buildingMapping.building',
            'hostelMapping.hostelBuilding',
            'statusHistory',
        ]);

        if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
            $ids = getEmployeeIdsForUser(Auth::user()->user_id);
            if (empty($ids)) {
                $ids = [Auth::user()->user_id];
            }
            $query->where(function ($q) use ($ids) {
                $q->whereIn('employee_master_pk', $ids)
                    ->orWhereIn('issue_logger', $ids)
                    ->orWhereIn('assigned_to', $ids)
                    ->orWhereIn('created_by', $ids);
            });
        }

        if (!empty($this->filters['raised_by']) && $this->filters['raised_by'] === 'self') {
            $ids = getEmployeeIdsForUser(Auth::user()->user_id);
            $query->whereIn('created_by', empty($ids) ? [Auth::user()->user_id] : $ids);
        }

        if (!empty($this->filters['search'])) {
            $term = trim($this->filters['search']);
            $query->where(function ($q) use ($term) {
                if (is_numeric($term)) {
                    $q->orWhere('pk', $term);
                }
                $q->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('category', fn ($cq) => $cq->where('issue_category', 'like', "%{$term}%"))
                    ->orWhereHas('subCategoryMappings.subCategory', fn ($sq) => $sq->where('issue_sub_category', 'like', "%{$term}%"));
            });
        }

        if (!empty($this->filters['category'])) {
            $query->where('issue_category_master_pk', $this->filters['category']);
        }

        if (!empty($this->filters['priority'])) {
            $query->where('issue_priority_master_pk', $this->filters['priority']);
        }

        if (!empty($this->filters['date_from'])) {
            $from = Carbon::parse($this->filters['date_from'])->startOfDay()->toDateTimeString();
            $query->where('created_date', '>=', $from);
        }
        if (!empty($this->filters['date_to'])) {
            $to = Carbon::parse($this->filters['date_to'])->endOfDay()->toDateTimeString();
            $query->where('created_date', '<=', $to);
        }

        if (isset($this->filters['status']) && $this->filters['status'] !== '') {
            $query->where('issue_status', (int) $this->filters['status']);
        }

        return $query->orderBy('created_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'S.No.',
            'Section',
            'Call ID',
            'Name',
            'Description',
            'Attended By',
            'Call Date',
            'Call Time',
            'Cleared Date',
            'Cleared Time',
            'Time Taken In Hours',
            'location',
            'Status',
            'Remarks',
        ];
    }

    public function map($issue): array
    {
        return $this->issueToRow($issue);
    }

    /**
     * Convert a single issue to export row (used by Excel map and PDF).
     */
    public function issueToRow(IssueLogManagement $issue): array
    {
        $this->rowIndex++;

        $callDate = $issue->created_date ? $issue->created_date->format('d-m-Y') : '';
        $callTime = $issue->created_date ? $issue->created_date->format('H:i:s') : '';
        $clearedAt = $this->effectiveClearedAt($issue);
        $clearedDate = $clearedAt ? $clearedAt->format('d-m-Y') : '';
        $clearedTime = $clearedAt ? $clearedAt->format('H:i:s') : '';

        return [
            $this->rowIndex,
            $issue->category->issue_category ?? 'N/A',
            $issue->pk,
            $this->nameWithDesignation($issue),
            $issue->description ?? '',
            $this->assignedToName($issue),
            $callDate,
            $callTime,
            $clearedDate,
            $clearedTime,
            $this->timeTaken($issue),
            $this->locationLabel($issue),
            $issue->issue_status == 2 ? 'Closed' : $issue->status_label,
            $issue->remark ?? '',
        ];
    }

    /**
     * Get rows for PDF export (limited to avoid memory exhaustion).
     * Returns ['rows' => [...], 'total_count' => int, 'truncated' => bool].
     */
    public function getRowsForPdf(int $limit = 5000): array
    {
        $query = $this->query();
        $totalCount = $query->count();
        $issues = (clone $query)->limit($limit)->get();
        $this->rowIndex = 0;
        $rows = [];
        foreach ($issues as $issue) {
            $rows[] = $this->issueToRow($issue);
        }
        return [
            'rows' => $rows,
            'total_count' => $totalCount,
            'truncated' => $totalCount > $limit,
            'limit' => $limit,
        ];
    }

    public function chunkSize(): int
    {
        return 500; // Smaller chunks for large datasets (65K+ rows) to avoid memory exhaustion
    }

    protected function locationLabel(IssueLogManagement $issue): string
    {
        if ($issue->buildingMapping && $issue->buildingMapping->building) {
            return trim($issue->buildingMapping->building->building_name ?? '') ?: '';
        }
        if ($issue->hostelMapping && $issue->hostelMapping->hostelBuilding) {
            $h = $issue->hostelMapping->hostelBuilding;
            return trim($h->hostel_name ?? $h->building_name ?? '') ?: '';
        }
        return '';
    }

    protected function nameWithDesignation(IssueLogManagement $issue): string
    {
        $creator = $issue->creator ?: EmployeeMaster::findByIdOrPkOld($issue->created_by);
        if (!$creator) {
            return 'CENTCOM LBSNAA - USER';
        }
        $name = trim(($creator->first_name ?? '') . ' ' . ($creator->middle_name ?? '') . ' ' . ($creator->last_name ?? ''));
        $designation = $creator->designation->designation_name ?? '';
        return $designation ? $name . ' - ' . $designation : $name;
    }

    protected function assignedToName(IssueLogManagement $issue): string
    {
        if (empty($issue->assigned_to) || trim((string) $issue->assigned_to) === '') {
            return 'Not assigned';
        }
        if (is_numeric($issue->assigned_to)) {
            $emp = EmployeeMaster::findByIdOrPkOld($issue->assigned_to);
            return $emp ? trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) : (string) $issue->assigned_to;
        }
        return $issue->assigned_to;
    }

    protected function effectiveClearedAt(IssueLogManagement $issue): ?Carbon
    {
        if (!empty($issue->clear_date)) {
            return Carbon::parse($issue->clear_date);
        }
        if ((int) $issue->issue_status === 2 && $issue->relationLoaded('statusHistory')) {
            $completed = $issue->statusHistory->where('issue_status', 2)->sortByDesc('issue_date')->first();
            return $completed && $completed->issue_date ? Carbon::parse($completed->issue_date) : null;
        }
        return null;
    }

    protected function timeTaken(IssueLogManagement $issue): string
    {
        $created = $issue->created_date ? Carbon::parse($issue->created_date) : null;
        $cleared = $this->effectiveClearedAt($issue);
        if (!$created || !$cleared) {
            return '';
        }
        $diff = $created->diff($cleared);
        return sprintf('%02d:%02d:%02d', $diff->h + $diff->days * 24, $diff->i, $diff->s);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'N';

        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($lastRow >= 1) {
            $sheet->getStyle('A1:' . $lastCol . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        return [];
    }
}
