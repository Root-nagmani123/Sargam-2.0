<?php
namespace App\Imports\GroupMapping;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GroupMappingMultipleSheetImport implements WithMultipleSheets
{
    public $sheet1Import;

    public $courseMasterPk;

    public function __construct($courseMasterPk)
    {
        $this->courseMasterPk = $courseMasterPk;
        $this->sheet1Import = new GroupMappingImport($courseMasterPk);
    }

    public function sheets(): array
    {
        return [
            'Sheet1' => $this->sheet1Import, // Pass the tracked instance
        ];
    }
}
