<?php

namespace Modules\GuestTaClaim\Support;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Modules\GuestTaClaim\Contracts\TimetableProvider;

/**
 * Sample data so the module can be tried before it is wired to the real
 * Sargam timetable. All names are fictitious.
 */
class DemoTimetableProvider implements TimetableProvider
{
    protected array $courses = [
        ['id' => 'c1', 'name' => '101st Foundation Course', 'coordinator_user_id' => 1],
        ['id' => 'c2', 'name' => 'Mid-Career Training Programme, Phase IV', 'coordinator_user_id' => 1],
        ['id' => 'c3', 'name' => 'Induction Training Programme', 'coordinator_user_id' => 1],
    ];

    protected array $faculty = [
        ['id' => 'f1', 'name' => 'Dr. Anand Sharma', 'designation' => 'Professor of Public Policy', 'organisation' => 'Institute of Policy Studies', 'category' => 'Academic', 'city' => 'New Delhi',
            'topics' => ['Evidence-based policy making', 'Designing public programmes', 'Policy evaluation methods']],
        ['id' => 'f2', 'name' => 'Shri Rajesh Menon', 'designation' => 'Secretary to GoI (Retd.)', 'organisation' => 'Former civil servant', 'category' => 'Retired officer', 'city' => 'Bengaluru',
            'topics' => ['Leadership in district administration', 'Crisis management: lessons from the field', 'Centre–state coordination']],
        ['id' => 'f3', 'name' => 'Ms. Kavita Iyer', 'designation' => 'Director', 'organisation' => 'Community health organisation', 'category' => 'Non-official expert', 'city' => 'Chennai',
            'topics' => ['Community participation in health delivery', 'Working with civil society', 'Last-mile service delivery']],
        ['id' => 'f4', 'name' => 'Dr. Vikram Singh', 'designation' => 'Joint Secretary', 'organisation' => 'Government of India (serving)', 'category' => 'Serving officer', 'city' => 'Lucknow',
            'topics' => ['Digital public infrastructure', 'Data governance in government', 'e-Office and process reengineering']],
    ];

    protected array $slots = [['09:30', '11:00'], ['11:30', '13:00'], ['14:30', '16:00']];
    protected array $venues = ['Lecture Hall 1', 'Lecture Hall 2', 'Main Auditorium'];

    public function courses(): Collection
    {
        return collect($this->courses);
    }

    public function course(string|int $courseId): ?array
    {
        return $this->courses()->firstWhere('id', (string) $courseId);
    }

    public function guestSessions(string|int $courseId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $out = collect();
        $seed = ord(substr((string) $courseId, 1, 1) ?: 'a');
        $day = 0;

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $date) {
            if ($day >= 31) {
                break;
            }
            if ($date->isSunday()) {
                continue;
            }
            foreach ($this->slots as $s => [$start, $end]) {
                $k = ($seed + $day * 2 + $s) % 6;
                if ($k < 4) {
                    $f = $this->faculty[$k];
                    $out->push([
                        'id' => "{$courseId}-{$date->format('Y-m-d')}-{$s}",
                        'faculty_id' => $f['id'],
                        'date' => $date->format('Y-m-d'),
                        'start' => $start,
                        'end' => $end,
                        'topic' => $f['topics'][($day + $s) % count($f['topics'])],
                        'venue' => $this->venues[($day + $s) % 3],
                    ]);
                }
            }
            $day++;
        }

        return $out;
    }

    public function faculty(string|int $facultyId): ?array
    {
        $f = collect($this->faculty)->firstWhere('id', (string) $facultyId);

        if (! $f) {
            return null;
        }

        $faculty = collect($f)->except('topics')->all();
        $faculty['official_address'] = collect([$faculty['organisation'], $faculty['city']])->filter()->join(', ');

        return $faculty;
    }

    public function facultyMany(array $ids): Collection
    {
        return collect($ids)->mapWithKeys(fn ($id) => [$id => $this->faculty($id)])->filter();
    }
}