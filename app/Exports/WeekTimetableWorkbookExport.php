<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WeekTimetableWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  list<list<string>>  $gridRows
     * @param  list<list<string>>  $sessionsRowsWithHeader
     */
    public function __construct(
        protected array $gridRows,
        protected string $gridSheetTitle,
        protected array $sessionsRowsWithHeader
    ) {
    }

    public function sheets(): array
    {
        return [
            new WeekTimetableGridExport($this->gridRows, $this->gridSheetTitle),
            new WeekTimetableSessionsSheetExport($this->sessionsRowsWithHeader, 'Sessions'),
        ];
    }
}
