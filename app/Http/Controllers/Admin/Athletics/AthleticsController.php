<?php

namespace App\Http\Controllers\Admin\Athletics;

use App\Http\Controllers\Concerns\ExportsMasterGrid;
use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared by every Athletics Meet screen: Download (CSV / Excel / PDF) and Print
 * all go through ExportsMasterGrid off one column definition per grid.
 */
abstract class AthleticsController extends Controller
{
    use ExportsMasterGrid;

    /**
     * The grids are client-side DataTables whose search matches any visible
     * cell text, labels included — so the export filters the same rendered
     * values rather than SQL columns, or a searched export would disagree with
     * the screen.
     */
    protected function exportGrid(Request $request, string $format, Collection $rows, array $defs, string $title, string $slug, string $orientation = 'portrait')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, static::$exportFormats, true), 404);

        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $searchable = array_diff_key($defs, ['sno' => true]);
            $rows = $rows->filter(fn ($row) => collect($searchable)->contains(
                fn ($col) => str_contains(mb_strtolower((string) $col['value']($row, 0)), $needle)
            ))->values();
        }

        return $this->renderMasterExport(
            $format,
            $rows->values(),
            $this->resolveExportColumns($request, $defs),
            $title,
            $slug,
            $q !== '' ? 'Search: '.$q : null,
            'Nothing to export',
            $orientation
        );
    }

    /** OTs enrolled (actively) in a course: pk, display_name, generated_OT_code, gender, user_name. */
    protected function courseOts(int $coursePk): Collection
    {
        return DB::table('student_master_course__map as m')
            ->join('student_master as sm', 'sm.pk', '=', 'm.student_master_pk')
            ->where('m.course_master_pk', $coursePk)
            ->where('m.active_inactive', 1)
            ->orderBy('sm.display_name')
            ->distinct()
            ->get(['sm.pk', 'sm.display_name', 'sm.generated_OT_code', 'sm.gender', 'sm.user_name']);
    }

    /** Running courses for pickers, labelled the way the grids show them. */
    protected function courseOptions(): Collection
    {
        return CourseMaster::activeRunning()->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name'])
            ->mapWithKeys(fn ($c) => [$c->pk => static::courseLabel($c)]);
    }

    public static function courseLabel($course): string
    {
        return (string) ($course?->couse_short_name ?: $course?->course_name ?: '—');
    }

    public static function otLabel($ot): string
    {
        return trim(($ot->display_name ?? '').(filled($ot->generated_OT_code ?? null) ? ' - '.$ot->generated_OT_code : ''));
    }

    /** Running serial column every grid starts with. */
    protected function snoColumn(): array
    {
        return ['heading' => 'S. No.', 'width' => '8%', 'align' => 'center', 'value' => fn ($row, int $i) => $i + 1];
    }

    protected function statusColumn(): array
    {
        return ['heading' => 'Status', 'width' => '12%', 'align' => 'center', 'value' => fn ($row) => (int) $row->status === 1 ? 'Active' : 'Inactive'];
    }
}
