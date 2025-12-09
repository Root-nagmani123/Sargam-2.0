<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * LBSNAA Holidays for 2026 including Gazetted and Restricted Holidays
     */
    public function run(): void
    {
        $holidays = [
            // Gazetted Holidays 2026
            [
                'holiday_name' => 'Republic Day',
                'holiday_date' => '2026-01-26',
                'holiday_type' => 'gazetted',
                'description' => 'Republic Day of India',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Maha Shivaratri',
                'holiday_date' => '2026-03-03',
                'holiday_type' => 'gazetted',
                'description' => 'Maha Shivaratri',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Holi',
                'holiday_date' => '2026-03-15',
                'holiday_type' => 'gazetted',
                'description' => 'Festival of Colors',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Good Friday',
                'holiday_date' => '2026-04-03',
                'holiday_type' => 'gazetted',
                'description' => 'Good Friday',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Ram Navami',
                'holiday_date' => '2026-04-02',
                'holiday_type' => 'gazetted',
                'description' => 'Ram Navami',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Mahavir Jayanti',
                'holiday_date' => '2026-04-09',
                'holiday_type' => 'gazetted',
                'description' => 'Birth anniversary of Lord Mahavir',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Id-ul-Fitr',
                'holiday_date' => '2026-04-21',
                'holiday_type' => 'gazetted',
                'description' => 'Eid-ul-Fitr (subject to sighting of moon)',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Buddha Purnima',
                'holiday_date' => '2026-05-01',
                'holiday_type' => 'gazetted',
                'description' => 'Buddha Purnima',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Id-ul-Zuha (Bakrid)',
                'holiday_date' => '2026-06-28',
                'holiday_type' => 'gazetted',
                'description' => 'Eid-ul-Adha (subject to sighting of moon)',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Muharram',
                'holiday_date' => '2026-07-18',
                'holiday_type' => 'gazetted',
                'description' => 'Muharram',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Independence Day',
                'holiday_date' => '2026-08-15',
                'holiday_type' => 'gazetted',
                'description' => 'Independence Day of India',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Janmashtami',
                'holiday_date' => '2026-08-25',
                'holiday_type' => 'gazetted',
                'description' => 'Birth of Lord Krishna',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Milad-un-Nabi',
                'holiday_date' => '2026-09-27',
                'holiday_type' => 'gazetted',
                'description' => 'Birthday of Prophet Muhammad',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Mahatma Gandhi Birthday',
                'holiday_date' => '2026-10-02',
                'holiday_type' => 'gazetted',
                'description' => 'Gandhi Jayanti',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Dussehra',
                'holiday_date' => '2026-10-13',
                'holiday_type' => 'gazetted',
                'description' => 'Vijaya Dashami',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Diwali',
                'holiday_date' => '2026-11-01',
                'holiday_type' => 'gazetted',
                'description' => 'Festival of Lights',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Guru Nanak Birthday',
                'holiday_date' => '2026-11-20',
                'holiday_type' => 'gazetted',
                'description' => 'Guru Nanak Jayanti',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Christmas',
                'holiday_date' => '2026-12-25',
                'holiday_type' => 'gazetted',
                'description' => 'Christmas Day',
                'year' => 2026
            ],

            // Restricted Holidays 2026
            [
                'holiday_name' => 'Lohri',
                'holiday_date' => '2026-01-13',
                'holiday_type' => 'restricted',
                'description' => 'Punjabi festival marking the end of winter',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Makar Sankranti',
                'holiday_date' => '2026-01-14',
                'holiday_type' => 'restricted',
                'description' => 'Harvest festival',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Basant Panchami',
                'holiday_date' => '2026-02-01',
                'holiday_type' => 'restricted',
                'description' => 'Festival of Saraswati',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Swami Dayananda Saraswati Jayanti',
                'holiday_date' => '2026-02-19',
                'holiday_type' => 'restricted',
                'description' => 'Birth anniversary of Swami Dayananda Saraswati',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Holika Dahan',
                'holiday_date' => '2026-03-14',
                'holiday_type' => 'restricted',
                'description' => 'Day before Holi',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Chaitra Sukhladi / Ugadi / Gudi Padava',
                'holiday_date' => '2026-03-22',
                'holiday_type' => 'restricted',
                'description' => 'New Year for Hindus',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Hazrat Ali Birthday',
                'holiday_date' => '2026-04-13',
                'holiday_type' => 'restricted',
                'description' => 'Birthday of Hazrat Ali',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Mesadi / Vaisakhi',
                'holiday_date' => '2026-04-14',
                'holiday_type' => 'restricted',
                'description' => 'Sikh New Year and harvest festival',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Jamat-Ul-Vida',
                'holiday_date' => '2026-04-17',
                'holiday_type' => 'restricted',
                'description' => 'Last Friday of Ramadan',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Shab-e-Qadr',
                'holiday_date' => '2026-04-18',
                'holiday_type' => 'restricted',
                'description' => 'Night of Power',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Maharana Pratap Jayanti',
                'holiday_date' => '2026-05-13',
                'holiday_type' => 'restricted',
                'description' => 'Birth anniversary of Maharana Pratap',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Rath Yatra',
                'holiday_date' => '2026-06-24',
                'holiday_type' => 'restricted',
                'description' => 'Jagannath Rath Yatra',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Raksha Bandhan',
                'holiday_date' => '2026-08-12',
                'holiday_type' => 'restricted',
                'description' => 'Festival of brother-sister bond',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Nag Panchami',
                'holiday_date' => '2026-08-13',
                'holiday_type' => 'restricted',
                'description' => 'Festival dedicated to snakes',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Parsi New Year',
                'holiday_date' => '2026-08-18',
                'holiday_type' => 'restricted',
                'description' => 'Parsi New Year (Navroz)',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Onam',
                'holiday_date' => '2026-08-30',
                'holiday_type' => 'restricted',
                'description' => 'Kerala harvest festival',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Ganesh Chaturthi',
                'holiday_date' => '2026-09-05',
                'holiday_type' => 'restricted',
                'description' => 'Birth of Lord Ganesha',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Mahalaya / Pitru Paksha Amavasya',
                'holiday_date' => '2026-09-27',
                'holiday_type' => 'restricted',
                'description' => 'Ancestor worship day',
                'year' => 2026
            ],
            [
                'holiday_name' => 'First Day of Durga Puja',
                'holiday_date' => '2026-10-08',
                'holiday_type' => 'restricted',
                'description' => 'Maha Saptami',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Maha Ashtami',
                'holiday_date' => '2026-10-09',
                'holiday_type' => 'restricted',
                'description' => 'Eighth day of Durga Puja',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Maha Navami',
                'holiday_date' => '2026-10-10',
                'holiday_type' => 'restricted',
                'description' => 'Ninth day of Durga Puja',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Maharishi Valmiki Birthday',
                'holiday_date' => '2026-10-13',
                'holiday_type' => 'restricted',
                'description' => 'Birth anniversary of Maharishi Valmiki',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Karva Chauth',
                'holiday_date' => '2026-10-21',
                'holiday_type' => 'restricted',
                'description' => 'Festival for married women',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Naraka Chaturdasi',
                'holiday_date' => '2026-10-31',
                'holiday_type' => 'restricted',
                'description' => 'Day before Diwali',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Govardhan Puja',
                'holiday_date' => '2026-11-02',
                'holiday_type' => 'restricted',
                'description' => 'Day after Diwali',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Bhai Dooj',
                'holiday_date' => '2026-11-03',
                'holiday_type' => 'restricted',
                'description' => 'Festival of brother-sister',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Chhath Puja',
                'holiday_date' => '2026-11-06',
                'holiday_type' => 'restricted',
                'description' => 'Worship of Sun God',
                'year' => 2026
            ],
            [
                'holiday_name' => 'Guru Teg Bahadur Martyrdom Day',
                'holiday_date' => '2026-12-01',
                'holiday_type' => 'restricted',
                'description' => 'Martyrdom day of Guru Teg Bahadur',
                'year' => 2026
            ]
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }

        $this->command->info('LBSNAA Holidays for 2026 seeded successfully!');
    }
}
