<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\DashboardStatItem;
use App\Models\DashboardStatSnapshot;
use App\Models\StudentMasterCourseMap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardStatisticsController extends Controller
{
    public function index()
    {
        $snapshots = DashboardStatSnapshot::withCount('items')
            ->orderBy('snapshot_date', 'desc')
            ->paginate(15);
        $courses = $this->getCoursesForDropdown();
        return view('admin.dashboard_statistics.index', compact('snapshots', 'courses'));
    }

    public function create()
    {
        return view('admin.dashboard_statistics.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'snapshot_date' => 'required|date',
            'title' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $snapshot = DashboardStatSnapshot::create([
                'snapshot_date' => $validated['snapshot_date'],
                'title' => $validated['title'] ?? null,
                'is_default' => $request->boolean('is_default'),
            ]);

            if ($request->boolean('is_default')) {
                DashboardStatSnapshot::where('id', '!=', $snapshot->id)->update(['is_default' => false]);
            }

            $this->syncItems($snapshot, $request);
        });

        return redirect()
            ->route('admin.dashboard-statistics.index')
            ->with('success', 'Dashboard statistics snapshot created successfully.');
    }

    public function edit(DashboardStatSnapshot $dashboard_statistic)
    {
        $snapshot = $dashboard_statistic->load('items');
        return view('admin.dashboard_statistics.edit', compact('snapshot'));
    }

    public function update(Request $request, DashboardStatSnapshot $dashboard_statistic)
    {
        $validated = $request->validate([
            'snapshot_date' => 'required|date',
            'title' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $validated, $dashboard_statistic) {
            $dashboard_statistic->update([
                'snapshot_date' => $validated['snapshot_date'],
                'title' => $validated['title'] ?? null,
                'is_default' => $request->boolean('is_default'),
            ]);

            if ($request->boolean('is_default')) {
                DashboardStatSnapshot::where('id', '!=', $dashboard_statistic->id)->update(['is_default' => false]);
            }

            $this->syncItems($dashboard_statistic, $request);
        });

        return redirect()
            ->route('admin.dashboard-statistics.index')
            ->with('success', 'Dashboard statistics updated successfully.');
    }

    public function destroy(DashboardStatSnapshot $dashboard_statistic)
    {
        $dashboard_statistic->delete();
        return redirect()
            ->route('admin.dashboard-statistics.index')
            ->with('success', 'Snapshot deleted successfully.');
    }

    public function setDefault(DashboardStatSnapshot $dashboard_statistic)
    {
        DashboardStatSnapshot::query()->update(['is_default' => false]);
        $dashboard_statistic->update(['is_default' => true]);
        return redirect()
            ->route('admin.dashboard-statistics.index')
            ->with('success', 'Default snapshot updated.');
    }

    /**
     * Save current enrolled-student data for a course as a snapshot (batch profile).
     */
    public function saveSnapshotFromCourse(Request $request)
    {
        $validated = $request->validate([
            'course_master_pk' => 'required|exists:course_master,pk',
            'snapshot_date' => 'required|date',
            'title' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        $course = CourseMaster::findOrFail($validated['course_master_pk']);
        $chartData = $this->aggregateChartDataFromCourse((int) $validated['course_master_pk']);

        if (empty($chartData['social_groups']['categories']) && empty($chartData['gender']['labels'])) {
            return redirect()
                ->back()
                ->withErrors(['course_master_pk' => 'No enrolled students found for this course.'])
                ->withInput($request->only('course_master_pk', 'snapshot_date', 'title', 'is_default'));
        }

        DB::transaction(function () use ($validated, $chartData) {
            $snapshot = DashboardStatSnapshot::create([
                'snapshot_date' => $validated['snapshot_date'],
                'title' => $validated['title'] ?? $validated['snapshot_date'],
                'is_default' => $request->boolean('is_default'),
            ]);

            if ($request->boolean('is_default')) {
                DashboardStatSnapshot::where('id', '!=', $snapshot->id)->update(['is_default' => false]);
            }

            $this->createItemsFromChartData($snapshot, $chartData);
        });

        return redirect()
            ->route('admin.dashboard-statistics.index')
            ->with('success', 'Batch profile saved as snapshot successfully.');
    }

    /**
     * Create dashboard stat items from aggregated chart data (e.g. from course).
     */
    protected function createItemsFromChartData(DashboardStatSnapshot $snapshot, array $chartData): void
    {
        $sortOrder = 0;

        foreach ($chartData['social_groups']['categories'] ?? [] as $i => $label) {
            $snapshot->items()->create([
                'chart_type' => 'social_groups',
                'label' => $label,
                'female_count' => (int) ($chartData['social_groups']['female'][$i] ?? 0),
                'male_count' => (int) ($chartData['social_groups']['male'][$i] ?? 0),
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($chartData['gender']['labels'] ?? [] as $i => $label) {
            $snapshot->items()->create([
                'chart_type' => 'gender',
                'label' => $label,
                'value' => (float) ($chartData['gender']['values'][$i] ?? 0),
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($chartData['age']['categories'] ?? [] as $i => $label) {
            $snapshot->items()->create([
                'chart_type' => 'age',
                'label' => $label,
                'female_count' => (int) ($chartData['age']['female'][$i] ?? 0),
                'male_count' => (int) ($chartData['age']['male'][$i] ?? 0),
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($chartData['stream']['categories'] ?? [] as $i => $label) {
            $snapshot->items()->create([
                'chart_type' => 'stream',
                'label' => $label,
                'value' => (float) ($chartData['stream']['values'][$i] ?? 0),
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($chartData['cadre']['categories'] ?? [] as $i => $label) {
            $snapshot->items()->create([
                'chart_type' => 'cadre',
                'label' => $label,
                'female_count' => (int) ($chartData['cadre']['female'][$i] ?? 0),
                'male_count' => (int) ($chartData['cadre']['male'][$i] ?? 0),
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($chartData['domicile']['categories'] ?? [] as $i => $label) {
            $snapshot->items()->create([
                'chart_type' => 'domicile',
                'label' => $label,
                'value' => (float) ($chartData['domicile']['values'][$i] ?? 0),
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    public function charts(Request $request)
    {
        $coursePk = $request->get('course_master_pk');
        $snapshotId = $request->get('snapshot_id');

        $course = null;
        $snapshot = null;
        $chartData = [];

        if ($coursePk) {
            $course = CourseMaster::find($coursePk);
            if ($course) {
                $chartData = $this->aggregateChartDataFromCourse((int) $coursePk);
            }
        }

        if (empty($chartData) && $snapshotId) {
            $snapshot = DashboardStatSnapshot::with('items')->findOrFail($snapshotId);
            $chartData = $this->buildChartData($snapshot);
        }

        if (empty($chartData) && !$coursePk) {
            $snapshot = DashboardStatSnapshot::getDefaultOrLatest();
            if ($snapshot) {
                $snapshot->load('items');
                $chartData = $this->buildChartData($snapshot);
            }
        }

        $courses = $this->getCoursesForDropdown();

        if (empty($chartData)) {
            return view('admin.dashboard_statistics.charts', [
                'snapshot' => $snapshot,
                'chartData' => [],
                'course' => $course,
                'courses' => $courses,
            ]);
        }

        return view('admin.dashboard_statistics.charts', compact('snapshot', 'chartData', 'course', 'courses'));
    }

    /**
     * Aggregate chart data from enrolled students for a specific course.
     */
    protected function aggregateChartDataFromCourse(int $courseMasterPk): array
    {
        $rows = DB::table('student_master_course__map as smcm')
            ->join('student_master as sm', 'smcm.student_master_pk', '=', 'sm.pk')
            ->leftJoin('admission_category_master as acm', 'sm.admission_category_pk', '=', 'acm.pk')
            ->leftJoin('stream_master as strm', 'sm.highest_stream_pk', '=', 'strm.pk')
            ->leftJoin('cadre_master as cm', 'sm.cadre_master_pk', '=', 'cm.pk')
            ->leftJoin('state_master as st', 'sm.domicile_state_pk', '=', 'st.pk')
            ->where('smcm.course_master_pk', $courseMasterPk)
            ->where('smcm.active_inactive', 1)
            ->where('sm.active_inactive', 1)
            ->select(
                'sm.gender',
                'sm.dob',
                DB::raw('COALESCE(acm.seat_name, "Not Specified") as social_group'),
                DB::raw('COALESCE(strm.stream_name, "Other") as stream_name'),
                DB::raw('COALESCE(cm.cadre_name, "Other") as cadre_name'),
                DB::raw('COALESCE(st.state_name, "Not Specified") as state_name')
            )
            ->get();

        $socialGroups = [];
        $genderCounts = ['Female' => 0, 'Male' => 0];
        $ageBuckets = ['18-25' => ['female' => 0, 'male' => 0], '26-30' => ['female' => 0, 'male' => 0], '31-35' => ['female' => 0, 'male' => 0], 'Other' => ['female' => 0, 'male' => 0]];
        $streamCounts = [];
        $cadreCounts = [];
        $domicileCounts = [];

        foreach ($rows as $row) {
            $isFemale = $this->isFemale($row->gender);
            $genderKey = $isFemale ? 'Female' : 'Male';
            $genderCounts[$genderKey] = ($genderCounts[$genderKey] ?? 0) + 1;

            $ageBucket = $this->getAgeBucket($row->dob);
            if (!isset($ageBuckets[$ageBucket])) {
                $ageBuckets[$ageBucket] = ['female' => 0, 'male' => 0];
            }
            $ageBuckets[$ageBucket][$isFemale ? 'female' : 'male']++;

            $sg = $row->social_group ?? 'Not Specified';
            if (!isset($socialGroups[$sg])) {
                $socialGroups[$sg] = ['female' => 0, 'male' => 0];
            }
            $socialGroups[$sg][$isFemale ? 'female' : 'male']++;

            $stream = $row->stream_name ?? 'Other';
            $streamCounts[$stream] = ($streamCounts[$stream] ?? 0) + 1;

            $cadre = $row->cadre_name ?? 'Other';
            if (!isset($cadreCounts[$cadre])) {
                $cadreCounts[$cadre] = ['female' => 0, 'male' => 0];
            }
            $cadreCounts[$cadre][$isFemale ? 'female' : 'male']++;

            $state = $row->state_name ?? 'Not Specified';
            $domicileCounts[$state] = ($domicileCounts[$state] ?? 0) + 1;
        }

        $total = $rows->count();
        $genderPct = $total > 0
            ? ['Female' => round($genderCounts['Female'] / $total * 100, 1), 'Male' => round($genderCounts['Male'] / $total * 100, 1)]
            : ['Female' => 0, 'Male' => 0];

        return [
            'summary' => [
                'total_participants' => $total,
                'female_count' => $genderCounts['Female'],
                'male_count' => $genderCounts['Male'],
                'states_count' => count($domicileCounts),
                'cadres_count' => count($cadreCounts),
                'streams_count' => count($streamCounts),
                'social_groups_count' => count($socialGroups),
            ],
            'social_groups' => [
                'categories' => array_keys($socialGroups),
                'female' => array_values(array_column($socialGroups, 'female')),
                'male' => array_values(array_column($socialGroups, 'male')),
            ],
            'gender' => [
                'labels' => ['Female', 'Male'],
                'values' => [$genderPct['Female'], $genderPct['Male']],
            ],
            'age' => [
                'categories' => array_keys($ageBuckets),
                'female' => array_column($ageBuckets, 'female'),
                'male' => array_column($ageBuckets, 'male'),
            ],
            'stream' => [
                'categories' => array_keys($streamCounts),
                'values' => array_values($streamCounts),
            ],
            'cadre' => [
                'categories' => array_keys($cadreCounts),
                'female' => array_column($cadreCounts, 'female'),
                'male' => array_column($cadreCounts, 'male'),
            ],
            'domicile' => [
                'categories' => array_keys($domicileCounts),
                'values' => array_values($domicileCounts),
            ],
        ];
    }

    protected function isFemale($gender): bool
    {
        if ($gender === null || $gender === '') {
            return false;
        }
        $g = is_numeric($gender) ? (int) $gender : (string) $gender;
        if (is_int($g)) {
            return $g === 2 || $g === 0;
        }
        return in_array(strtolower(trim($g)), ['female', 'f', '2'], true);
    }

    protected function getAgeBucket($dob): string
    {
        if (!$dob) {
            return 'Other';
        }
        try {
            $age = Carbon::parse($dob)->age;
            if ($age >= 18 && $age <= 25) {
                return '18-25';
            }
            if ($age >= 26 && $age <= 30) {
                return '26-30';
            }
            if ($age >= 31 && $age <= 35) {
                return '31-35';
            }
        } catch (\Throwable $e) {
            return 'Other';
        }
        return 'Other';
    }

    protected function getCoursesForDropdown()
    {
        return CourseMaster::where('active_inactive', 1)
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);
    }

    protected function syncItems(DashboardStatSnapshot $snapshot, Request $request): void
    {
        $snapshot->items()->delete();

        $chartTypes = [
            'social_groups' => ['female_count', 'male_count'],
            'gender' => ['value'],
            'age' => ['female_count', 'male_count'],
            'stream' => ['value'],
            'cadre' => ['female_count', 'male_count'],
            'domicile' => ['value'],
        ];

        foreach ($chartTypes as $chartType => $fields) {
            $labels = $request->input("{$chartType}.label", []);
            if (!is_array($labels)) {
                continue;
            }
            foreach ($labels as $i => $label) {
                if (trim((string) $label) === '') {
                    continue;
                }
                $item = [
                    'chart_type' => $chartType,
                    'label' => $label,
                    'sort_order' => $i,
                ];
                if (in_array('female_count', $fields)) {
                    $item['female_count'] = (int) ($request->input("{$chartType}.female_count.{$i}") ?? 0);
                }
                if (in_array('male_count', $fields)) {
                    $item['male_count'] = (int) ($request->input("{$chartType}.male_count.{$i}") ?? 0);
                }
                if (in_array('value', $fields)) {
                    $item['value'] = (float) ($request->input("{$chartType}.value.{$i}") ?? 0);
                }
                $snapshot->items()->create($item);
            }
        }
    }

    protected function buildChartData(DashboardStatSnapshot $snapshot): array
    {
        $items = $snapshot->items->groupBy('chart_type');

        $get = function ($type) use ($items) {
            return $items->get($type, collect())->sortBy('sort_order')->values();
        };

        $socialGroups = $get('social_groups');
        $gender = $get('gender');
        $age = $get('age');
        $stream = $get('stream');
        $cadre = $get('cadre');
        $domicile = $get('domicile');

        $totalFromSocial = $socialGroups->sum('female_count') + $socialGroups->sum('male_count');
        $femaleTotal = $socialGroups->sum('female_count');
        $maleTotal = $socialGroups->sum('male_count');

        return [
            'summary' => [
                'total_participants' => (int) $totalFromSocial,
                'female_count' => (int) $femaleTotal,
                'male_count' => (int) $maleTotal,
                'states_count' => $domicile->count(),
                'cadres_count' => $cadre->count(),
                'streams_count' => $stream->count(),
                'social_groups_count' => $socialGroups->count(),
            ],
            'social_groups' => [
                'categories' => $socialGroups->pluck('label')->toArray(),
                'female' => $socialGroups->pluck('female_count')->toArray(),
                'male' => $socialGroups->pluck('male_count')->toArray(),
            ],
            'gender' => [
                'labels' => $gender->pluck('label')->toArray(),
                'values' => $gender->pluck('value')->toArray(),
            ],
            'age' => [
                'categories' => $age->pluck('label')->toArray(),
                'female' => $age->pluck('female_count')->toArray(),
                'male' => $age->pluck('male_count')->toArray(),
            ],
            'stream' => [
                'categories' => $stream->pluck('label')->toArray(),
                'values' => $stream->pluck('value')->map(fn ($v) => (float) $v)->toArray(),
            ],
            'cadre' => [
                'categories' => $cadre->pluck('label')->toArray(),
                'female' => $cadre->pluck('female_count')->toArray(),
                'male' => $cadre->pluck('male_count')->toArray(),
            ],
            'domicile' => [
                'categories' => $domicile->pluck('label')->toArray(),
                'values' => $domicile->pluck('value')->map(fn ($v) => (float) $v)->toArray(),
            ],
        ];
    }
}
