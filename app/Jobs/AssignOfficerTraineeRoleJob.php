<?php

namespace App\Jobs;

use App\Services\OfficerTraineeRoleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignOfficerTraineeRoleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int>|null  $userCredentialPks  Specific user_credentials.pk values, or null for all missing.
     */
    public function __construct(
        public ?array $userCredentialPks = null
    ) {}

    public function handle(OfficerTraineeRoleService $service): void
    {
        if ($this->userCredentialPks !== null && $this->userCredentialPks !== []) {
            $service->assignToUserPks($this->userCredentialPks);
        } else {
            $service->assignToAllMissingStudents();
        }
    }
}
