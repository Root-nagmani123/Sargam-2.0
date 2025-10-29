<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeerEvent extends Model
{
    protected $table = 'peer_events';
    
    protected $fillable = ['event_name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(PeerCourse::class, 'event_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(PeerGroup::class, 'event_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(PeerColumn::class, 'event_id');
    }

    // Scope for active events
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}