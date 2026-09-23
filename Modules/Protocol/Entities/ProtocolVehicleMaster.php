<?php

// namespace Modules\Protocol\Models;

// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

// class ProtocolVehicleMaster extends Model
// {
    // use SoftDeletes;

    // protected $table = 'protocol_vehicle_masters';

    // protected $fillable = [
        // 'vehicle_name',
        // 'vehicle_number',
        // 'manufacturer',
        // 'engine_number',
        // 'chassis_number',
        // 'model',
        // 'average_committed',
        // 'current_reading',
        // 'fuel_type',
        // 'vehicle_type',
        // 'capacity',
        // 'status',
        // 'manufacture_year',
        // 'maintained_by',
        // 'registration_number',
        // 'colony_name',
        // 'state_name',
        // 'region',
        // 'driver_name',
        // 'registration_authority',
        // 'vehicle_notes',
        // 'vehicle_part',
        // 'validity_km',
        // 'validity_years',

        // 'owned_by',
        // 'financed_by',
        // 'total_vehicle_cost',
        // 'actual_vehicle_cost',
        // 'purchase_type',
        // 'purchase_date',
        // 'purchase_cost',
        // 'margin_money',
        // 'loan_amount',
        // 'emi',
        // 'rate_of_interest',
        // 'first_installment_date',
        // 'last_installment_date',
        // 'vendor_name',
        // 'purchase_notes',

        // 'has_insurance',
        // 'insurer_name',
        // 'insurance_company_name',
        // 'insurance_date',
        // 'insurance_expiry_date',
        // 'premium_amount',
        // 'insurance_notes',
    // ];

    // protected $casts = [
        // 'status' => 'boolean',
        // 'has_insurance' => 'boolean',

        // 'average_committed' => 'decimal:2',
        // 'current_reading' => 'decimal:2',
        // 'validity_km' => 'decimal:2',

        // 'total_vehicle_cost' => 'decimal:2',
        // 'actual_vehicle_cost' => 'decimal:2',
        // 'purchase_cost' => 'decimal:2',
        // 'margin_money' => 'decimal:2',
        // 'loan_amount' => 'decimal:2',
        // 'emi' => 'decimal:2',
        // 'rate_of_interest' => 'decimal:2',
        // 'premium_amount' => 'decimal:2',

        // 'purchase_date' => 'date',
        // 'first_installment_date' => 'date',
        // 'last_installment_date' => 'date',
        // 'insurance_date' => 'date',
        // 'insurance_expiry_date' => 'date',
    // ];
// }

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\State;
// use App\Models\Country; // aapke country model ka namespace daal dena
// use App\Models\City;    // aapke city model ka namespace daal dena

class ProtocolVehicleMaster extends Model
{
    use SoftDeletes;

    protected $table = 'protocol_vehicle_masters';

    protected $fillable = [
        'vehicle_name', 'vehicle_number', 'manufacturer', 'engine_number',
        'chassis_number', 'model', 'average_committed', 'current_reading',
        'fuel_type', 'vehicle_type', 'capacity', 'status', 'manufacture_year',
        'maintained_by', 'registration_number', 'country_id', 'state_id',
        'city_id', 'driver_id', 'registration_authority', 'vehicle_notes',
        'vehicle_part', 'validity_km', 'validity_years', 'owned_by',
        'financed_by', 'total_vehicle_cost', 'actual_vehicle_cost',
        'purchase_type', 'purchase_date', 'purchase_cost', 'margin_money',
        'loan_amount', 'emi', 'rate_of_interest', 'first_installment_date',
        'last_installment_date', 'vendor_name', 'purchase_notes',
        'has_insurance', 'insurer_name', 'insurance_company_name',
        'insurance_date', 'insurance_expiry_date', 'premium_amount',
        'insurance_notes',
    ];

    protected $casts = [
        'status'                 => 'boolean',
        'has_insurance'          => 'boolean',
        'purchase_date'          => 'date',
        'first_installment_date' => 'date',
        'last_installment_date'  => 'date',
        'insurance_date'         => 'date',
        'insurance_expiry_date'  => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(ProtocolDriverMaster::class, 'driver_id');
    }

    public function state()
    {
        // State model 'pk' ko primary key use karta hai (controller me bhi wahi hai)
        return $this->belongsTo(State::class, 'state_id', 'pk');
    }

    // country() aur city() bhi isi tarah apne Country/City model ke hisaab se add kar lena
}