<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;

class Holiday2025Seeder extends Seeder
{
    public function run()
    {
        $holidays = [
            // Gazetted Holidays 2025
            ['holiday_name' => 'Republic Day', 'holiday_date' => '2025-01-26', 'holiday_type' => 'gazetted', 'description' => 'Republic Day of India', 'year' => 2025],
            ['holiday_name' => 'Holi', 'holiday_date' => '2025-03-14', 'holiday_type' => 'gazetted', 'description' => 'Festival of Colors', 'year' => 2025],
            ['holiday_name' => 'Good Friday', 'holiday_date' => '2025-04-18', 'holiday_type' => 'gazetted', 'description' => 'Good Friday', 'year' => 2025],
            ['holiday_name' => 'Buddha Purnima', 'holiday_date' => '2025-05-12', 'holiday_type' => 'gazetted', 'description' => 'Birth Anniversary of Gautam Buddha', 'year' => 2025],
            ['holiday_name' => 'Eid-ul-Fitr', 'holiday_date' => '2025-03-31', 'holiday_type' => 'gazetted', 'description' => 'Festival of Breaking the Fast', 'year' => 2025],
            ['holiday_name' => 'Independence Day', 'holiday_date' => '2025-08-15', 'holiday_type' => 'gazetted', 'description' => 'Independence Day of India', 'year' => 2025],
            ['holiday_name' => 'Janmashtami', 'holiday_date' => '2025-08-16', 'holiday_type' => 'gazetted', 'description' => 'Birth of Lord Krishna', 'year' => 2025],
            ['holiday_name' => 'Mahatma Gandhi Jayanti', 'holiday_date' => '2025-10-02', 'holiday_type' => 'gazetted', 'description' => 'Birth Anniversary of Mahatma Gandhi', 'year' => 2025],
            ['holiday_name' => 'Dussehra', 'holiday_date' => '2025-10-02', 'holiday_type' => 'gazetted', 'description' => 'Victory of Good over Evil', 'year' => 2025],
            ['holiday_name' => 'Diwali', 'holiday_date' => '2025-10-20', 'holiday_type' => 'gazetted', 'description' => 'Festival of Lights', 'year' => 2025],
            ['holiday_name' => 'Guru Nanak Jayanti', 'holiday_date' => '2025-11-05', 'holiday_type' => 'gazetted', 'description' => 'Birth Anniversary of Guru Nanak Dev', 'year' => 2025],
            ['holiday_name' => 'Christmas', 'holiday_date' => '2025-12-25', 'holiday_type' => 'gazetted', 'description' => 'Christmas Day', 'year' => 2025],
            
            // Add December holidays for immediate visibility
            ['holiday_name' => 'Christmas Eve (Restricted)', 'holiday_date' => '2025-12-24', 'holiday_type' => 'restricted', 'description' => 'Day before Christmas', 'year' => 2025],
            ['holiday_name' => 'New Year Eve (Restricted)', 'holiday_date' => '2025-12-31', 'holiday_type' => 'restricted', 'description' => 'Last day of the year', 'year' => 2025],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }

        $this->command->info('LBSNAA Holidays for 2025 seeded successfully!');
    }
}
