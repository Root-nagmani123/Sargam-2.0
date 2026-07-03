<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class FcRosterListExport implements FromView, WithTitle
{
    /**
     * @param  Collection<int, array<string, mixed>>  $tableRows
     * @param  array<int, array{key: string, label: string}>  $columns
     */
    public function __construct(
        protected Collection $tableRows,
        protected array $columns,
        protected string $filterDescription,
        protected string $sheetTitle,
        protected string $reportHeading,
        protected bool $truncated = false,
        protected int $totalMatching = 0,
    ) {}

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function view(): View
    {
        return view('admin.registration.export-excel-table', [
            'colCount' => count($this->columns),
            'columns' => $this->columns,
            'tableRows' => $this->tableRows,
            'filterDescription' => $this->filterDescription,
            'reportHeading' => $this->reportHeading,
            'generatedAt' => now()->format('d-m-Y H:i'),
            'truncated' => $this->truncated,
            'totalMatching' => $this->totalMatching,
        ]);
    }
}
