<?php

namespace Modules\GuestTaClaim\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Modules\GuestTaClaim\Models\ClaimAttachment;

class AttachmentController extends Controller
{
    public function __invoke(ClaimAttachment $attachment)
    {
        $this->authorizeView($attachment->claim);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
