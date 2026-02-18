<?php

namespace App\Exports;

use App\Models\IssueLogManagement;
use App\Models\EmployeeMaster;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class IssueManagementExport implements FromCollection, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = IssueLogManagement::with([
            'category',
            'priority',
            'creator.designation',
            'buildingMapping.building',
            'hostelMapping.hostelBuilding',
            'statusHistory',
        ]);

        // User scope (non-admin sees only own issues)
        if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
            $query->where(function ($q) {
                $q->where('employee_master_pk', Auth::user()->user_id)
                    ->orWhere('issue_logger', Auth::user()->user_id)
                    ->orWhere('assigned_to', Auth::user()->user_id)
                    ->orWhere('created_by', Auth::user()->user_id);
            });
        }

        if (!empty($this->filters['raised_by']) && $this->filters['raised_by'] === 'self') {
            $query->where('created_by', Auth::user()->user_id);
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

        $issues = $query->orderBy('created_date', 'desc')->get();

        $locationLabel = function ($issue) {
            if ($issue->buildingMapping && $issue->buildingMapping->building) {
                return trim($issue->buildingMapping->building->building_name ?? '') ?: '';
            }
            if ($issue->hostelMapping && $issue->hostelMapping->hostelBuilding) {
                $h = $issue->hostelMapping->hostelBuilding;
                return trim($h->hostel_name ?? $h->building_name ?? '') ?: '';
            }
            return '';
        };

        $nameWithDesignation = function ($issue) {
            $creator = $issue->creator;
            if (!$creator) {
                return 'CENTCOM LBSNAA - USER';
            }
            $name = trim(($creator->first_name ?? '') . ' ' . ($creator->middle_name ?? '') . ' ' . ($creator->last_name ?? ''));
            $designation = $creator->designation->designation_name ?? '';
            return $designation ? $name . ' - ' . $designation : $name;
        };

        $assignedToName = function ($issue) {
            if (empty($issue->assigned_to) || trim((string) $issue->assigned_to) === '') {
                return 'Not assigned';
            }
            if (is_numeric($issue->assigned_to)) {
                $emp = EmployeeMaster::find($issue->assigned_to);
                return $emp ? trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) : (string) $issue->assigned_to;
            }
            return $issue->assigned_to;
        };

        // Effective cleared datetime: clear_date if set, else from status history when status is Completed (2)
        $effectiveClearedAt = function ($issue) {
            if ($issue->clear_date) {
                return Carbon::parse($issue->clear_date);
            }
            if ((int) $issue->issue_status === 2 && $issue->relationLoaded('statusHistory')) {
                $completed = $issue->statusHistory->where('issue_status', 2)->sortByDesc('issue_date')->first();
                return $completed && $completed->issue_date ? Carbon::parse($completed->issue_date) : null;
            }
            return null;
        };

        $timeTaken = function ($issue) use ($effectiveClearedAt) {
            $created = $issue->created_date ? Carbon::parse($issue->created_date) : null;
            $cleared = $effectiveClearedAt($issue);
            if (!$created || !$cleared) {
                return '';
            }
            $diff = $created->diff($cleared);
            return sprintf('%02d:%02d:%02d', $diff->h + $diff->days * 24, $diff->i, $diff->s);
        };

        $rows = collect();

        // Table header - yellow in styles
        $rows->push([
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
        ]);

        // Data rows
        $sno = 1;
        foreach ($issues as $issue) {
            $callDate = $issue->created_date ? $issue->created_date->format('d-m-Y') : '';
            $callTime = $issue->created_date ? $issue->created_date->format('H:i:s') : '';
            $clearedAt = $effectiveClearedAt($issue);
            $clearedDate = $clearedAt ? $clearedAt->format('d-m-Y') : '';
            $clearedTime = $clearedAt ? $clearedAt->format('H:i:s') : '';

            $rows->push([
                $sno++,
                $issue->category->issue_category ?? 'N/A',
                $issue->pk,
                $nameWithDesignation($issue),
                $issue->description ?? '',
                $assignedToName($issue),
                $callDate,
                $callTime,
                $clearedDate,
                $clearedTime,
                $timeTaken($issue),
                $locationLabel($issue),
                $issue->issue_status == 2 ? 'Closed' : $issue->status_label,
                $issue->remark ?? '',
            ]);
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'N';

        // Table header row (row 1) - yellow background, bold
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

        // Data area border and alignment (from row 1 to end)
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
