<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

class NoticeDeploymentReadinessTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_notice_migrations_and_physical_schema_are_present(): void
    {
        foreach ([
            '2026_09_28_100000_add_audience_targeting_to_notices_notification',
            '2026_09_28_120000_backfill_notice_audience_map_scalars',
        ] as $migration) {
            $this->assertTrue(DB::table('migrations')->where('migration', $migration)->exists(), $migration.' must run before activation');
        }

        $this->assertTrue(Schema::hasTable('notice_audience_map'), 'STOP: notice_audience_map is missing');
        foreach (['pk', 'notices_notification_pk', 'audience_type', 'reference_pk', 'active_inactive', 'created_date'] as $column) {
            $this->assertTrue(Schema::hasColumn('notice_audience_map', $column), 'Missing audience map column: '.$column);
        }
        foreach (['department_master_pk', 'group_type_map_pk', 'audience_mode'] as $column) {
            $this->assertTrue(Schema::hasColumn('notices_notification', $column), 'Missing notice column: '.$column);
        }

        foreach ([
            'notice_audience_map' => [
                'PRIMARY' => ['pk'],
                'idx_nam_notice' => ['notices_notification_pk', 'audience_type'],
                'idx_nam_reference' => ['audience_type', 'reference_pk'],
            ],
            'notices_notification' => ['idx_nn_department' => ['department_master_pk']],
        ] as $table => $expected) {
            $indexes = collect(DB::select('SHOW INDEX FROM '.$table));
            foreach ($expected as $name => $columns) {
                $this->assertSame($columns, $indexes->where('Key_name', $name)->sortBy('Seq_in_index')->pluck('Column_name')->all(), 'Missing or incorrect index: '.$name);
            }
        }
    }

    public static function dashboardAccounts(): array
    {
        return [
            'staff' => ['E', ['Employee']],
            'faculty' => ['F', ['Faculty']],
            'trainee' => ['S', ['Student-OT']],
        ];
    }

    /** @dataProvider dashboardAccounts */
    public function test_migrated_dashboard_and_notice_feed_return_complete_pages(string $category, array $roles): void
    {
        $user = User::where('user_category', $category)->whereNotNull('user_id')->orderBy('pk')->first();
        if (! $user) {
            $this->markTestSkipped('Missing '.$category.' login for deployment rehearsal');
        }

        $this->as($user, $roles);
        foreach (['live', 'archive'] as $scope) {
            $query = notice_feed_query_by_role($scope);
            $this->assertNotNull($query);
            $this->assertStringContainsString('notice_audience_map', $query->toSql());
            $query->limit(1)->get();

            $response = $this->get(route('admin.dashboard.feed', ['tab' => 'notices', 'notice_scope' => $scope]));
            $response->assertOk()->assertViewIs('admin.dashboard.feed');
            $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('notices'));
            $this->assertStringContainsString('</html>', $response->getContent());
        }

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();
        $this->assertStringContainsString('</html>', $response->getContent());
    }
}
