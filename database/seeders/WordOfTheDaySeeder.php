<?php

namespace Database\Seeders;

use App\Models\WordOfTheDay;
use Illuminate\Database\Seeder;

class WordOfTheDaySeeder extends Seeder
{
    /**
     * Seed bilingual “word of the day” entries (admin / governance / academic theme).
     * Safe to run multiple times: upserts by hindi + english pair.
     */
    public function run(): void
    {
        $rows = [
            ['अर्हक अंक', 'Qualifying marks'],
            ['पाठ्यक्रम', 'Curriculum'],
            ['अनुशासन', 'Discipline'],
            ['उपस्थिति', 'Attendance'],
            ['मूल्यांकन', 'Evaluation'],
            ['संकाय', 'Faculty'],
            ['अध्ययन सामग्री', 'Study material'],
            ['परीक्षा', 'Examination'],
            ['गोपनीयता', 'Confidentiality'],
            ['पारदर्शिता', 'Transparency'],
            ['जवाबदेही', 'Accountability'],
            ['नेतृत्व', 'Leadership'],
            ['सार्वजनिक सेवा', 'Public service'],
            ['नैतिकता', 'Ethics'],
            ['प्रशासनिक आदेश', 'Administrative order'],
            ['संवैधानिक मूल्य', 'Constitutional values'],
            ['सुशासन', 'Good governance'],
            ['कार्य निष्पादन', 'Performance of duties'],
            ['समन्वय', 'Coordination'],
            ['निर्णय लेख', 'Written order / decision'],
            ['प्रतिवेदन', 'Report'],
            ['कार्य योजना', 'Work plan'],
            ['समीक्षा', 'Review'],
            ['क्षमता निर्माण', 'Capacity building'],
            ['संस्थागत स्मृति', 'Institutional memory'],
            ['शासन', 'Governance'],
            ['नीति', 'Policy'],
            ['दिशा-निर्देश', 'Guidelines'],
            ['अधिकार क्षेत्र', 'Jurisdiction'],
            ['संहिता', 'Code / compendium'],
            ['अभिलेख', 'Official record'],
        ];

        $now = now();
        foreach ($rows as $i => [$hindi, $english]) {
            WordOfTheDay::query()->firstOrCreate(
                [
                    'hindi_text' => $hindi,
                    'english_text' => $english,
                ],
                [
                    'sort_order' => $i + 1,
                    'active_inactive' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
