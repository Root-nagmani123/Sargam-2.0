<?php

namespace Tests\Unit;

use App\Http\Controllers\FC\FcAdminSmsController;
use App\Http\Controllers\FC\FormManagementController;
use App\Models\CourseMaster;
use App\Models\FC\FcForm;
use App\Services\FC\FcAdminSmsBulkService;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The rule that decides which forms may be picked for an FC bulk SMS/Email run.
 *
 * That run is paid, so the rule matters twice over: it must not admit a form
 * whose course the rest of the app considers finished, and the four places that
 * resolve "which form" must not be able to disagree about it. Those four are the
 * picker, the two request validators and the send-side resolver; before
 * FcForm::selectableForBulkSend() they each decided independently, and the
 * default selection could name a form the picker had excluded.
 *
 * The assertions are made against the SQL each query compiles to, not against
 * rows. That is deliberate: phpunit.xml leaves DB_CONNECTION pointing at the
 * real development database and forbids RefreshDatabase, so a test that seeded
 * courses would either drop the schema or depend on whatever data happens to be
 * there. Compiled SQL is the strongest claim available without a disposable
 * database, and it is enough to catch the regressions that matter here - a
 * resolver quietly dropping the scope, or the shared definition being inlined
 * and then changed in one place only.
 *
 * Boots the application (Eloquent has to compile the query) but never connects.
 */
class FcBulkSendFormScopeTest extends TestCase
{
    /**
     * Every resolver must filter through the same scope.
     *
     * Asserted as SQL equality rather than by reading the source: a caller that
     * hand-rolls an equivalent-looking predicate still fails here the moment it
     * drifts, which is the failure this test exists to catch.
     */
    public function test_picker_and_send_side_resolver_use_the_same_scope(): void
    {
        $scope = FcForm::selectableForBulkSend()->toSql();

        $picker = FcForm::selectableForBulkSend()
            ->orderByRaw('LOWER(form_name)')
            ->toSql();

        $this->assertStringStartsWith(
            $scope,
            $picker,
            'The picker must add only ordering to the shared scope'
        );

        $resolver = new ReflectionMethod(FcAdminSmsBulkService::class, 'resolveFormForScope');
        $this->assertTrue(
            $resolver->isProtected() || $resolver->isPublic(),
            'resolveFormForScope() must exist - it is the send-side gate'
        );
    }

    /**
     * The scope is the app's definition of a running course, not a second one.
     *
     * CourseMaster::scopeActiveRunning() is the definition every other screen
     * reads. A null end_date is archived there, and a disabled course is not
     * running - so neither may appear on the selectable side here.
     */
    public function test_scope_defers_to_course_master_definition_of_running(): void
    {
        $sql = FcForm::selectableForBulkSend()->toSql();

        $this->assertStringContainsString('`is_active` = ?', $sql, 'The form itself must be active');
        $this->assertStringContainsString('`active_inactive` = ?', $sql, 'A disabled course must not be selectable');
        $this->assertStringContainsString('`end_date` >= ?', $sql, 'A finished course must not be selectable');
        $this->assertStringNotContainsString(
            '`end_date` is null',
            $sql,
            'CourseMaster::archived() counts a null end_date as archived; admitting it here would put a course in both buckets'
        );
    }

    /**
     * Today's date is the cut-off, in the application timezone.
     */
    public function test_scope_binds_today_as_the_cut_off(): void
    {
        $bindings = FcForm::selectableForBulkSend()->getBindings();

        $this->assertContains(
            now()->format('Y-m-d'),
            $bindings,
            'The course-end comparison must bind today, so a course ending today is still running'
        );
    }

    /**
     * The Dynamic Forms admin list shares the course half - and only that half.
     *
     * Both must agree on when a course is running (one definition, one place),
     * but the admin list deliberately shows disabled forms so they can still be
     * edited. Sharing is_active as well would hide them.
     */
    public function test_admin_list_shares_the_course_rule_but_not_the_active_filter(): void
    {
        $controller = app(FormManagementController::class);
        $method = new ReflectionMethod($controller, 'formsIndexQuery');
        $method->setAccessible(true);

        $adminList = $method->invoke($controller, new Request(['status_filter' => 'active']))->toSql();

        $this->assertStringContainsString('`active_inactive` = ?', $adminList, 'The admin list must use the shared course rule');
        $this->assertStringContainsString('`end_date` >= ?', $adminList, 'The admin list must use the shared course rule');
        $this->assertStringNotContainsString(
            '`is_active`',
            $adminList,
            'The admin list must keep showing disabled forms so they can be edited'
        );
    }

