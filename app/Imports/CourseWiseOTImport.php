<?php

namespace App\Imports;

use App\Models\CourseWiseOTList;
use App\Models\StudentMaster;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;



class CourseWiseOTImport implements ToCollection, WithHeadingRow, WithValidation
{
    private $importedCount = 0;
    private $errors = [];
    private $updatedCount = 0;
    private $skippedCount = 0;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        
        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because of header row and 0-index
                
                // Extract values directly from row array
                $rowArray = $row->toArray();
                
                // Debug: Log the actual column names
                if ($index === 0) {
                    \Log::info("Excel columns found:", array_keys($rowArray));
                }
                
                // Find student_master_pk - try different possible column names
                $studentMasterPk = $this->findValue($rowArray, [
                    'student_master_pk',
                    'studentmasterpk',
                    'student master pk',
                    'student_pk',
                    'studentpk'
                ]);
                
                // Find course_master_pk
                $courseMasterPk = $this->findValue($rowArray, [
                    'course_master_pk',
                    'coursemasterpk',
                    'course master pk',
                    'course_pk',
                    'coursepk'
                ]);
                
                // Find OT Code - Excel shows "OT Code"
                $otCode = $this->findValue($rowArray, [
                    'OT Code',
                    'OTCode',
                    'otcode',
                    'ot_code',
                    'generated_ot_code',
                    'ot'
                ]);
                
                \Log::info("Row {$rowNumber} values:", [
                    'student_master_pk' => $studentMasterPk,
                    'course_master_pk' => $courseMasterPk,
                    'ot_code' => $otCode
                ]);
                
                // Validate required fields
                if (empty($studentMasterPk)) {
                    $this->errors[] = "Row {$rowNumber}: Student Master PK not found. Available columns: " . implode(', ', array_keys($rowArray));
                    $this->skippedCount++;
                    continue;
                }
                
                if (empty($courseMasterPk)) {
                    $this->errors[] = "Row {$rowNumber}: Course Master PK not found";
                    $this->skippedCount++;
                    continue;
                }
                
                if (empty($otCode)) {
                    $this->errors[] = "Row {$rowNumber}: OT Code not found";
                    $this->skippedCount++;
                    continue;
                }
                
                // Convert to integers
                $studentMasterPk = (int) $studentMasterPk;
                $courseMasterPk = (int) $courseMasterPk;
                
                // Validate that student exists
                $studentExists = StudentMaster::where('pk', $studentMasterPk)->exists();
                if (!$studentExists) {
                    $this->errors[] = "Row {$rowNumber}: Student with PK {$studentMasterPk} does not exist in student_master table";
                    $this->skippedCount++;
                    continue;
                }
                
                // Process the import
                $result = $this->processImport($studentMasterPk, $courseMasterPk, $otCode);
                
                if ($result === 'created') {
                    $this->importedCount++;
                } elseif ($result === 'updated') {
                    $this->updatedCount++;
                } elseif ($result === 'duplicate') {
                    $this->skippedCount++;
                } else {
                    $this->errors[] = "Row {$rowNumber}: Failed to process";
                    $this->skippedCount++;
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Import failed: " . $e->getMessage();
            \Log::error('Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Process import for a single row
     */
    private function processImport($studentMasterPk, $courseMasterPk, $otCode)
    {
        // Check if exact same record already exists
        $existingRecord = CourseWiseOTList::where([
            'student_master_pk' => $studentMasterPk,
            'course_master_pk' => $courseMasterPk,
            'generated_ot_code' => $otCode
        ])->first();

        if ($existingRecord) {
            // Record already exists with same OT code
            $existingRecord->active_inactive = 1; // Ensure active
            $existingRecord->updated_date = now();
            $existingRecord->save();
            return 'duplicate';
        }

        // Check if record exists with different OT code
        $existingDifferentOT = CourseWiseOTList::where([
            'student_master_pk' => $studentMasterPk,
            'course_master_pk' => $courseMasterPk
        ])->first();

        if ($existingDifferentOT) {
            // Update existing record with new OT code
            $existingDifferentOT->generated_ot_code = $otCode;
            $existingDifferentOT->active_inactive = 1;
            $existingDifferentOT->updated_date = now();
            $existingDifferentOT->save();
            return 'updated';
        }

        // Create new record
        CourseWiseOTList::create([
            'student_master_pk' => $studentMasterPk,
            'course_master_pk' => $courseMasterPk,
            'generated_ot_code' => $otCode,
            'active_inactive' => 1,
            'created_date' => now(),
            'updated_date' => now(),
        ]);

        return 'created';
    }

    /**
     * Helper to find value by trying multiple column names
     */
    private function findValue($rowArray, $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            // Try exact match
            if (isset($rowArray[$key])) {
                return $rowArray[$key];
            }
            
            // Try case-insensitive match
            foreach ($rowArray as $actualKey => $value) {
                $normalizedActual = Str::lower(str_replace([' ', '_', '-', '.'], '', $actualKey));
                $normalizedKey = Str::lower(str_replace([' ', '_', '-', '.'], '', $key));
                
                if ($normalizedActual === $normalizedKey) {
                    return $value;
                }
            }
        }
        
        return null;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'student_master_pk' => 'required|integer',
            'course_master_pk' => 'required|integer',
            'ot_code' => 'required|string|max:20',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'student_master_pk.required' => 'Student Master PK is required',
            'course_master_pk.required' => 'Course Master PK is required',
            'ot_code.required' => 'OT Code is required',
            'ot_code.max' => 'OT Code must not exceed 20 characters',
        ];
    }

    /**
     * Get import results
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getUpdatedCount()
    {
        return $this->updatedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getTotalProcessed()
    {
        return $this->importedCount + $this->updatedCount + $this->skippedCount;
    }
}