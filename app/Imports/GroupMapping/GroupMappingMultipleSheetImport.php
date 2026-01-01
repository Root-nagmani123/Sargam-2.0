<?php
namespace App\Imports\GroupMapping;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GroupMappingMultipleSheetImport implements WithMultipleSheets
{
    public $sheet1Import;
    public $courseType;

    public function __construct($courseType)
    {
        $this->courseType = $courseType;
        $this->sheet1Import = new GroupMappingImport($this->courseType);
    }

    public function sheets(): array
    {
        return [
            'Sheet1' => $this->sheet1Import, // Pass the tracked instance
        ];
    }
}
