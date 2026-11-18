<?php

namespace App\Http\Controllers\Admin\Athletics;

use App\Models\Athletics\AthleticsDrive;
use App\Models\Athletics\AthleticsEvent;
use App\Models\Athletics\AthleticsHouseMember;
use App\Models\Athletics\AthleticsNomination;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NominationController extends AthleticsController
{
    private const TABS = ['all' => 'All', 'approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Reject'];

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('admin.athletics.nominations.index', [
            'nominations' => $this->nominations($filters),
            'filters' => $filters,
            'tabs' => self::TABS,
            'drives' => AthleticsDrive::orderByDesc('id')->get(['id', 'form_title']),
            'events' => AthleticsEvent::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Events of a drive + its eligible OTs who have not been nominated yet — feeds the Add modal. */
    public function driveOptions(AthleticsDrive $drive)
    {
        $taken = $drive->nominations()->pluck('student_master_pk')->flip();

        return response()->json([
            'events' => $drive->events()->orderBy('name')->get(['athletics_events.id', 'name', 'category']),
            'ots' => $this->eligibleOts($drive)
                ->reject(fn ($ot) => $taken->has($ot->pk))
                ->map(fn ($ot) => ['id' => $ot->pk, 'text' => static::otLabel($ot)])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'drive_id' => 'required|integer|exists:athletics_drives,id',
            'student_master_pk' => 'required|integer',
            'event_ids' => 'required|array|min:1',
            'event_ids.*' => 'integer',
        ]);
        $drive = AthleticsDrive::findOrFail($data['drive_id']);

        if (! $this->eligibleOts($drive)->contains('pk', $data['student_master_pk'])) {
            return back()->with('error', 'That OT is not enrolled in the drive\'s course or the drive is not open to them.');
        }
        if ($drive->nominations()->where('student_master_pk', $data['student_master_pk'])->exists()) {
            return back()->with('error', 'That OT already has a nomination for this drive — edit it instead.');
        }

        $events = $this->checkedEvents($drive, $data['event_ids']);
        if (is_string($events)) {
            return back()->with('error', $events);
        }

        DB::transaction(function () use ($drive, $data, $events) {
            $nomination = AthleticsNomination::create([
                'drive_id' => $drive->id,
                'student_master_pk' => $data['student_master_pk'],
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);
            $nomination->events()->sync($events->pluck('id'));
        });

        return back()->with('success', 'Nomination added.');
    }

    public function update(Request $request, AthleticsNomination $nomination)
    {
        if ($nomination->status === 'approved') {
            return back()->with('error', 'An approved nomination is frozen and cannot be edited.');
        }

        $data = $request->validate(['event_ids' => 'required|array|min:1', 'event_ids.*' => 'integer']);
        $events = $this->checkedEvents($nomination->drive, $data['event_ids']);
        if (is_string($events)) {
            return back()->with('error', $events);
        }

        $nomination->events()->sync($events->pluck('id'));

        return back()->with('success', 'Nomination updated.');
    }

    /**
     * Approve (also "Freeze All"), decline or delete one or many nominations.
     * Approved rows are frozen: they are never declined or deleted from here.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'delete'])],
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $query = AthleticsNomination::whereIn('id', $data['ids'])->where('status', '!=', 'approved');

        $count = match ($data['action']) {
            'approve' => $query->update(['status' => 'approved']),
            'reject' => $query->where('status', 'pending')->update(['status' => 'rejected']),
            'delete' => $query->delete(),
        };

        $verb = ['approve' => 'approved', 'reject' => 'declined', 'delete' => 'deleted'][$data['action']];
        $skipped = count(array_unique($data['ids'])) - $count;

        return back()->with('success', "{$count} nomination(s) {$verb}.".($skipped > 0 ? " {$skipped} skipped (already approved or not pending)." : ''));
    }

    public function export(Request $request, string $format = 'csv')
    {
        return $this->exportGrid($request, $format, $this->nominations($this->filters($request)), [
            'sno' => $this->snoColumn(),
            'ot_code' => ['heading' => 'OT Code', 'width' => '10%', 'value' => fn ($n) => $n->student->generated_OT_code ?? '—'],
            'ot' => ['heading' => 'OTs', 'width' => '16%', 'value' => fn ($n) => $n->student->display_name ?? '—'],
            'house' => ['heading' => 'House', 'width' => '11%', 'value' => fn ($n) => $n->house_name ?? '—'],
            'events' => ['heading' => 'Nomination For', 'width' => '30%', 'value' => fn ($n) => $n->events->pluck('name')->implode(', ')],
            'drive' => ['heading' => 'Drive', 'width' => '15%', 'value' => fn ($n) => $n->drive->form_title ?? '—'],
            'status' => ['heading' => 'Status', 'width' => '10%', 'align' => 'center', 'value' => fn ($n) => AthleticsNomination::STATUSES[$n->status] ?? $n->status],
        ], 'Nominations', 'AthleticsNominations', 'landscape');
    }

    private function filters(Request $request): array
    {
        $status = (string) $request->query('status', 'all');

        return [
            'status' => array_key_exists($status, self::TABS) ? $status : 'all',
            'drive' => (int) $request->query('drive') ?: null,
            'event' => (int) $request->query('event') ?: null,
        ];
    }

    /** The grid's rows, each carrying house_name from the OT's house for the drive's course. */
    private function nominations(array $filters): Collection
    {
        $rows = AthleticsNomination::with(['student:pk,display_name,generated_OT_code', 'drive:id,form_title,course_master_pk', 'events:athletics_events.id,name'])
            ->when($filters['status'] !== 'all', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['drive'], fn ($q, $id) => $q->where('drive_id', $id))
            ->when($filters['event'], fn ($q, $id) => $q->whereHas('events', fn ($e) => $e->where('athletics_events.id', $id)))
            ->orderByDesc('id')
            ->get();

        $houses = AthleticsHouseMember::with('house:id,name')
            ->whereIn('course_master_pk', $rows->pluck('drive.course_master_pk')->unique())
            ->whereIn('student_master_pk', $rows->pluck('student_master_pk')->unique())
            ->get()
            ->keyBy(fn ($m) => $m->course_master_pk.'|'.$m->student_master_pk);

        return $rows->each(function ($n) use ($houses) {
            $n->house_name = $houses->get(($n->drive->course_master_pk ?? '').'|'.$n->student_master_pk)?->house?->name;
        });
    }

    private function eligibleOts(AthleticsDrive $drive): Collection
    {
        return $this->courseOts((int) $drive->course_master_pk)
            ->filter(fn ($ot) => $drive->isVisibleTo(AthleticsDrive::normaliseGender($ot->gender)))
            ->values();
    }

    /** The drive's events among $ids, or the reason they can't be nominated together. */
    private function checkedEvents(AthleticsDrive $drive, array $ids): Collection|string
    {
        $events = $drive->events()->whereIn('athletics_events.id', $ids)->get();
        if ($events->count() !== count(array_unique($ids))) {
            return 'One of the picked events is not part of this drive.';
        }

        return $drive->limitError($events) ?? $events;
    }
}
