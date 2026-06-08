<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * LBSNAA / Government of India Holidays for 2025, 2026, 2027
     * Gazetted + Restricted holidays as per GoI annual notification
     */
    public function run(): void
    {
        $years = [2025, 2026, 2027];

        foreach ($years as $year) {
            Holiday::where('year', $year)->delete();
        }

        $holidays = array_merge(
            $this->getHolidays2025(),
            $this->getHolidays2026(),
            $this->getHolidays2027()
        );

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }

        $this->command->info('LBSNAA Holidays for 2025, 2026, 2027 seeded successfully!');
    }

    /**
     * Government of India Holidays 2025
     */
    private function getHolidays2025(): array
    {
        return [
            // ===== GAZETTED HOLIDAYS 2025 =====
            ['holiday_name' => 'Republic Day', 'holiday_date' => '2025-01-26', 'holiday_type' => 'gazetted', 'description' => 'Republic Day', 'year' => 2025],
            ['holiday_name' => 'Maha Shivaratri', 'holiday_date' => '2025-02-26', 'holiday_type' => 'gazetted', 'description' => 'Maha Shivaratri', 'year' => 2025],
            ['holiday_name' => 'Holi', 'holiday_date' => '2025-03-14', 'holiday_type' => 'gazetted', 'description' => 'Holi', 'year' => 2025],
            ['holiday_name' => 'Id-ul-Fitr', 'holiday_date' => '2025-03-31', 'holiday_type' => 'gazetted', 'description' => 'Id-ul-Fitr', 'year' => 2025],
            ['holiday_name' => 'Ram Navami', 'holiday_date' => '2025-04-06', 'holiday_type' => 'gazetted', 'description' => 'Ram Navami', 'year' => 2025],
            ['holiday_name' => 'Mahavir Jayanti', 'holiday_date' => '2025-04-10', 'holiday_type' => 'gazetted', 'description' => 'Mahavir Jayanti', 'year' => 2025],
            ['holiday_name' => 'Good Friday', 'holiday_date' => '2025-04-18', 'holiday_type' => 'gazetted', 'description' => 'Good Friday', 'year' => 2025],
            ['holiday_name' => 'Buddha Purnima', 'holiday_date' => '2025-05-12', 'holiday_type' => 'gazetted', 'description' => 'Buddha Purnima', 'year' => 2025],
            ['holiday_name' => 'Id-ul-Zuha (Bakrid)', 'holiday_date' => '2025-06-07', 'holiday_type' => 'gazetted', 'description' => 'Id-ul-Zuha (Bakrid)', 'year' => 2025],
            ['holiday_name' => 'Muharram', 'holiday_date' => '2025-07-06', 'holiday_type' => 'gazetted', 'description' => 'Muharram', 'year' => 2025],
            ['holiday_name' => 'Independence Day', 'holiday_date' => '2025-08-15', 'holiday_type' => 'gazetted', 'description' => 'Independence Day', 'year' => 2025],
            ['holiday_name' => 'Janmashtami (Vaishnava)', 'holiday_date' => '2025-08-16', 'holiday_type' => 'gazetted', 'description' => 'Janmashtami', 'year' => 2025],
            ['holiday_name' => 'Milad-un-Nabi or Id-e-Milad', 'holiday_date' => '2025-09-05', 'holiday_type' => 'gazetted', 'description' => 'Birthday of Prophet Mohammad', 'year' => 2025],
            ['holiday_name' => "Mahatma Gandhi's Birthday", 'holiday_date' => '2025-10-02', 'holiday_type' => 'gazetted', 'description' => "Mahatma Gandhi's Birthday", 'year' => 2025],
            ['holiday_name' => 'Dussehra', 'holiday_date' => '2025-10-02', 'holiday_type' => 'gazetted', 'description' => 'Dussehra (Vijaya Dashami)', 'year' => 2025],
            ['holiday_name' => 'Diwali (Deepawali)', 'holiday_date' => '2025-10-20', 'holiday_type' => 'gazetted', 'description' => 'Diwali', 'year' => 2025],
            ['holiday_name' => "Guru Nanak's Birthday", 'holiday_date' => '2025-11-05', 'holiday_type' => 'gazetted', 'description' => "Guru Nanak's Birthday", 'year' => 2025],
            ['holiday_name' => 'Christmas Day', 'holiday_date' => '2025-12-25', 'holiday_type' => 'gazetted', 'description' => 'Christmas Day', 'year' => 2025],

            // ===== RESTRICTED HOLIDAYS 2025 =====
            ['holiday_name' => "New Year's Day", 'holiday_date' => '2025-01-01', 'holiday_type' => 'restricted', 'description' => "New Year's Day", 'year' => 2025],
            ['holiday_name' => "Guru Gobind Singh's Birthday", 'holiday_date' => '2025-01-06', 'holiday_type' => 'restricted', 'description' => "Guru Gobind Singh's Birthday", 'year' => 2025],
            ['holiday_name' => 'Makar Sankranti / Pongal', 'holiday_date' => '2025-01-14', 'holiday_type' => 'restricted', 'description' => 'Makar Sankranti / Pongal', 'year' => 2025],
            ['holiday_name' => 'Sri Panchami / Basant Panchami', 'holiday_date' => '2025-02-02', 'holiday_type' => 'restricted', 'description' => 'Sri Panchami / Basant Panchami', 'year' => 2025],
            ['holiday_name' => "Guru Ravidas's Birthday", 'holiday_date' => '2025-02-12', 'holiday_type' => 'restricted', 'description' => "Guru Ravidas's Birthday", 'year' => 2025],
            ['holiday_name' => 'Shivaji Jayanti', 'holiday_date' => '2025-02-19', 'holiday_type' => 'restricted', 'description' => 'Shivaji Jayanti', 'year' => 2025],
            ['holiday_name' => 'Swami Dayananda Saraswati Jayanti', 'holiday_date' => '2025-03-02', 'holiday_type' => 'restricted', 'description' => 'Birthday of Swami Dayananda Saraswati', 'year' => 2025],
            ['holiday_name' => 'Holika Dahan', 'holiday_date' => '2025-03-13', 'holiday_type' => 'restricted', 'description' => 'Holika Dahan', 'year' => 2025],
            ['holiday_name' => 'Dolyatra', 'holiday_date' => '2025-03-13', 'holiday_type' => 'restricted', 'description' => 'Dolyatra', 'year' => 2025],
            ['holiday_name' => 'Chaitra Sukladi / Gudi Padava / Ugadi / Cheti Chand', 'holiday_date' => '2025-03-30', 'holiday_type' => 'restricted', 'description' => 'Chaitra Sukladi / Gudi Padava / Ugadi / Cheti Chand', 'year' => 2025],
            ['holiday_name' => "Vaisakhi / Vishu / Meshadi", 'holiday_date' => '2025-04-14', 'holiday_type' => 'restricted', 'description' => "Vaisakhi / Vishu / Meshadi (Tamil New Year's Day)", 'year' => 2025],
            ['holiday_name' => 'Vaisakhadi (Bengal) / Bahag Bihu (Assam)', 'holiday_date' => '2025-04-15', 'holiday_type' => 'restricted', 'description' => 'Vaisakhadi (Bengal) / Bahag Bihu (Assam)', 'year' => 2025],
            ['holiday_name' => 'Easter Sunday', 'holiday_date' => '2025-04-20', 'holiday_type' => 'restricted', 'description' => 'Easter Sunday', 'year' => 2025],
            ['holiday_name' => "Guru Rabindranath's Birthday", 'holiday_date' => '2025-05-09', 'holiday_type' => 'restricted', 'description' => "Guru Rabindranath's Birthday", 'year' => 2025],
            ['holiday_name' => 'Jamat-Ul-Vida', 'holiday_date' => '2025-03-28', 'holiday_type' => 'restricted', 'description' => 'Jamat-Ul-Vida', 'year' => 2025],
            ['holiday_name' => 'Rath Yatra', 'holiday_date' => '2025-06-27', 'holiday_type' => 'restricted', 'description' => 'Rath Yatra', 'year' => 2025],
            ['holiday_name' => "Parsi New Year's Day / Nauroj", 'holiday_date' => '2025-08-16', 'holiday_type' => 'restricted', 'description' => "Parsi New Year's Day / Nauroj", 'year' => 2025],
            ['holiday_name' => 'Raksha Bandhan', 'holiday_date' => '2025-08-09', 'holiday_type' => 'restricted', 'description' => 'Raksha Bandhan', 'year' => 2025],
            ['holiday_name' => 'Onam or Thiru Onam Day', 'holiday_date' => '2025-09-05', 'holiday_type' => 'restricted', 'description' => 'Onam or Thiru Onam Day', 'year' => 2025],
            ['holiday_name' => 'Ganesh Chaturthi / Vinayaka Chaturthi', 'holiday_date' => '2025-08-27', 'holiday_type' => 'restricted', 'description' => 'Ganesh Chaturthi / Vinayaka Chaturthi', 'year' => 2025],
            ['holiday_name' => 'Dussehra (Saptami)', 'holiday_date' => '2025-09-30', 'holiday_type' => 'restricted', 'description' => 'Dussehra (Saptami)', 'year' => 2025],
            ['holiday_name' => 'Dussehra (Mahashtami)', 'holiday_date' => '2025-10-01', 'holiday_type' => 'restricted', 'description' => 'Dussehra (Mahashtami)', 'year' => 2025],
            ['holiday_name' => "Maharishi Valmiki's Birthday", 'holiday_date' => '2025-10-07', 'holiday_type' => 'restricted', 'description' => "Maharishi Valmiki's Birthday", 'year' => 2025],
            ['holiday_name' => 'Karaka Chaturthi (Karva Chouth)', 'holiday_date' => '2025-10-10', 'holiday_type' => 'restricted', 'description' => 'Karaka Chaturthi (Karva Chouth)', 'year' => 2025],
            ['holiday_name' => 'Naraka Chaturdasi', 'holiday_date' => '2025-10-19', 'holiday_type' => 'restricted', 'description' => 'Naraka Chaturdasi', 'year' => 2025],
            ['holiday_name' => 'Govardhan Puja', 'holiday_date' => '2025-10-21', 'holiday_type' => 'restricted', 'description' => 'Govardhan Puja', 'year' => 2025],
            ['holiday_name' => 'Bhai Duj', 'holiday_date' => '2025-10-23', 'holiday_type' => 'restricted', 'description' => 'Bhai Duj', 'year' => 2025],
            ['holiday_name' => 'Prathar Sasthi or Surya Sasthi (Chhat Puja)', 'holiday_date' => '2025-10-26', 'holiday_type' => 'restricted', 'description' => 'Prathar Sasthi or Surya Sasthi (Chhat Puja)', 'year' => 2025],
            ['holiday_name' => "Guru Teg Bahadur's Martyrdom Day", 'holiday_date' => '2025-11-24', 'holiday_type' => 'restricted', 'description' => "Guru Teg Bahadur's Martyrdom Day", 'year' => 2025],
            ['holiday_name' => 'Christmas Eve', 'holiday_date' => '2025-12-24', 'holiday_type' => 'restricted', 'description' => 'Christmas Eve', 'year' => 2025],
        ];
    }

    /**
     * Government of India Holidays 2026
     */
    private function getHolidays2026(): array
    {
        return [
            // ===== GAZETTED HOLIDAYS 2026 =====
            ['holiday_name' => 'Republic Day', 'holiday_date' => '2026-01-26', 'holiday_type' => 'gazetted', 'description' => 'Republic Day', 'year' => 2026],
            ['holiday_name' => 'Holi', 'holiday_date' => '2026-03-04', 'holiday_type' => 'gazetted', 'description' => 'Holi', 'year' => 2026],
            ['holiday_name' => 'Id-ul-Fitr', 'holiday_date' => '2026-03-21', 'holiday_type' => 'gazetted', 'description' => 'Id-ul-Fitr', 'year' => 2026],
            ['holiday_name' => 'Ram Navami', 'holiday_date' => '2026-03-26', 'holiday_type' => 'gazetted', 'description' => 'Ram Navami', 'year' => 2026],
            ['holiday_name' => 'Mahavir Jayanti', 'holiday_date' => '2026-03-31', 'holiday_type' => 'gazetted', 'description' => 'Mahavir Jayanti', 'year' => 2026],
            ['holiday_name' => 'Good Friday', 'holiday_date' => '2026-04-03', 'holiday_type' => 'gazetted', 'description' => 'Good Friday', 'year' => 2026],
            ['holiday_name' => 'Buddha Purnima', 'holiday_date' => '2026-05-01', 'holiday_type' => 'gazetted', 'description' => 'Buddha Purnima', 'year' => 2026],
            ['holiday_name' => 'Id-ul-Zuha (Bakrid)', 'holiday_date' => '2026-05-27', 'holiday_type' => 'gazetted', 'description' => 'Id-ul-Zuha (Bakrid)', 'year' => 2026],
            ['holiday_name' => 'Muharram', 'holiday_date' => '2026-06-26', 'holiday_type' => 'gazetted', 'description' => 'Muharram', 'year' => 2026],
            ['holiday_name' => 'Independence Day', 'holiday_date' => '2026-08-15', 'holiday_type' => 'gazetted', 'description' => 'Independence Day', 'year' => 2026],
            ['holiday_name' => 'Milad-un-Nabi or Id-e-Milad', 'holiday_date' => '2026-08-26', 'holiday_type' => 'gazetted', 'description' => 'Birthday of Prophet Mohammad', 'year' => 2026],
            ['holiday_name' => 'Janmashtami (Vaishnava)', 'holiday_date' => '2026-09-04', 'holiday_type' => 'gazetted', 'description' => 'Janmashtami', 'year' => 2026],
            ['holiday_name' => "Mahatma Gandhi's Birthday", 'holiday_date' => '2026-10-02', 'holiday_type' => 'gazetted', 'description' => "Mahatma Gandhi's Birthday", 'year' => 2026],
            ['holiday_name' => 'Dussehra', 'holiday_date' => '2026-10-20', 'holiday_type' => 'gazetted', 'description' => 'Dussehra', 'year' => 2026],
            ['holiday_name' => 'Diwali (Deepawali)', 'holiday_date' => '2026-11-08', 'holiday_type' => 'gazetted', 'description' => 'Diwali', 'year' => 2026],
            ['holiday_name' => "Guru Nanak's Birthday", 'holiday_date' => '2026-11-24', 'holiday_type' => 'gazetted', 'description' => "Guru Nanak's Birthday", 'year' => 2026],
            ['holiday_name' => 'Christmas Day', 'holiday_date' => '2026-12-25', 'holiday_type' => 'gazetted', 'description' => 'Christmas Day', 'year' => 2026],

            // ===== RESTRICTED HOLIDAYS 2026 =====
            ['holiday_name' => "New Year's Day", 'holiday_date' => '2026-01-01', 'holiday_type' => 'restricted', 'description' => "New Year's Day", 'year' => 2026],
            ['holiday_name' => "Hazarat Ali's Birthday", 'holiday_date' => '2026-01-03', 'holiday_type' => 'restricted', 'description' => "Hazarat Ali's Birthday", 'year' => 2026],
            ['holiday_name' => 'Makar Sankranti', 'holiday_date' => '2026-01-14', 'holiday_type' => 'restricted', 'description' => 'Makar Sankranti', 'year' => 2026],
            ['holiday_name' => 'Magha Bihu / Pongal', 'holiday_date' => '2026-01-14', 'holiday_type' => 'restricted', 'description' => 'Magha Bihu / Pongal', 'year' => 2026],
            ['holiday_name' => 'Sri Panchami / Basant Panchami', 'holiday_date' => '2026-01-23', 'holiday_type' => 'restricted', 'description' => 'Sri Panchami / Basant Panchami', 'year' => 2026],
            ['holiday_name' => "Guru Ravidas's Birthday", 'holiday_date' => '2026-02-01', 'holiday_type' => 'restricted', 'description' => "Guru Ravidas's Birthday", 'year' => 2026],
            ['holiday_name' => 'Birthday of Swami Dayananda Saraswati', 'holiday_date' => '2026-02-12', 'holiday_type' => 'restricted', 'description' => 'Birthday of Swami Dayananda Saraswati', 'year' => 2026],
            ['holiday_name' => 'Maha Shivaratri', 'holiday_date' => '2026-02-15', 'holiday_type' => 'restricted', 'description' => 'Maha Shivaratri', 'year' => 2026],
            ['holiday_name' => 'Shivaji Jayanti', 'holiday_date' => '2026-02-19', 'holiday_type' => 'restricted', 'description' => 'Shivaji Jayanti', 'year' => 2026],
            ['holiday_name' => 'Holika Dahan', 'holiday_date' => '2026-03-03', 'holiday_type' => 'restricted', 'description' => 'Holika Dahan', 'year' => 2026],
            ['holiday_name' => 'Dolyatra', 'holiday_date' => '2026-03-03', 'holiday_type' => 'restricted', 'description' => 'Dolyatra', 'year' => 2026],
            ['holiday_name' => 'Jhanda Aarohan', 'holiday_date' => '2026-03-08', 'holiday_type' => 'restricted', 'description' => 'Jhanda Aarohan', 'year' => 2026],
            ['holiday_name' => 'Chaitra Sukladi / Gudi Padava / Ugadi / Cheti Chand', 'holiday_date' => '2026-03-19', 'holiday_type' => 'restricted', 'description' => 'Chaitra Sukladi / Gudi Padava / Ugadi / Cheti Chand', 'year' => 2026],
            ['holiday_name' => 'Jamat-Ul-Vida', 'holiday_date' => '2026-03-20', 'holiday_type' => 'restricted', 'description' => 'Jamat-Ul-Vida', 'year' => 2026],
            ['holiday_name' => 'Easter Sunday', 'holiday_date' => '2026-04-05', 'holiday_type' => 'restricted', 'description' => 'Easter Sunday', 'year' => 2026],
            ['holiday_name' => "Vaisakhi / Vishu / Meshadi (Tamil's New Year's Day)", 'holiday_date' => '2026-04-14', 'holiday_type' => 'restricted', 'description' => "Vaisakhi / Vishu / Meshadi (Tamil's New Year's Day)", 'year' => 2026],
            ['holiday_name' => 'Vaisakhadi (Bengal) / Bahag Bihu (Assam)', 'holiday_date' => '2026-04-15', 'holiday_type' => 'restricted', 'description' => 'Vaisakhadi (Bengal) / Bahag Bihu (Assam)', 'year' => 2026],
            ['holiday_name' => "Guru Rabindranath's Birthday", 'holiday_date' => '2026-05-09', 'holiday_type' => 'restricted', 'description' => "Guru Rabindranath's Birthday", 'year' => 2026],
            ['holiday_name' => 'Rath Yatra', 'holiday_date' => '2026-07-16', 'holiday_type' => 'restricted', 'description' => 'Rath Yatra', 'year' => 2026],
            ['holiday_name' => "Parsi New Year's Day / Nauroj", 'holiday_date' => '2026-08-15', 'holiday_type' => 'restricted', 'description' => "Parsi New Year's Day / Nauroj", 'year' => 2026],
            ['holiday_name' => 'Onam or Thiru Onam Day', 'holiday_date' => '2026-08-26', 'holiday_type' => 'restricted', 'description' => 'Onam or Thiru Onam Day', 'year' => 2026],
            ['holiday_name' => 'Raksha Bandhan', 'holiday_date' => '2026-08-28', 'holiday_type' => 'restricted', 'description' => 'Raksha Bandhan', 'year' => 2026],
            ['holiday_name' => 'Ganesh Chaturthi / Vinayaka Chaturthi', 'holiday_date' => '2026-09-14', 'holiday_type' => 'restricted', 'description' => 'Ganesh Chaturthi / Vinayaka Chaturthi', 'year' => 2026],
            ['holiday_name' => 'Dussehra (Saptami)', 'holiday_date' => '2026-10-18', 'holiday_type' => 'restricted', 'description' => 'Dussehra (Saptami)', 'year' => 2026],
            ['holiday_name' => 'Dussehra (Mahashtami)', 'holiday_date' => '2026-10-19', 'holiday_type' => 'restricted', 'description' => 'Dussehra (Mahashtami)', 'year' => 2026],
            ['holiday_name' => 'Dussehra (Mahanavami)', 'holiday_date' => '2026-10-20', 'holiday_type' => 'restricted', 'description' => 'Dussehra (Mahanavami)', 'year' => 2026],
            ['holiday_name' => "Maharishi Valmiki's Birthday", 'holiday_date' => '2026-10-26', 'holiday_type' => 'restricted', 'description' => "Maharishi Valmiki's Birthday", 'year' => 2026],
            ['holiday_name' => 'Karaka Chaturthi (Karva Chouth)', 'holiday_date' => '2026-10-29', 'holiday_type' => 'restricted', 'description' => 'Karaka Chaturthi (Karva Chouth)', 'year' => 2026],
            ['holiday_name' => 'Naraka Chaturdasi', 'holiday_date' => '2026-11-08', 'holiday_type' => 'restricted', 'description' => 'Naraka Chaturdasi', 'year' => 2026],
            ['holiday_name' => 'Govardhan Puja', 'holiday_date' => '2026-11-09', 'holiday_type' => 'restricted', 'description' => 'Govardhan Puja', 'year' => 2026],
            ['holiday_name' => 'Bhai Duj', 'holiday_date' => '2026-11-11', 'holiday_type' => 'restricted', 'description' => 'Bhai Duj', 'year' => 2026],
            ['holiday_name' => 'Prathar Sasthi or Surya Sasthi (Chhat Puja)', 'holiday_date' => '2026-11-15', 'holiday_type' => 'restricted', 'description' => 'Prathar Sasthi or Surya Sasthi (Chhat Puja)', 'year' => 2026],
            ['holiday_name' => "Guru Teg Bahadur's Martyrdom Day", 'holiday_date' => '2026-11-24', 'holiday_type' => 'restricted', 'description' => "Guru Teg Bahadur's Martyrdom Day", 'year' => 2026],
            ['holiday_name' => "Hazarat Ali's Birthday", 'holiday_date' => '2026-12-23', 'holiday_type' => 'restricted', 'description' => "Hazarat Ali's Birthday", 'year' => 2026],
            ['holiday_name' => 'Christmas Eve', 'holiday_date' => '2026-12-24', 'holiday_type' => 'restricted', 'description' => 'Christmas Eve', 'year' => 2026],
        ];
    }

    /**
     * Government of India Holidays 2027 (Tentative - based on lunar calendar projections)
     * Note: Islamic holidays may shift by 1-2 days based on moon sighting
     */
    private function getHolidays2027(): array
    {
        return [
            // ===== GAZETTED HOLIDAYS 2027 =====
            ['holiday_name' => 'Republic Day', 'holiday_date' => '2027-01-26', 'holiday_type' => 'gazetted', 'description' => 'Republic Day', 'year' => 2027],
            ['holiday_name' => 'Holi', 'holiday_date' => '2027-03-22', 'holiday_type' => 'gazetted', 'description' => 'Holi', 'year' => 2027],
            ['holiday_name' => 'Id-ul-Fitr', 'holiday_date' => '2027-03-10', 'holiday_type' => 'gazetted', 'description' => 'Id-ul-Fitr', 'year' => 2027],
            ['holiday_name' => 'Ram Navami', 'holiday_date' => '2027-04-15', 'holiday_type' => 'gazetted', 'description' => 'Ram Navami', 'year' => 2027],
            ['holiday_name' => 'Mahavir Jayanti', 'holiday_date' => '2027-04-19', 'holiday_type' => 'gazetted', 'description' => 'Mahavir Jayanti', 'year' => 2027],
            ['holiday_name' => 'Good Friday', 'holiday_date' => '2027-03-26', 'holiday_type' => 'gazetted', 'description' => 'Good Friday', 'year' => 2027],
            ['holiday_name' => 'Buddha Purnima', 'holiday_date' => '2027-05-20', 'holiday_type' => 'gazetted', 'description' => 'Buddha Purnima', 'year' => 2027],
            ['holiday_name' => 'Id-ul-Zuha (Bakrid)', 'holiday_date' => '2027-05-17', 'holiday_type' => 'gazetted', 'description' => 'Id-ul-Zuha (Bakrid)', 'year' => 2027],
            ['holiday_name' => 'Muharram', 'holiday_date' => '2027-06-16', 'holiday_type' => 'gazetted', 'description' => 'Muharram', 'year' => 2027],
            ['holiday_name' => 'Independence Day', 'holiday_date' => '2027-08-15', 'holiday_type' => 'gazetted', 'description' => 'Independence Day', 'year' => 2027],
            ['holiday_name' => 'Milad-un-Nabi or Id-e-Milad', 'holiday_date' => '2027-08-15', 'holiday_type' => 'gazetted', 'description' => 'Birthday of Prophet Mohammad', 'year' => 2027],
            ['holiday_name' => 'Janmashtami (Vaishnava)', 'holiday_date' => '2027-08-25', 'holiday_type' => 'gazetted', 'description' => 'Janmashtami', 'year' => 2027],
            ['holiday_name' => "Mahatma Gandhi's Birthday", 'holiday_date' => '2027-10-02', 'holiday_type' => 'gazetted', 'description' => "Mahatma Gandhi's Birthday", 'year' => 2027],
            ['holiday_name' => 'Dussehra', 'holiday_date' => '2027-10-09', 'holiday_type' => 'gazetted', 'description' => 'Dussehra (Vijaya Dashami)', 'year' => 2027],
            ['holiday_name' => 'Diwali (Deepawali)', 'holiday_date' => '2027-10-29', 'holiday_type' => 'gazetted', 'description' => 'Diwali', 'year' => 2027],
            ['holiday_name' => "Guru Nanak's Birthday", 'holiday_date' => '2027-11-14', 'holiday_type' => 'gazetted', 'description' => "Guru Nanak's Birthday", 'year' => 2027],
            ['holiday_name' => 'Christmas Day', 'holiday_date' => '2027-12-25', 'holiday_type' => 'gazetted', 'description' => 'Christmas Day', 'year' => 2027],

            // ===== RESTRICTED HOLIDAYS 2027 =====
            ['holiday_name' => "New Year's Day", 'holiday_date' => '2027-01-01', 'holiday_type' => 'restricted', 'description' => "New Year's Day", 'year' => 2027],
            ['holiday_name' => 'Makar Sankranti / Pongal', 'holiday_date' => '2027-01-14', 'holiday_type' => 'restricted', 'description' => 'Makar Sankranti / Pongal', 'year' => 2027],
            ['holiday_name' => 'Sri Panchami / Basant Panchami', 'holiday_date' => '2027-02-11', 'holiday_type' => 'restricted', 'description' => 'Sri Panchami / Basant Panchami', 'year' => 2027],
            ['holiday_name' => "Guru Ravidas's Birthday", 'holiday_date' => '2027-02-21', 'holiday_type' => 'restricted', 'description' => "Guru Ravidas's Birthday", 'year' => 2027],
            ['holiday_name' => 'Shivaji Jayanti', 'holiday_date' => '2027-02-19', 'holiday_type' => 'restricted', 'description' => 'Shivaji Jayanti', 'year' => 2027],
            ['holiday_name' => 'Maha Shivaratri', 'holiday_date' => '2027-03-06', 'holiday_type' => 'restricted', 'description' => 'Maha Shivaratri', 'year' => 2027],
            ['holiday_name' => 'Jamat-Ul-Vida', 'holiday_date' => '2027-03-09', 'holiday_type' => 'restricted', 'description' => 'Jamat-Ul-Vida', 'year' => 2027],
            ['holiday_name' => 'Holika Dahan', 'holiday_date' => '2027-03-21', 'holiday_type' => 'restricted', 'description' => 'Holika Dahan', 'year' => 2027],
            ['holiday_name' => 'Dolyatra', 'holiday_date' => '2027-03-21', 'holiday_type' => 'restricted', 'description' => 'Dolyatra', 'year' => 2027],
            ['holiday_name' => 'Easter Sunday', 'holiday_date' => '2027-03-28', 'holiday_type' => 'restricted', 'description' => 'Easter Sunday', 'year' => 2027],
            ['holiday_name' => 'Chaitra Sukladi / Gudi Padava / Ugadi / Cheti Chand', 'holiday_date' => '2027-04-08', 'holiday_type' => 'restricted', 'description' => 'Chaitra Sukladi / Gudi Padava / Ugadi / Cheti Chand', 'year' => 2027],
            ['holiday_name' => "Vaisakhi / Vishu / Meshadi (Tamil's New Year's Day)", 'holiday_date' => '2027-04-14', 'holiday_type' => 'restricted', 'description' => "Vaisakhi / Vishu / Meshadi (Tamil's New Year's Day)", 'year' => 2027],
            ['holiday_name' => 'Vaisakhadi (Bengal) / Bahag Bihu (Assam)', 'holiday_date' => '2027-04-15', 'holiday_type' => 'restricted', 'description' => 'Vaisakhadi (Bengal) / Bahag Bihu (Assam)', 'year' => 2027],
            ['holiday_name' => "Guru Rabindranath's Birthday", 'holiday_date' => '2027-05-09', 'holiday_type' => 'restricted', 'description' => "Guru Rabindranath's Birthday", 'year' => 2027],
            ['holiday_name' => 'Rath Yatra', 'holiday_date' => '2027-07-05', 'holiday_type' => 'restricted', 'description' => 'Rath Yatra', 'year' => 2027],
            ['holiday_name' => "Parsi New Year's Day / Nauroj", 'holiday_date' => '2027-08-16', 'holiday_type' => 'restricted', 'description' => "Parsi New Year's Day / Nauroj", 'year' => 2027],
            ['holiday_name' => 'Raksha Bandhan', 'holiday_date' => '2027-08-17', 'holiday_type' => 'restricted', 'description' => 'Raksha Bandhan', 'year' => 2027],
            ['holiday_name' => 'Onam or Thiru Onam Day', 'holiday_date' => '2027-09-14', 'holiday_type' => 'restricted', 'description' => 'Onam or Thiru Onam Day', 'year' => 2027],
            ['holiday_name' => 'Ganesh Chaturthi / Vinayaka Chaturthi', 'holiday_date' => '2027-09-03', 'holiday_type' => 'restricted', 'description' => 'Ganesh Chaturthi / Vinayaka Chaturthi', 'year' => 2027],
            ['holiday_name' => 'Dussehra (Saptami)', 'holiday_date' => '2027-10-07', 'holiday_type' => 'restricted', 'description' => 'Dussehra (Saptami)', 'year' => 2027],
            ['holiday_name' => 'Dussehra (Mahashtami)', 'holiday_date' => '2027-10-08', 'holiday_type' => 'restricted', 'description' => 'Dussehra (Mahashtami)', 'year' => 2027],
            ['holiday_name' => 'Dussehra (Mahanavami)', 'holiday_date' => '2027-10-09', 'holiday_type' => 'restricted', 'description' => 'Dussehra (Mahanavami)', 'year' => 2027],
            ['holiday_name' => "Maharishi Valmiki's Birthday", 'holiday_date' => '2027-10-15', 'holiday_type' => 'restricted', 'description' => "Maharishi Valmiki's Birthday", 'year' => 2027],
            ['holiday_name' => 'Karaka Chaturthi (Karva Chouth)', 'holiday_date' => '2027-10-18', 'holiday_type' => 'restricted', 'description' => 'Karaka Chaturthi (Karva Chouth)', 'year' => 2027],
            ['holiday_name' => 'Naraka Chaturdasi', 'holiday_date' => '2027-10-28', 'holiday_type' => 'restricted', 'description' => 'Naraka Chaturdasi', 'year' => 2027],
            ['holiday_name' => 'Govardhan Puja', 'holiday_date' => '2027-10-30', 'holiday_type' => 'restricted', 'description' => 'Govardhan Puja', 'year' => 2027],
            ['holiday_name' => 'Bhai Duj', 'holiday_date' => '2027-10-31', 'holiday_type' => 'restricted', 'description' => 'Bhai Duj', 'year' => 2027],
            ['holiday_name' => 'Prathar Sasthi or Surya Sasthi (Chhat Puja)', 'holiday_date' => '2027-11-04', 'holiday_type' => 'restricted', 'description' => 'Prathar Sasthi or Surya Sasthi (Chhat Puja)', 'year' => 2027],
            ['holiday_name' => "Guru Teg Bahadur's Martyrdom Day", 'holiday_date' => '2027-11-24', 'holiday_type' => 'restricted', 'description' => "Guru Teg Bahadur's Martyrdom Day", 'year' => 2027],
            ['holiday_name' => "Hazarat Ali's Birthday", 'holiday_date' => '2027-12-13', 'holiday_type' => 'restricted', 'description' => "Hazarat Ali's Birthday", 'year' => 2027],
            ['holiday_name' => 'Christmas Eve', 'holiday_date' => '2027-12-24', 'holiday_type' => 'restricted', 'description' => 'Christmas Eve', 'year' => 2027],
        ];
    }
}
