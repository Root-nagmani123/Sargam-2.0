<?php

namespace App\Http\Controllers\Admin\Athletics;

use App\Models\Athletics\AthleticsHouse;
use App\Models\Athletics\AthleticsHouseMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HouseController extends AthleticsController
{
    public function index()
    {
        return view('admin.athletics.houses.index', [
            'houses' => AthleticsHouse::withCount('members')->orderByDesc('id')->get(),
            'courses' => $this->courseOptions(),
            'inUse' => DB::table('athletics_results')->whereNotNull('house_id')->distinct()->pluck('house_id')->flip(),
        ]);
    }

    /** OTs of a course not yet in any house for it — feeds the OT picker. */
    public function ots(Request $request)
    {
        $coursePk = (int) $request->query('course');
        $placed = AthleticsHouseMember::where('course_master_pk', $coursePk)->pluck('student_master_pk')->flip();

        return response()->json(
            $this->courseOts($coursePk)
                ->reject(fn ($ot) => $placed->has($ot->pk))
                ->map(fn ($ot) => ['id' => $ot->pk, 'text' => static::otLabel($ot)])
                ->values()
        );
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $house = AthleticsHouse::create(['name' => $data['name'], 'status' => $data['status'], 'created_by' => Auth::id()]);

        return back()->with('success', 'House created successfully.'.$this->addMembers($house, $data));
    }

    /** Edit renames / re-statuses and may ADD OTs; removal is done from the House OTs list. */
    public function update(Request $request, AthleticsHouse $house)
    {
        $data = $this->validated($request);
        $house->update(['name' => $data['name'], 'status' => $data['status']]);

        return back()->with('success', 'House updated successfully.'.$this->addMembers($house, $data));
    }

    public function destroy(AthleticsHouse $house)
    {
        if (DB::table('athletics_results')->where('house_id', $house->id)->exists()) {
            return back()->with('error', 'This house has recorded results and cannot be deleted. Deactivate it instead.');
        }

        $house->delete(); // members cascade

        return back()->with('success', 'House deleted successfully.');
    }

    public function members(AthleticsHouse $house)
    {
        return view('admin.athletics.houses.members', [
            'house' => $house,
            'members' => $house->members()->with(['student:pk,display_name,generated_OT_code,user_name', 'course:pk,course_name,couse_short_name'])->get(),
        ]);
    }

    public function removeMember(AthleticsHouse $house, AthleticsHouseMember $member)
    {
        abort_unless((int) $member->house_id === (int) $house->id, 404);
        $member->delete();

        return back()->with('success', 'OT removed from '.$house->name.'.');
    }

    public function export(Request $request, string $format = 'csv')
    {
        return $this->exportGrid($request, $format, AthleticsHouse::withCount('members')->orderByDesc('id')->get(), [
            'sno' => $this->snoColumn(),
            'name' => ['heading' => 'House Name', 'width' => '50%', 'value' => fn ($r) => $r->name],
            'ots' => ['heading' => 'OT', 'width' => '20%', 'align' => 'center', 'value' => fn ($r) => (int) $r->members_count],
            'status' => $this->statusColumn(),
        ], 'House Master', 'AthleticsHouseMaster');
    }

    public function exportMembers(Request $request, AthleticsHouse $house, string $format = 'csv')
    {
        $members = $house->members()->with(['student', 'course'])->get();

        return $this->exportGrid($request, $format, $members, [
            'sno' => $this->snoColumn(),
            'course' => ['heading' => 'Course Name', 'width' => '22%', 'value' => fn ($m) => static::courseLabel($m->course)],
            'ot' => ['heading' => 'OT Name', 'width' => '35%', 'value' => fn ($m) => static::otLabel($m->student)],
            'user_name' => ['heading' => 'User Name', 'width' => '35%', 'value' => fn ($m) => $m->student->user_name ?? '—'],
        ], $house->name.' House OTs', 'AthleticsHouseOTs');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'status' => 'required|in:0,1',
            'course_master_pk' => 'nullable|required_with:student_ids|integer',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer',
        ]);
    }

    /** Adds picked OTs; any already placed in a house for that course stay put and are reported. */
    private function addMembers(AthleticsHouse $house, array $data): string
    {
        $coursePk = (int) ($data['course_master_pk'] ?? 0);
        $picked = collect($data['student_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
        if (! $coursePk || $picked->isEmpty()) {
            return '';
        }

        // Only OTs actually enrolled in the course — the picker is client input.
        $enrolled = $this->courseOts($coursePk)->pluck('pk')->map(fn ($pk) => (int) $pk)->flip();
        $rows = $picked->filter(fn ($id) => $enrolled->has($id))
            ->map(fn ($id) => [
                'house_id' => $house->id, 'course_master_pk' => $coursePk, 'student_master_pk' => $id,
                'created_at' => now(), 'updated_at' => now(),
            ])->values()->all();

        $added = DB::table('athletics_house_members')->insertOrIgnore($rows);
        $skipped = $picked->count() - $added;

        return " {$added} OT(s) added".($skipped > 0 ? ", {$skipped} skipped (already in a house or not in the course)." : '.');
    }
}
