<?php

namespace App\Exports;

use App\Models\IssueReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IssueReportExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected const COLUMN_LABELS = [
        'sno'             => 'S.No.',
        'date'            => 'Date',
        'dept_name'       => 'Department Name',
        'sub_module_name' => 'Sub-Module',
        'reporter'        => 'Issue Raised By',
        'description'     => 'Issue Description',
        'attachment'      => 'Attachment',
        'status'          => 'Status',
    ];

    protected array $filters;

    protected array $activeKeys;

    protected int $rowIndex = 0;

    public function __construct(array $filters = [], array $activeKeys = [])
    {
        $this->filters    = $filters;
        $this->activeKeys = $activeKeys ?: array_keys(self::COLUMN_LABELS);
    }

    public function query(): Builder
    {
        $query = IssueReport::query()
            ->leftJoin('user_credentials as uc', 'uc.user_id', '=', 'issue_reports.reported_by')
            ->select([
                'issue_reports.*',
                'uc.first_name as reporter_first',
                'uc.last_name  as reporter_last',
                'uc.user_name  as reporter_username',
            ]);

        $statusFilter    = $this->filters['status_filter']    ?? 'all';
        $deptFilter      = $this->filters['dept_filter']      ?? '';
        $submoduleFilter = $this->filters['submodule_filter'] ?? '';
        $dateFrom        = $this->filters['date_from']        ?? '';
        $dateTo          = $this->filters['date_to']          ?? '';

        if ($statusFilter === 'active') {
            $query->whereIn('issue_reports.status', [IssueReport::STATUS_OPEN, IssueReport::STATUS_IN_PROGRESS]);
        } elseif ($statusFilter === 'fixed') {
            $query->whereIn('issue_reports.status', [IssueReport::STATUS_RESOLVED, IssueReport::STATUS_CLOSED]);
        }

        if ($deptFilter      !== '') $query->where('issue_reports.module_name', $deptFilter);
        if ($submoduleFilter !== '') $query->where('issue_reports.sub_module', $submoduleFilter);
        if ($dateFrom        !== '') $query->whereDate('issue_reports.created_at', '>=', $dateFrom);
        if ($dateTo          !== '') $query->whereDate('issue_reports.created_at', '<=', $dateTo);

        return $query->orderBy('issue_reports.id', 'desc');
    }

    public function headings(): array
    {
        return array_map(fn ($key) => self::COLUMN_LABELS[$key], $this->activeKeys);
    }

    public function map($report): array
    {
        $this->rowIndex++;

        $name = trim(($report->reporter_first ?? '') . ' ' . ($report->reporter_last ?? ''));
        if ($name === '') {
            $name = $report->reporter_username ?? ('User #' . $report->reported_by);
        }
        $statusLabel = in_array((int) $report->status, [IssueReport::STATUS_OPEN, IssueReport::STATUS_IN_PROGRESS])
            ? 'Active' : 'Fixed Issue';

        $row = [
            'sno'             => $this->rowIndex,
            'date'            => $report->created_at ? Carbon::parse($report->created_at)->format('d-m-Y') : '',
            'dept_name'       => $report->module_name ?? '',
            'sub_module_name' => $report->sub_module  ?? '',
            'reporter'        => $name,
            'description'     => $report->description ?? '',
            'attachment'      => $report->attachment ? url('storage/' . $report->attachment) : '',
            'status'          => $statusLabel,
        ];

        return array_map(fn ($key) => $row[$key], $this->activeKeys);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();

        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D6E4F0'],
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
