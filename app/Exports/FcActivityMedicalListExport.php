<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class FcActivityMedicalListExport implements FromView, WithTitle
{
    public const COL_COUNT = 7;

    public function __construct(
        protected Collection $tableRows,
        protected string $filterDescription
    ) {}

    public function title(): string
    {
        return 'Medical trainees';
    }

    public function view(): View
    {
        return view('admin.fc-activities.medical.excel.export-table', [
            'colCount' => self::COL_COUNT,
            'tableRows' => $this->tableRows,
            'filterDescription' => $this->filterDescription,
            'generatedAt' => now()->format('d-m-Y H:i'),
        ]);
    }
}
