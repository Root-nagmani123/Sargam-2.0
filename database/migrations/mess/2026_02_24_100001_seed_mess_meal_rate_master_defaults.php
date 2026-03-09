<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed default meal rates: Govrt 300, OT 150, Faculty 150, Alumni 160
     * for each meal type (Breakfast, Lunch, Dinner).
     *
     * @return void
     */
    public function up()
    {
        $defaults = [
            'govrt' => 300,
            'ot' => 150,
            'faculty' => 150,
            'alumni' => 160,
        ];
        $mealTypes = ['breakfast', 'lunch', 'dinner'];

        $rows = [];
        foreach ($mealTypes as $meal) {
            foreach ($defaults as $category => $rate) {
                $rows[] = [
                    'meal_type' => $meal,
                    'category_type' => $category,
                    'rate' => $rate,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (count($rows) > 0 && DB::table('mess_meal_rate_master')->count() === 0) {
            DB::table('mess_meal_rate_master')->insert($rows);
        }
    }

    /**
     * Reverse: do not remove data (user may have edited rates).
     *
     * @return void
     */
    public function down()
    {
        // No-op: seed data is not reverted to avoid data loss
    }
};