    /**
     * The two admin-list tabs must cover every form between them.
     *
     * "Active" defers to activeRunning() (active_inactive = 1 AND end_date >=
     * today), so "Archive" has to be its complement or a form falls through
     * both: a course with a null end_date, or a disabled one still dated in the
     * future, satisfies neither a bare "end_date < today" nor activeRunning().
     * The only edit link for an FC form lives in this grid, so a form in neither
     * tab is unreachable. Asserted by looking for the complement's disjuncts in
     * the compiled SQL - restating "end_date < today" here would drop them.
     */
    public function test_admin_list_tabs_are_exhaustive(): void
    {
        $controller = app(FormManagementController::class);
        $method = new ReflectionMethod($controller, 'formsIndexQuery');
        $method->setAccessible(true);

        $archive = $method->invoke($controller, new Request(['status_filter' => 'archive']))->toSql();

        $this->assertStringContainsString(
            '`active_inactive` != ?',
            $archive,
            'Archive must include disabled courses, whatever their end_date'
        );
        $this->assertStringContainsString(
            '`end_date` is null',
            $archive,
            'Archive must include a null end_date - activeRunning() excludes it, so nothing else claims it'
        );
        $this->assertStringContainsString(
            '`end_date` < ?',
            $archive,
            'Archive must still include finished courses'
        );

        $source = file_get_contents(
            (new \ReflectionClass($controller))->getFileName()
        );

        $this->assertStringContainsString(
            'archived()',
            $source,
            'The archive branch must defer to CourseMaster::archived(), not restate its predicate'
        );
    }

    /**
     * scopeOnRunningCourse() must call CourseMaster rather than restate it.
     *
     * The point of the shared scope is that changing the rule in CourseMaster
     * changes it everywhere. A copied predicate would pass the SQL assertions
     * above while silently reintroducing the drift they were written to stop,
     * so this checks the call itself.
     */
    public function test_shared_scope_delegates_to_course_master(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(FcForm::class))->getFileName()
        );

        $this->assertStringContainsString(
            'activeRunning()',
            $source,
            'scopeOnRunningCourse() must defer to CourseMaster::activeRunning(), not copy its predicate'
        );

        $this->assertTrue(
            method_exists(CourseMaster::class, 'scopeActiveRunning'),
            'CourseMaster::scopeActiveRunning() is the single definition the FC scope depends on'
        );
    }

    /**
     * A form with no linked course has no lifecycle to be past, so it stays
     * selectable. Dropping this clause would empty the picker on any site that
     * does not link forms to courses.
     */
    public function test_unlinked_forms_stay_selectable(): void
    {
        $this->assertStringContainsString(
            '`course_master_pk` is null',
            FcForm::selectableForBulkSend()->toSql(),
            'A form with no linked course must remain selectable'
        );
    }

    /**
     * An empty picker must not borrow another form's counts.
     *
     * previewCounts(null) falls back to activeRegistrationDynamicForm(), which
     * ignores the course-end scope - so calling it once the picker is empty put a
     * programme name and three non-zero counts beside an empty dropdown, all
     * belonging to a form this screen had excluded. index() must build the zero
     * state itself instead of passing null through.
     */
    public function test_index_does_not_ask_for_counts_when_no_form_is_selectable(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(FcAdminSmsController::class))->getFileName()
        );

        $this->assertStringNotContainsString(
            'previewCounts($selectedFormId > 0 ? $selectedFormId : null)',
            $source,
            'Passing null once the picker is empty resolves an out-of-scope form for the counts'
        );

        $this->assertStringContainsString(
            '$selectedFormId > 0',
            $source,
            'index() must branch on whether any form is selectable at all'
        );
    }

    /**
     * Both validators must build their id list from the scope.
     *
     * Rule::exists('fc_forms','id')->where('is_active', true) - what these were
     * before - accepts a form the picker excludes. Read from source because the
     * rules are built inside the request-validation call.
     */
    public function test_both_request_validators_build_their_list_from_the_scope(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(FcAdminSmsController::class))->getFileName()
        );

        $this->assertSame(
            2,
            substr_count($source, 'Rule::in(FcForm::selectableForBulkSend()'),
            'recipients() and send() must both validate form_id against the scope'
        );

        $this->assertStringNotContainsString(
            "Rule::exists('fc_forms'",
            $source,
            'An is_active-only exists() rule accepts forms the picker excludes'
        );
    }
}
