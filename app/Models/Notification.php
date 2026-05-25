<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'pk';
    public $timestamps = false; // We use created_at manually
    
    protected $fillable = [
        'sender_user_id',
        'receiver_user_id',
        'type',
        'module_name',
        'reference_pk',
        'title',
        'message',
        'is_read',
        'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Birthday wish notifications received by an employee today.
     */
    public function scopeBirthdayWishesReceivedToday($query, int $receiverUserId)
    {
        return $query
            ->where('receiver_user_id', $receiverUserId)
            ->where('type', 'birthday')
            ->where(function ($q) {
                $q->where('module_name', 'BirthdayWish')
                    ->orWhereNull('module_name');
            })
            ->whereDate('created_at', today());
    }

    /**
     * Get the sender user
     * Note: Assumes user_credentials table has a user_id field that matches sender_user_id
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id', 'user_id');
    }

    /**
     * Get the receiver user
     * Note: Assumes user_credentials table has a user_id field that matches receiver_user_id
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_user_id', 'user_id');
    }
}

