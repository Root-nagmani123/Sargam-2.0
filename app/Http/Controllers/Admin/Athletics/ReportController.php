<?php

namespace App\Http\Controllers\Admin\Athletics;

use App\Models\Athletics\AthleticsDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Overall Champion: houses ranked by points, medals as tie-breakers. */
class ReportController extends AthleticsController
{
    public function champion(Request $request)
    {
        $filters = $this->filters($request);

        return view('admin.athletics.reports.champion', [
            'rows' => $this->standings($filters),
            'filters' => $filters,
            'drives' => AthleticsDrive::where('status', 'active')->orderByDesc('id')->get(['id', 'form_title', 'course_master_pk']),
            'courses' => $this->courseOptions(),
        ]);
    }

    public function championExport(Request $request, string $format = 'csv')
    {
        return $this->exportGrid($request, $format, $this->standings($this->filters($request)), [
            'rank' => ['heading' => 'Rank', 'width' => '10%', 'align' => 'center', 'value' => fn ($r) => $r->rank],
            'house' => ['heading' => 'House', 'width' => '38%', 'value' => fn ($r) => $r->name],
            'gold' => ['heading' => '1st', 'width' => '12%', 'align' => 'center', 'value' => fn ($r) => (int) $r->gold],
            'silver' => ['heading' => '2nd', 'width' => '12%', 'align' => 'center', 'value' => fn ($r) => (int) $r->silver],
            'bronze' => ['heading' => '3rd', 'width' => '12%', 'align' => 'center', 'value' => fn ($r) => (int) $r->bronze],
            'points' => ['heading' => 'Total Points', 'width' => '16%', 'align' => 'center', 'value' => fn ($r) => (float) $r->points],
        ], 'Overall Champion', 'AthleticsOverallChampion');
    }

    private function filters(Request $request): array
    {
        return ['course' => (int) $request->query('course') ?: null, 'drive' => (int) $request->query('drive') ?: null];
    }

    private function standings(array $filters): Collection
    {
        $rows = DB::table('athletics_results as r')
            ->join('athletics_houses as h', 'h.id', '=', 'r.house_id')
            ->join('athletics_drives as d', 'd.id', '=', 'r.drive_id')
            ->when($filters['course'], fn ($q, $c) => $q->where('d.course_master_pk', $c))
            ->when($filters['drive'], fn ($q, $id) => $q->where('r.drive_id', $id))
            ->groupBy('h.id', 'h.name')
            ->selectRaw('h.id, h.name, SUM(r.points) AS points,
                SUM(CASE WHEN r.position = 1 THEN 1 ELSE 0 END) AS gold,
                SUM(CASE WHEN r.position = 2 THEN 1 ELSE 0 END) AS silver,
                SUM(CASE WHEN r.position = 3 THEN 1 ELSE 0 END) AS bronze')
            ->orderByDesc('points')->orderByDesc('gold')->orderByDesc('silver')->orderByDesc('bronze')->orderBy('h.name')
            ->get();

        // Competition ranking: houses level on points and medals share a rank (1, 1, 3).
        $prev = null;
        foreach ($rows as $i => $row) {
            $key = [(float) $row->points, (int) $row->gold, (int) $row->silver, (int) $row->bronze];
            $row->rank = ($prev && $prev[0] === $key) ? $prev[1] : $i + 1;
            $prev = [$key, $row->rank];
        }

        return $rows;
    }
}
