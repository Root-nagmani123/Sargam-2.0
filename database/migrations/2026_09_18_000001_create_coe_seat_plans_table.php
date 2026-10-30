<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — one automatic room-allocation run (Examination module doc, "AUTOMATIC Exam ROOM
 * ALLOCATION" and "VENUE / CLASSROOM MANAGEMENT").
 *
 * The doc places this immediately after the laptop check in its final workflow:
 * ALLOCATE ROLL NUMBERS → CHECK LAPTOP REQUIREMENT → ALLOCATE VENUE → NOTIFY
 * PARTICIPANTS. All three inputs it names — roll number, laptop requirement, room
 * capacity — already exist on this branch, which is why the plan references them rather
 * than storing its own copies.
 *
 * ── Why a plan row, separate from the seats ──
 * The doc's screen lists one row per course with a Status and an Edit action, and the
 * allocation is re-runnable ("Re assign Assig" on the roll-number screen, and a room plan
 * is redone whenever the intake or the venue list changes). So the configuration the
 * administrator typed lives here and survives, while coe_seat_allocations is replaced
 * wholesale by each run.
 *
 * ── A cohort can have several plans ──
 * One per examination: Mid-Term and Final-Term are seated separately, in different rooms,
 * on different days. So the key is course + batch + NAME, not course + batch.
 *
 * ── There is no examination_drive_pk yet ──
 * The doc's Examination Drive module is a later milestone (its Milestone 3; this is
 * Milestone 6) and no examination_drives table exists. exam_name / exam_date /
 * exam_start_time therefore carry what the participant's "Your Examination Details"
 * notification has to print. When the drive module lands, it adds a nullable
 * examination_drive_pk here and these three become its fallback — nothing has to move.
 *
 * ── Type mapping, checked against the live schema ──
 *   course_master.pk       bigint UNSIGNED → unsignedBigInteger here
 *   student_master.batch   int             → integer here (batch is a year, not a string)
 * MySQL refuses a foreign key whose signedness differs from the referenced column, so
 * these are not interchangeable with Laravel's foreignId()/id() defaults.
 *
 * ── Deployment ──
 *   Order        : migration first, then code. All three tables are new, so the currently
 *                  deployed application runs unchanged against the new schema.
 *   Rollback     : down() drops this table; the two child tables drop first from their own
 *                  migrations, which carry later timestamps. Rolling back after an
 *                  allocation loses the seating plan — reproducible by re-running
 *                  Allocate, because no marks or attendance hang off a seat yet.
 *   Verification : open /admin/coe/seat-allocation, create a plan for a course that has
 *                  roll numbers, add venues, press Allocate, and confirm the room-wise
 *                  counts add up to the participant count.
 */
return new class extends Migration
{
    private const TABLE = 'coe_seat_plans';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');

            $table->string('plan_name', 150);

            $table->unsignedBigInteger('course_master_pk');
            // NOT NULL on purpose: MySQL lets NULLs repeat inside a UNIQUE index, so a
            // nullable batch would silently allow two plans of the same name for one course.
            $table->integer('batch')->comment('Matches student_master.batch (year as int)');

            // ── What the participant's notification has to print (doc: "VENUE NOTIFICATION") ──
            $table->string('exam_name', 150)->nullable()
                ->comment('e.g. "Mid-Term Examination". Free text until the Examination Drive module exists');
            $table->string('subject_name', 150)->nullable()
                ->comment('e.g. "Public Administration". Free text for the same reason');
            $table->date('exam_date')->nullable();
            $table->time('exam_start_time')->nullable();

            /*
             * ── How laptop requirement is read off the OT laptop status ──
             * The declaration asks "Is a laptop required?", which does not by itself say
             * who needs a laptop-capable desk — that depends on the examination. Storing
             * the interpretation makes it the administrator's choice per plan rather than
             * a guess hard-coded in PHP, which is the doc's "MOST IMPORTANT DESIGN
             * PRINCIPLE" applied to this screen.
             */
            $table->tinyInteger('laptop_requirement')->default(2)
                ->comment('1=Participants who declared Laptop Required=Yes take laptop seats, 2=Participants who declared Laptop Required=No take them, 3=Everyone needs a laptop seat, 4=Nobody (offline paper examination)');

            /*
             * ── Allocation rules ──
             * Named columns rather than a JSON blob, for the same reason
             * coe_roll_number_allocations gives each rule its own: every one is a
             * documented requirement, and a column stays queryable and readable in a diff.
             *
             * The doc's other seating rule — "participants from the same service are not
             * seated together" — is deliberately NOT repeated here. It is satisfied at
             * roll-number allocation time by RollNumberAllocator, and seats are handed out
             * in roll-number order, so the property carries through to the desks. A second
             * implementation could only disagree with the first.
             */
            $table->boolean('rule_split_by_laptop')->default(true)
                ->comment("The doc's worked example: laptop-needing participants go only to rooms with laptop capacity");
            $table->boolean('rule_balance_rooms')->default(false)
                ->comment('Spread participants evenly across the chosen rooms instead of filling each to capacity before opening the next');
            $table->boolean('rule_skip_alternate_seats')->default(false)
                ->comment('Leave an empty desk between candidates: seats step 1, 3, 5 ... so a room of 30 takes 15. The usual anti-copying measure for a written paper');

            $table->unsignedInteger('seat_start_number')->default(1)
                ->comment('The number the first seat in EVERY room is called; rooms are numbered independently so a seat is addressed as room + seat');

            // Written by each run so the grid can show the split without counting rows.
            $table->unsignedInteger('allocated_count')->default(0);
            $table->unsignedInteger('unallocated_count')->default(0)
                ->comment('Participants the last run could not seat — always a capacity shortfall, never a silent drop');

            $table->tinyInteger('status')->default(1)
                ->comment('1=Draft (venues being chosen), 2=Allocated (seats assigned, administrator only), 3=Published (participants can see their seat)');
            $table->timestamp('allocated_at')->nullable();
            $table->unsignedBigInteger('allocated_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            // One plan of a given name per cohort. Enforced here and not only in the
            // controller: an exists()-then-insert check loses a double submit.
            $table->unique(['course_master_pk', 'batch', 'plan_name'], 'coe_sp_course_batch_name_unique');
            // The grid's Status filter reads this alone.
            $table->index('status', 'coe_sp_status_index');
            // The listing orders by examination date within a course.
            $table->index(['course_master_pk', 'exam_date'], 'coe_sp_course_examdate_index');

            // restrict, not cascade: deleting a course must not silently discard the
            // seating plan of every examination that was arranged for it.
            $table->foreign('course_master_pk', 'coe_sp_course_master_pk_foreign')
                ->references('pk')->on('course_master')
                ->onDelete('restrict');

            /*
             * There is no foreign key to the laptop answers: the plan does not point at
             * one cohort's declaration, it reads whatever coe_laptop_responses holds for
             * its own participants at the moment it allocates. laptop_requirement above
             * is the whole of the link.
             */
        });
    }

    public function down(): void
    {
        // coe_seat_plan_venues and coe_seat_allocations hold the child foreign keys and
        // drop themselves first — their migrations carry later timestamps, so rollback
        // runs them before this one.
        Schema::dropIfExists(self::TABLE);
    }
};
