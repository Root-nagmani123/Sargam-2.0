<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Virtual model for HAC Approved DataTable.
 * The actual query is a UNION subquery aliased as 'hac_approved'.
 * This model's table name must be 'hac_approved' so that Yajra DataTables
 * applies search/order to the correct alias (hac_approved.request_id etc.)
 * instead of estate_home_request_details.request_id (which does not exist).
 */
class EstateHacApprovedRow extends Model
{
    protected $table = 'hac_approved';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    public $timestamps = false;

    /**
     * Columns exposed by the union subquery (aliases from estate_change_home_req_details + estate_home_request_details).
     * Not used for mass assignment; only for reference.
     */
    protected $fillable = [
        'request_type',
        'source_pk',
        'pk',
        'request_id',
        'request_date',
        'emp_name',
        'employee_id',
        'emp_designation',
        'pay_scale',
        'doj_pay_scale',
        'doj_service',
        'doj_academic',
        'eligibility_label',
        'current_or_availability',
        'remarks',
        'change_ap_dis_status',
    ];
}
