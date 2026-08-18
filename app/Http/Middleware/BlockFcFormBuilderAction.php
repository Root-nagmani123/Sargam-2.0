<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Per-action safety catch for the FC form-builder endpoints that are destructive
 * to a LIVE intake without being deletes — adding a step, reordering steps.
 *
 * Sibling of BlockFcFormBuilderDelete, but parameterised: the route names the
 * config flag it is gated on, so each action keeps its own switch and an admin
 * can be given exactly one of them.
 *
 *     Route::post('/steps/reorder', ...)->middleware('fc.builder.action:form_step_reorder_enabled')
 *
 * Field-level locks (step target table, tracker column, is_active …) are NOT
 * enforced here — middleware cannot see past the request body into which field
 * moved. Those are applied in FormManagementController, which substitutes the
 * stored value before validation so the save succeeds with the field unchanged.
 */
class BlockFcFormBuilderAction
{
    /** Config flag → what the user was trying to do, for the error message. */
    private const LABELS = [
        'form_step_add_enabled'     => 'Adding a step',
        'form_step_reorder_enabled' => 'Reordering steps',
        'form_step_actions_enabled' => 'Editing a step or its fields',
    ];

    public function handle(Request $request, Closure $next, string $flag)
    {
        if (config('fc.'.$flag)) {
            return $next($request);
        }

        $what = self::LABELS[$flag] ?? 'This action';

        $message = $what.' is disabled on this environment.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return back()->with('error', $message);
    }
}
