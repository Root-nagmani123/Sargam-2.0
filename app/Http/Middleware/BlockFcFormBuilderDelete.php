<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Safety catch for the FC form-builder delete endpoints (delete form / step /
 * field / group / group field / document master).
 *
 * The FC intake is live while the rest of the project is still in development, and
 * these deletes cascade to trainee data, so the buttons are hidden in the UI and this
 * guard closes the endpoints behind them — a stale tab, a bookmarked action or a
 * hand-crafted POST cannot delete either.
 *
 * Nothing is removed: flip FC_FORM_BUILDER_DELETE_ENABLED=true (config fc.php →
 * form_builder_delete_enabled) and both the buttons and the endpoints come back
 * exactly as they were.
 */
class BlockFcFormBuilderDelete
{
    public function handle(Request $request, Closure $next)
    {
        if (config('fc.form_builder_delete_enabled')) {
            return $next($request);
        }

        $message = 'Deleting is disabled on this environment. Set the item to inactive instead.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return back()->with('error', $message);
    }
}
