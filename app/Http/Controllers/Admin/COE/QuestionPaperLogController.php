<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\QuestionPaperLogger;
use Illuminate\Http\Request;

/**
 * COE → Question Paper Management → Question Paper Management Logs: who
 * viewed, wrote, translated, froze, printed or unfroze which question paper,
 * when and from where. Entries come from QuestionPaperLogger::entries().
 *
 * Two scopes, each its own URL (docs/new-design-index-page.md §1 "Scope tabs"):
 *   ?scope=all        every entry (default)
 *   ?scope=translated only the translation's entries
 */
class QuestionPaperLogController extends Controller
{
    public const SCOPES = ['all', 'translated'];

    public function __construct(private QuestionPaperLogger $log)
    {
    }

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true) ? $request->query('scope') : 'all';
        $entries = $this->log->entries($scope === 'translated' ? QuestionPaperLogger::MODULE_TRANSLATION : null);

        $options = fn (string $key) => $entries->pluck($key)->reject(fn ($v) => $v === '-')->unique()->sort()->values();

        return view('admin.coe.question_paper_logs.index', [
            'scope' => $scope,
            'entries' => $entries,
            'drives' => $options('drive'),
            'batches' => $options('batch'),
            'faculties' => $options('faculty'),
            'actions' => QuestionPaperLogger::ACTIONS,
            'roles' => QuestionPaperLogger::ROLES,
        ]);
    }
}
