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

        return static::query()
            ->where(function ($q) use ($facultyPk) {
                $q->where('Coordinator_name', $facultyPk)
                  ->orWhereRaw('FIND_IN_SET(?, Assistant_Coordinator_name)', [$facultyPk]);
            })
            ->pluck('courses_master_pk')
            ->map(fn ($pk) => (int) $pk)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
