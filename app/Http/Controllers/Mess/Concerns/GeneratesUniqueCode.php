<?php

namespace App\Http\Controllers\Mess\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Shared "max(id)+1, then probe for a taken code, increment and retry" pattern used by
 * StoreController::generateStoreCode(), ItemSubcategoryController::generateItemCode(), and
 * PurchaseOrderController::generatePoNumber() — three byte-identical copies of this loop with
 * only the model class and code format differing.
 *
 * This is a pure code-location move: the probe-and-retry behavior (and its pre-existing
 * theoretical race condition under concurrent requests) is unchanged. Fixing that race
 * condition would require wrapping callers in a locking transaction, which is a separate,
 * larger change — out of scope here.
 */
trait GeneratesUniqueCode
{
    /**
     * @param  callable(int): EloquentBuilder  $maxIdQuery  Returns a fresh query to find max('id') on the target model.
     * @param  callable(int): string  $formatCode  Formats a candidate code from the running counter.
     * @param  callable(string): bool  $codeExists  Returns true if the candidate code is already taken.
     */
    protected function generateUniqueSequentialCode(
        callable $maxIdQuery,
        callable $formatCode,
        callable $codeExists
    ): string {
        $next = ((int) $maxIdQuery()) + 1;
        $code = $formatCode($next);

        while ($codeExists($code)) {
            $next++;
            $code = $formatCode($next);
        }

        return $code;
    }
}
