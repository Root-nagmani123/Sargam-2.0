<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\SampleVenueData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * COE → Examination Management → Venue Allocation.
 *
 * Design-first. Two screens:
 *   index           — allocation list (Active / Archived tabs)
 *   edit/{id}       — interactive seating plan editor
 */
class VenueAllocationController extends Controller
{
    use SampleVenueData;

    public const SCOPES      = ['active', 'archived'];
    public const COURSES     = ['FC101', 'FC102', 'Phase IV 2025'];
    public const CATEGORIES  = ['General Category', 'PH Category', 'SC/ST Category'];
    public const SUBJECTS    = ['Law', 'Ethics & Integrity', 'Leadership', 'Discipline'];
    public const COMPONENTS  = ['Ethics & Integrity', 'Discipline', 'Case Study'];
    public const STATUSES    = ['Pending', 'Frozen'];

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        $rows = $this->sampleAllocations()->where('archived', $scope === 'archived')->values();

        return view('admin.coe.venue_allocation.index', [
            'scope'      => $scope,
            'rows'       => $rows,
            'courses'    => self::COURSES,
            'categories' => self::CATEGORIES,
            'subjects'   => self::SUBJECTS,
            'components' => self::COMPONENTS,
            'statuses'   => self::STATUSES,
            'buildings'  => $this->sampleBuildings(),
            'floorsByBuilding'    => $this->sampleFloorNamesByBuilding(),
            'roomsByBuildingFloor'=> $this->sampleRoomsByBuildingFloor(),
        ]);
    }

    public function edit(int $id)
    {
        $allocation = $this->sampleAllocations()->firstWhere('id', $id);
        abort_if($allocation === null, 404);

        return view('admin.coe.venue_allocation.edit', [
            'allocation' => $allocation,
        ]);
    }

    /** @return Collection<int, array> */
    private function sampleAllocations(): Collection
    {
        $rows = [
            // course, category, subject, component, otCount, laptopReq, scriberReq,
            // building, floor, room, capacity, availLaptop, scriberSeats, emptyLaptop, status, archived
            ['FC101', 'General Category', 'Law', 'Ethics & Integrity', 100, 20, '-',
             'Aadharsila', '2nd Floor – AS-SF02', 'First Room – AS-SF02-R1', 100, 22, '-',  '00', 'Pending', false],
            ['FC101', 'PH Category',      'Law', 'Ethics & Integrity',  50,  20, 20,
             'Aadharsila', '2nd Floor – AS-SF',   'Exam Hall – AS-SF-EH1',   60,  60, 32, '00', 'Frozen',  false],
        ];

        return collect($rows)->map(fn (array $r, int $i) => [
            'id'           => $i + 1,
            'course'       => $r[0],
            'category'     => $r[1],
            'subject'      => $r[2],
            'component'    => $r[3],
            'ot_count'     => $r[4],
            'laptop_req'   => $r[5],
            'scriber_req'  => $r[6],
            'building'     => $r[7],
            'floor'        => $r[8],
            'room'         => $r[9],
            'capacity'     => $r[10],
            'avail_laptop' => $r[11],
            'scriber_seats'=> $r[12],
            'empty_laptop' => $r[13],
            'status'       => $r[14],
            'archived'     => $r[15],
            // Sample seat assignments for the edit page
            'seat_assignments' => [
                ['roll' => 'FC101-047', 'name' => 'Rajesh Kumar Sharma', 'seat' => 'A08', 'block' => 'Centre', 'status' => 'Selected'],
                ['roll' => 'FC101-052', 'name' => 'Priya Rani Menon',    'seat' => 'A06', 'block' => 'Centre', 'status' => 'Swap Target'],
            ],
            'total_seats'    => 105,
            'assigned_seats' => 98,
            'empty_seats'    => 7,
            'pending_swaps'  => 1,
        ]);
    }
}
