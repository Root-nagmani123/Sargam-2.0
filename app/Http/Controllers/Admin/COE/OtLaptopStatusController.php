<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * COE → Laptop Management → OT Laptop Status.
 *
 * Records, per Officer Trainee, whether they need a laptop for the exam and —
 * for an OT with a disability — whether they need a scriber.
 *
 * Design-first screen, like the question-paper screens: the rows and the OT
 * picker are SAMPLE data and no table backs this page yet. Replace
 * sampleRows() / sampleOts() with real queries when the module lands; the view
 * reads only the keys listed on them.
 *
 * Two scopes, each its own URL (docs/new-design-index-page.md §1 "Scope tabs"):
 *   ?scope=active    running courses (default)
 *   ?scope=archived  finished courses
 */
class OtLaptopStatusController extends Controller
{
    public const SCOPES = ['active', 'archived'];

    /** Bulk import limits — the dropzone and the template link read these. */
    public const IMPORT_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public const IMPORT_MAX_MB = 5;

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true) ? $request->query('scope') : 'active';

        $rows = $this->sampleRows()->where('archived', $scope === 'archived')->values();
        $ots = $this->sampleOts();

        return view('admin.coe.ot_laptop_status.index', [
            'scope' => $scope,
            'rows' => $rows,
            'courses' => $rows->pluck('course')->merge($ots->pluck('course'))->unique()->sort()->values(),
            'services' => $rows->pluck('service')->unique()->sort()->values(),
            'batches' => $rows->pluck('batch')->merge($ots->pluck('batch'))->unique()->sort()->values(),
            'ots' => $ots,
            'importExtensions' => self::IMPORT_EXTENSIONS,
            'importMaxMb' => self::IMPORT_MAX_MB,
        ]);
    }

    /**
     * @return Collection<int, array{id:int, roll:string, name:string, code:string, course:string, service:string,
     *     batch:string, laptop_required:bool, reason:?string, disability:bool, scriber:bool,
     *     scriber_remarks:?string, updated_by:string, archived:bool}>
     */
    private function sampleRows(): Collection
    {
        $rows = [
            // roll, name, code, course, service, batch, laptop, reason, disability, scriber, remarks, by, archived
            ['FC101-001', 'Amit Sharma', 'A01', 'FC-101', 'IAS', '2026', false, null, false, false, null, 'OT', false],
            ['FC101-002', 'Sumit Verma', 'A02', 'FC-101', 'IPS', '2026', true, 'Does not own a laptop', false, false, null, 'Admin', false],
            ['FC101-003', 'Rakhi Singh', 'A03', 'FC-101', 'IFS', '2026', false, null, true, true, 'Need fast writer', 'OT', false],
            ['FC101-004', 'Neha Gupta', 'A04', 'FC-101', 'IAS', '2026', false, null, false, false, null, 'Admin', false],
            ['FC101-005', 'Rohit Jain', 'A05', 'FC-101', 'IRS', '2026', true, 'Using office computer', false, false, null, 'OT', false],
            ['FC101-006', 'Sneha Patel', 'A06', 'FC-101', 'IPS', '2026', false, null, false, false, null, 'Admin', false],
            ['FC101-007', 'Karan Mehta', 'A07', 'FC-101', 'IAS', '2026', false, null, false, false, null, 'Admin', false],
            ['FC101-008', 'Isha Khan', 'A08', 'FC-101', 'IFS', '2026', true, 'Personal laptop not working since last week', false, false, null, 'Admin', false],
            ['FC102-001', 'Vikram Rao', 'B01', 'FC-102', 'IRS', '2026', false, null, true, false, null, 'OT', false],
            ['FC102-002', 'Pooja Nair', 'B02', 'FC-102', 'IAS', '2026', true, 'Laptop under repair', false, false, null, 'Admin', false],
            ['FC099-011', 'Arjun Das', 'Z11', 'FC-099', 'IPS', '2025', true, 'Does not own a laptop', false, false, null, 'Admin', true],
            ['FC099-012', 'Meera Iyer', 'Z12', 'FC-099', 'IAS', '2025', false, null, true, true, 'Low-vision support', 'OT', true],
        ];

        return collect($rows)->values()->map(fn (array $r, int $i) => [
            'id' => $i + 1,
            'roll' => $r[0], 'name' => $r[1], 'code' => $r[2], 'course' => $r[3], 'service' => $r[4],
            'batch' => $r[5], 'laptop_required' => $r[6], 'reason' => $r[7], 'disability' => $r[8],
            'scriber' => $r[9], 'scriber_remarks' => $r[10], 'updated_by' => $r[11], 'archived' => $r[12],
        ]);
    }

    /**
     * OTs the "Add Single Record" picker offers, narrowed by Course + Batch in the browser.
     *
     * @return Collection<int, array{id:int, name:string, code:string, course:string, service:string, batch:string, roll:string}>
     */
    private function sampleOts(): Collection
    {
        $names = ['Aimee Snyder', 'Kayla Fernandez', 'Deanna Roberts', 'Brandy Campbell', 'Shawna Carr', 'Josefina Barber'];

        return collect($names)->values()->map(fn (string $n, int $i) => [
            'id' => 100 + $i,
            'name' => $n,
            'code' => 'A'.(31 + $i),
            'course' => $i < 4 ? 'FC-101' : 'FC-102',
            'service' => ['IAS', 'IPS', 'IFS', 'IRS'][$i % 4],
            'batch' => '2026',
            'roll' => ($i < 4 ? 'FC101-' : 'FC102-').str_pad((string) (31 + $i), 3, '0', STR_PAD_LEFT),
        ]);
    }
}
