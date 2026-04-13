<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * LBSNAA holidays for Gregorian year 2026 (Saka 1947 / 1948) — Gazetted & Restricted per official list.
     */
    public function run(): void
    {
        Holiday::where('year', 2026)->delete();

        $holidays = [
            // —— GAZETTED HOLIDAYS ——
            [
                'holiday_name' => 'Republic Day',
                'holiday_date' => '2026-01-26',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1947 — Magha 06 — Monday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Holi',
                'holiday_date' => '2026-03-04',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1947 — Phalguna 13 — Wednesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Idu'l Fitr",
                'holiday_date' => '2026-03-21',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1947 — Phalguna 30 — Saturday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Ram Navami',
                'holiday_date' => '2026-03-26',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Chaitra 05 — Thursday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Mahavir Jayanti',
                'holiday_date' => '2026-03-31',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Chaitra 10 — Tuesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Good Friday',
                'holiday_date' => '2026-04-03',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Chaitra 13 — Friday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Buddha Purnima',
                'holiday_date' => '2026-05-01',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Vaisakha 11 — Friday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Id-ul-Zuha (Bakrid)',
                'holiday_date' => '2026-05-27',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Jyaishtha 06 — Wednesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Muharram',
                'holiday_date' => '2026-06-26',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Ashadha 05 — Friday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Independence Day',
                'holiday_date' => '2026-08-15',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Sravana 24 — Saturday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Milad-un-Nabi or Id-e-Milad (Birthday of Prophet Mohammad)',
                'holiday_date' => '2026-08-26',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Bhadra 04 — Wednesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Janmashtami (Vaishnva)',
                'holiday_date' => '2026-09-04',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Bhadra 13 — Friday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Mahatma Gandhi's Birthday",
                'holiday_date' => '2026-10-02',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Ashvina 10 — Friday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Dussehra',
                'holiday_date' => '2026-10-20',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Ashvina 28 — Tuesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Diwali (Deepawali)',
                'holiday_date' => '2026-11-08',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Kartika 17 — Sunday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Guru Nanak's Birthday",
                'holiday_date' => '2026-11-24',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Agrahayana 03 — Tuesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Christmas Day',
                'holiday_date' => '2026-12-25',
                'holiday_type' => 'gazetted',
                'description' => 'Saka 1948 — Pausha 04 — Friday',
                'year' => 2026,
            ],

            // —— RESTRICTED HOLIDAYS ——
            [
                'holiday_name' => "New Year's Day",
                'holiday_date' => '2026-01-01',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Pausha 11 — Thursday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Hazarat Ali's Birthday",
                'holiday_date' => '2026-01-03',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Pausha 13 — Saturday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Makar Sankaranti',
                'holiday_date' => '2026-01-14',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Pausha 24 — Wednesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Magha Bihu / Pongal',
                'holiday_date' => '2026-01-14',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Pausha 24 — Wednesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Sri Panchami / Basant Panchami',
                'holiday_date' => '2026-01-23',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Magha 03 — Friday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Guru Ravidas's Birthday",
                'holiday_date' => '2026-02-01',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Magha 12 — Sunday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Birthday of Swami Dayananda Saraswati',
                'holiday_date' => '2026-02-12',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Magha 23 — Thursday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Maha Shivratri',
                'holiday_date' => '2026-02-15',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Magha 26 — Sunday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Shivaji Jayanti',
                'holiday_date' => '2026-02-19',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Magha 30 — Thursday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Holika Dahan',
                'holiday_date' => '2026-03-03',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Phalguna 12 — Tuesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Dolyatra',
                'holiday_date' => '2026-03-03',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Phalguna 12 — Tuesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Jhanda Aarohan',
                'holiday_date' => '2026-03-08',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Phalguna 17 — Sunday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Chaitra Sukladi / Gudi Padava / Ugadi / Cheti Chand',
                'holiday_date' => '2026-03-19',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Phalguna 28 — Thursday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Jamat-Ul-Vida',
                'holiday_date' => '2026-03-20',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1947 — Phalguna 29 — Friday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Easter Sunday',
                'holiday_date' => '2026-04-05',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Chaitra 15 — Sunday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Vaisakhi / Vishu / Meshadi (Tamil's New Year's Day)",
                'holiday_date' => '2026-04-14',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Chaitra 24 — Tuesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Vaisakhadi (Bengal) / Bahag Bihu (Assam)',
                'holiday_date' => '2026-04-15',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Chaitra 25 — Wednesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Guru Rabindranath's Birthday",
                'holiday_date' => '2026-05-09',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Vaisakha 19 — Saturday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Rath Yatra',
                'holiday_date' => '2026-07-16',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Ashadha 25 — Thursday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Parsi New Year's day / Nauroj",
                'holiday_date' => '2026-08-15',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Sravana 24 — Saturday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Onam or Thiru Onam Day',
                'holiday_date' => '2026-08-26',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Bhadra 04 — Wednesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Raksha Bandhan',
                'holiday_date' => '2026-08-28',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Bhadra 06 — Friday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Ganesh Chaturthi / Vinayaka Chaturthi',
                'holiday_date' => '2026-09-14',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Bhadra 23 — Monday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Dussehra (Saptami)',
                'holiday_date' => '2026-10-18',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Asvina 26 — Sunday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Dussehra (Mahashtami)',
                'holiday_date' => '2026-10-19',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Asvina 27 — Monday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Dussehra (Mahanavami)',
                'holiday_date' => '2026-10-20',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Asvina 28 — Tuesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Maharishi Valmiki's Birthday",
                'holiday_date' => '2026-10-26',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Kartika 04 — Monday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Karaka Chaturthi (Karva Chouth)',
                'holiday_date' => '2026-10-29',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Kartika 07 — Thursday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Naraka Chaturdasi',
                'holiday_date' => '2026-11-08',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Kartika 17 — Sunday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Govardhan Puja',
                'holiday_date' => '2026-11-09',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Kartika 18 — Monday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Bhai Duj',
                'holiday_date' => '2026-11-11',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Kartika 20 — Wednesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Pratihar Sashthi or Surya Sashthi (Chhat Puja)',
                'holiday_date' => '2026-11-15',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Kartika 24 — Sunday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Guru Teg Bahadur's Martyrdom Day",
                'holiday_date' => '2026-11-24',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Agrahayana 03 — Tuesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => "Hazarat Ali's Birthday",
                'holiday_date' => '2026-12-23',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Pausha 02 — Wednesday',
                'year' => 2026,
            ],
            [
                'holiday_name' => 'Christmas Eve',
                'holiday_date' => '2026-12-24',
                'holiday_type' => 'restricted',
                'description' => 'Saka 1948 — Pausha 03 — Thursday',
                'year' => 2026,
            ],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }

        $this->command->info('LBSNAA Holidays for 2026 (Gazetted + Restricted) seeded successfully.');
    }
}
