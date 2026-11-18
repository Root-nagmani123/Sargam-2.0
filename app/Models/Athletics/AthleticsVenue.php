<?php

namespace App\Models\Athletics;

use Illuminate\Database\Eloquent\Model;

class AthleticsVenue extends Model
{
    protected $table = 'athletics_venues';

    protected $fillable = ['name', 'venue_type', 'status', 'created_by'];

    /** Keyed like AthleticsEvent::CATEGORIES so a drive row can match venue to event type. */
    public const TYPES = ['track' => 'Track', 'field' => 'Field', 'group' => 'Group Events'];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
