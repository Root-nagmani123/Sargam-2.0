<?php

namespace Modules\Protocol\Http\Controllers\Concerns;

use Illuminate\Validation\ValidationException;

trait SafeErrorMessage
{
    /**
     * Message to flash for a caught exception. Validation messages are meant for the
     * user and pass through; anything else (SQL, table and column names) is logged instead.
     */
    protected function safeErrorMessage(\Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            return $e->getMessage();
        }

        report($e);

        return 'Something went wrong. Please try again, or contact IT support if the problem continues.';
    }
}
