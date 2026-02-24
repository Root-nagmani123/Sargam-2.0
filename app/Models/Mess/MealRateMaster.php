<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class MealRateMaster extends Model
{
    public const MEAL_BREAKFAST = 'breakfast';
    public const MEAL_LUNCH = 'lunch';
    public const MEAL_DINNER = 'dinner';

    public const CATEGORY_GOVRT = 'govrt';
    public const CATEGORY_OT = 'ot';
    public const CATEGORY_FACULTY = 'faculty';
    public const CATEGORY_ALUMNI = 'alumni';

    protected $table = 'mess_meal_rate_master';

    protected $fillable = [
        'meal_type',
        'category_type',
        'rate',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * @return array<string, string>
     */
    public static function mealTypes(): array
    {
        return [
            self::MEAL_BREAKFAST => 'Breakfast',
            self::MEAL_LUNCH => 'Lunch',
            self::MEAL_DINNER => 'Dinner',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function categoryTypes(): array
    {
        return [
            self::CATEGORY_GOVRT => 'Govrt',
            self::CATEGORY_OT => 'OT',
            self::CATEGORY_FACULTY => 'Faculty',
            self::CATEGORY_ALUMNI => 'Alumni',
        ];
    }

    /**
     * Default rate per category (used for seeding).
     */
    public static function defaultRates(): array
    {
        return [
            self::CATEGORY_GOVRT => 300,
            self::CATEGORY_OT => 150,
            self::CATEGORY_FACULTY => 150,
            self::CATEGORY_ALUMNI => 160,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getMealTypeLabelAttribute(): string
    {
        return self::mealTypes()[$this->meal_type] ?? $this->meal_type;
    }

    public function getCategoryTypeLabelAttribute(): string
    {
        return self::categoryTypes()[$this->category_type] ?? $this->category_type;
    }
}
