<?php

namespace App\Services\COE;

use RuntimeException;

/** A marks save refused for a reason the faculty can fix; rolls the transaction back. */
class ResultMarksException extends RuntimeException
{
}
