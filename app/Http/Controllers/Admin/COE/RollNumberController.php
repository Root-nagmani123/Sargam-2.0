<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * COE → Examination Management → Roll Numbers.
 *
 * Design-first screen. All data is sample only.
 */
class RollNumberController extends Controller
{
    public const SCOPES = ['all', 'generated', 'frozen'];

    public const COURSES = ['FC101', 'FC102', 'Phase IV 2025', 'Phase III 2025'];

    public const SERVICES = ['IAS', 'IPS', 'IFS', 'IRS'];

    public const EXAMINATIONS = ['Written Test', 'Practical', 'Viva'];

    public const CADRES = ['MP', 'Rajasthan', 'UP', 'Bihar', 'Maharastra', 'UK'];

    public const BATCHES = ['Batch A', 'Batch B', 'Batch C'];

    public const STRATEGIES = [
        'Allocation Rules',
        'Sequential by Service',
        'Sequential by Name',
        'Sequential by OT Code',
    ];

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'all';

        $rows = $this->sampleRows()->when($scope !== 'all', function (Collection $c) use ($scope) {
            return $c->filter(fn ($r) => strtolower($r['status']) === $scope);
        })->values();

        return view('admin.coe.roll_number.index', [
            'scope'       => $scope,
            'rows'        => $rows,
            'courses'     => self::COURSES,
            'services'    => self::SERVICES,
            'examinations'=> self::EXAMINATIONS,
            'cadres'      => self::CADRES,
            'batches'     => self::BATCHES,
            'strategies'  => self::STRATEGIES,
        ]);
    }

    /** @return Collection<int, array> */
    private function sampleRows(): Collection
    {
        $data = [
            // name, service, cadre, gender, otCode, rollNumber, status
            ['Amit Sharma',  'IAS', 'MP',          'Male',   'A72', 'FC101-001', 'Frozen'],
            ['Sumit Verma',  'IPS', 'Rajasthan',   'Male',   'A73', 'FC101-002', 'Frozen'],
            ['Rakhi Singh',  'IFS', 'UP',           'Female', 'A74', 'FC101-003', 'Frozen'],
            ['Rohit Mehta',  'IAS', 'MP',           'Male',   'A75', 'FC101-004', 'Frozen'],
            ['Kuldip Rao',   'IRS', 'Bihar',        'Male',   'A76', 'FC101-005', 'Frozen'],
            ['Priya Nair',   'IAS', 'Maharastra',   'Female', 'A77', 'FC101-006', 'Pending'],
            ['Anjali Gupta', 'IPS', 'UP',           'Female', 'A78', 'FC101-007', 'Frozen'],
            ['Vikram Patel', 'IFS', 'UK',           'Male',   'A79', 'FC101-008', 'Pending'],
            ['Meera Joshi',  'IAS', 'MP',           'Female', 'A80', 'FC102-001', 'Pending'],
            ['Arjun Khanna', 'IRS', 'Rajasthan',   'Male',   'A81', 'FC102-002', 'Pending'],
        ];

        return collect($data)->map(fn (array $r, int $i) => [
            'id'          => $i + 1,
            'name'        => $r[0],
            'service'     => $r[1],
            'cadre'       => $r[2],
            'gender'      => $r[3],
            'ot_code'     => $r[4],
            'roll_number' => $r[5],
            'status'      => $r[6],
            'course'      => str_starts_with($r[5], 'FC101') ? 'FC101' : 'FC102',
        ]);
    }
}
