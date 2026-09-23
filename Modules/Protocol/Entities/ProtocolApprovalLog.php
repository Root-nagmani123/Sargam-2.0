<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\EmployeeMaster;

class ProtocolApprovalLog extends Model
{
    protected $table = 'protocol_approval_logs';

    protected $fillable = [
        'protocol_request_id',
        'action',
        'action_by_id',
        'action_by_role',
        'recommended_to_id',
        'remarks',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function protocolRequest(): BelongsTo
    {
        return $this->belongsTo(ProtocolRequest::class, 'protocol_request_id');
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'action_by_id');
    }

    public function recommendedTo(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'recommended_to_id');
    }
}
