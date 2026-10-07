<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCordinatorMaster extends Model
{
    protected $table = 'course_coordinator_master';
    protected $guarded = [];
    protected $primaryKey = 'pk';
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'Modified_date';
    
    protected $fillable = [
        'courses_master_pk',
        'Coordinator_name',
        'Assistant_Coordinator_name',
        'assistant_coordinator_role',
        'created_date',
        'Modified_date'
    ];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'courses_master_pk', 'pk');
    }

    /**
     * Courses the user coordinates (Coordinator_name) or assists
     * (Assistant_Coordinator_name), both holding faculty_master pks. The user
     * is matched to their faculty record by faculty_master.employee_master_pk
     * = user_credentials.user_id; a user with no faculty record gets none.
     *
     * Only employee logins (user_category 'E') qualify: for other categories
     * user_id is not an employee pk, and on trainee logins it can equal a
     * stranger's, which would hand them that person's courses.
     *
     * Both columns are read with peopleIn(), the rule the Course Information
     * sheet prints them by, so a person is granted the courses that print
     * their name and no others.
     *
     * @return int[]
     */
    public static function courseIdsForUser($user = null): array
    {
        $user ??= auth()->user();
        if (($user->user_category ?? null) !== 'E') {
            return [];
        }
        $employeePk = $user->user_id ?? null;
        if (!$employeePk) {
            return [];
        }

        $facultyPk = FacultyMaster::where('employee_master_pk', $employeePk)->value('pk');
        if (!$facultyPk) {
            return [];
        }

        $facultyPk = (int) $facultyPk;

        // LIKE only narrows the rows fetched; peopleIn() decides the match.
        return static::query()
            ->where(function ($q) use ($facultyPk) {
                $q->where('Coordinator_name', 'like', '%'.$facultyPk.'%')
                  ->orWhere('Assistant_Coordinator_name', 'like', '%'.$facultyPk.'%');
            })
            ->get(['courses_master_pk', 'Coordinator_name', 'Assistant_Coordinator_name'])
            ->filter(function ($row) use ($facultyPk) {
                foreach (array_merge(static::peopleIn($row->Coordinator_name), static::peopleIn($row->Assistant_Coordinator_name)) as $part) {
                    if (ctype_digit($part) && (int) $part === $facultyPk) {
                        return true;
                    }
                }

                return false;
            })
            ->pluck('courses_master_pk')
            ->map(fn ($pk) => (int) $pk)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The people one Coordinator_name or Assistant_Coordinator_name value names.
     *
     * A value holds a faculty_master pk, a comma list of pks, or (on older rows)
     * a typed name. A comma list is split only when every part is a pk, so a
     * typed name that contains a comma ("Sharma, R.") stays one person. Parts
     * are trimmed; empty parts are dropped. A part made only of digits is a pk.
     *
     * @return string[]
     */
    public static function peopleIn($value): array
    {
        $value = trim((string) $value);
        $parts = array_values(array_filter(array_map('trim', explode(',', $value)), fn ($part) => $part !== ''));
        foreach ($parts as $part) {
            if (!ctype_digit($part)) {
                return $value !== '' ? [$value] : [];
            }
        }

        return $parts;
    }
}
