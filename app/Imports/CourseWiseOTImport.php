<?php

namespace App\Imports;

use App\Models\CourseWiseOTList;
use App\Models\StudentMaster;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CourseWiseOTImport implements ToCollection, WithHeadingRow
{
    private $importedCount = 0;
    private $errors = [];
    private $updatedCount = 0;
    private $skippedCount = 0;
    private $studentUpdatedCount = 0; // Track student updates
    private $batchSize = 1000;
    private $columnMap = [];
    private $existingStudents = [];
    private $existingOTRecords = [];
    private $studentsToUpdate = []; // Store student updates

    public function collection(Collection $rows)
    {
        $startTime = microtime(true);
        
        // Normalize column names
        $this->mapColumns($rows->first());
        
        Log::info("Starting OT Import with " . $rows->count() . " rows");
        
        DB::beginTransaction();
        
        try {
            // Preload all student IDs from the import file
            $this->preloadStudentExistence($rows);
            
            // Preload existing OT records
            $this->preloadExistingOTRecords($rows);
            
            // Process in batches
            $rows->chunk($this->batchSize)->each(function ($chunk) {
                $this->processBatch($chunk);
            });
            
            // Update student_master table with OT codes
            $this->updateStudentMaster();
            
            DB::commit();
            
            $executionTime = microtime(true) - $startTime;
            Log::info("Import completed in {$executionTime} seconds", [
                'imported' => $this->importedCount,
                'updated' => $this->updatedCount,
                'student_updated' => $this->studentUpdatedCount,
                'skipped' => $this->skippedCount,
                'errors' => count($this->errors)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Import failed: " . $e->getMessage();
            Log::error('Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Map and normalize column names
     */
    private function mapColumns($firstRow)
    {
        $rowArray = $firstRow->toArray();
        
        $columnPatterns = [
            'student_master_pk' => ['student_master_pk', 'studentmasterpk', 'student master pk', 'student_pk', 'studentpk', 'student id', 'studentid'],
            'course_master_pk' => ['course_master_pk', 'coursemasterpk', 'course master pk', 'course_pk', 'coursepk', 'course id', 'courseid'],
            'generated_ot_code' => ['ot code', 'otcode', 'ot_code', 'generated_ot_code', 'ot', 'otp code', 'otpcode'],
        ];
        
        foreach ($columnPatterns as $standardName => $possibleNames) {
            foreach ($possibleNames as $possibleName) {
                if (isset($rowArray[$possibleName])) {
                    $this->columnMap[$standardName] = $possibleName;
                    break;
                }
                
                // Case-insensitive matching
                foreach (array_keys($rowArray) as $actualCol) {
                    if (strtolower(str_replace([' ', '_', '-'], '', $actualCol)) === 
                        strtolower(str_replace([' ', '_', '-'], '', $possibleName))) {
                        $this->columnMap[$standardName] = $actualCol;
                        break 2;
                    }
                }
            }
            
            if (!isset($this->columnMap[$standardName])) {
                throw new \Exception("Required column '$standardName' not found. Available columns: " . 
                    implode(', ', array_keys($rowArray)));
            }
        }
    }

    /**
     * Preload student existence
     */
    private function preloadStudentExistence(Collection $rows)
    {
        $studentIds = [];
        
        foreach ($rows as $row) {
            $studentMasterPk = $this->getValue($row, 'student_master_pk');
            if ($studentMasterPk && is_numeric($studentMasterPk)) {
                $studentIds[] = (int) $studentMasterPk;
            }
        }
        
        $studentIds = array_unique($studentIds);
        
        if (empty($studentIds)) {
            throw new \Exception("No valid student IDs found");
        }
        
        $existingStudents = StudentMaster::whereIn('pk', $studentIds)
            ->pluck('pk')
            ->toArray();
        
        $this->existingStudents = array_fill_keys($existingStudents, true);
    }

    /**
     * Preload existing OT records
     */
    private function preloadExistingOTRecords(Collection $rows)
    {
        $studentIds = [];
        
        foreach ($rows as $row) {
            $studentMasterPk = $this->getValue($row, 'student_master_pk');
            if ($studentMasterPk && is_numeric($studentMasterPk)) {
                $studentIds[] = (int) $studentMasterPk;
            }
        }
        
        $studentIds = array_unique($studentIds);
        
        $existingRecords = CourseWiseOTList::whereIn('student_master_pk', $studentIds)
            ->get(['pk', 'student_master_pk', 'course_master_pk', 'generated_ot_code', 'active_inactive'])
            ->groupBy('student_master_pk')
            ->map(function ($records) {
                return $records->keyBy('course_master_pk');
            })
            ->toArray();
        
        $this->existingOTRecords = $existingRecords;
    }

    /**
     * Process batch
     */
    private function processBatch(Collection $batch)
    {
        $now = now();
        $toInsert = [];
        $toUpdate = [];
        $batchStudentUpdates = []; // Track student updates in this batch
        
        foreach ($batch as $index => $row) {
            $rowNumber = $index + 2;
            
            try {
                $studentMasterPk = $this->getValue($row, 'student_master_pk');
                $courseMasterPk = $this->getValue($row, 'course_master_pk');
                $otCode = $this->getValue($row, 'generated_ot_code');
                
                // Validation
                if (empty($studentMasterPk) || !is_numeric($studentMasterPk)) {
                    $this->errors[] = "Row {$rowNumber}: Invalid Student PK";
                    $this->skippedCount++;
                    continue;
                }
                
                if (empty($courseMasterPk) || !is_numeric($courseMasterPk)) {
                    $this->errors[] = "Row {$rowNumber}: Invalid Course PK";
                    $this->skippedCount++;
                    continue;
                }
                
                if (empty($otCode)) {
                    $this->errors[] = "Row {$rowNumber}: OT Code required";
                    $this->skippedCount++;
                    continue;
                }
                
                $studentMasterPk = (int) $studentMasterPk;
                $courseMasterPk = (int) $courseMasterPk;
                
                // Check student exists
                if (!isset($this->existingStudents[$studentMasterPk])) {
                    $this->errors[] = "Row {$rowNumber}: Student PK {$studentMasterPk} not found";
                    $this->skippedCount++;
                    continue;
                }
                
                // Check for duplicate in batch
                $batchKey = "{$studentMasterPk}-{$courseMasterPk}";
                if (isset($batchStudentUpdates[$batchKey])) {
                    $this->errors[] = "Row {$rowNumber}: Duplicate student-course in file";
                    $this->skippedCount++;
                    continue;
                }
                
                // Store student update (we'll update student_master later)
                $batchStudentUpdates[$batchKey] = [
                    'student_id' => $studentMasterPk,
                    'ot_code' => $otCode
                ];
                
                // Check if record exists in database
                if (isset($this->existingOTRecords[$studentMasterPk][$courseMasterPk])) {
                    $existingRecord = $this->existingOTRecords[$studentMasterPk][$courseMasterPk];
                    
                    // Update if OT code changed or status inactive
                    if ($existingRecord['generated_ot_code'] !== $otCode || $existingRecord['active_inactive'] != 1) {
                        $toUpdate[$existingRecord['pk']] = [
                            'generated_ot_code' => $otCode,
                            'active_inactive' => 1,
                            // 'updated_date' => $now
                        ];
                        $this->updatedCount++;
                    } else {
                        $this->skippedCount++; // No change needed
                    }
                } else {
                    // New record
                    $toInsert[] = [
                        'student_master_pk' => $studentMasterPk,
                        'course_master_pk' => $courseMasterPk,
                        'generated_ot_code' => $otCode,
                        'active_inactive' => 1,
                        'created_date' => $now,
                        // 'updated_date' => $now,
                    ];
                    $this->importedCount++;
                }
                
            } catch (\Exception $e) {
                $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
                $this->skippedCount++;
            }
        }
        
        // Bulk operations
        if (!empty($toInsert)) {
            CourseWiseOTList::insert($toInsert);
        }
        
        if (!empty($toUpdate)) {
            foreach ($toUpdate as $id => $data) {
                CourseWiseOTList::where('pk', $id)->update($data);
            }
        }
        
        // Store student updates for later processing
        foreach ($batchStudentUpdates as $update) {
            $this->studentsToUpdate[$update['student_id']] = $update['ot_code'];
        }
    }

    /**
     * Update student_master table with OT codes
     */
    private function updateStudentMaster()
    {
        if (empty($this->studentsToUpdate)) {
            return;
        }
        
        $now = now();
        $updatedCount = 0;
        
        // Update in chunks to avoid query length issues
        foreach (array_chunk($this->studentsToUpdate, 100, true) as $chunk) {
            foreach ($chunk as $studentId => $otCode) {
                try {
                    $updated = StudentMaster::where('pk', $studentId)
                        ->update([
                            'generated_OT_code' => $otCode,
                            // 'updated_date' => $now
                        ]);
                    
                    if ($updated) {
                        $updatedCount++;
                    }
                } catch (\Exception $e) {
                    $this->errors[] = "Failed to update student {$studentId}: " . $e->getMessage();
                }
            }
        }
        
        $this->studentUpdatedCount = $updatedCount;
        Log::info("Updated {$updatedCount} students with OT codes");
    }

    /**
     * Get value from row
     */
    private function getValue($row, $columnName)
    {
        if (!isset($this->columnMap[$columnName])) {
            return null;
        }
        
        $mappedColumn = $this->columnMap[$columnName];
        $value = $row[$mappedColumn] ?? null;
        
        // Trim and clean the value
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }
        
        return $value;
    }

    // Getter methods
    public function getErrors() { return $this->errors; }
    public function getImportedCount() { return $this->importedCount; }
    public function getUpdatedCount() { return $this->updatedCount; }
    public function getSkippedCount() { return $this->skippedCount; }
    public function getStudentUpdatedCount() { return $this->studentUpdatedCount; } // Add this method
    public function getTotalProcessed() { 
        return $this->importedCount + $this->updatedCount + $this->skippedCount; 
    }
}