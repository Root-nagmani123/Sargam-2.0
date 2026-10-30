<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\CourseRepositoryController;
use App\Support\CourseRepositorySearch;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CourseRepositorySearchInputTest extends TestCase
{
    public function test_filter_normalization_preserves_unicode_and_internal_spaces(): void
    {
        $label = "  \u{0939}\u{093F}\u{0902}\u{0926}\u{0940}  Law \u{1F600}  ";
        $criteria = CourseRepositorySearch::criteria(Request::create('/', 'GET', [
            'course' => $label."\r\n\0\u{0085}\u{2028}\u{2029}",
            'subject' => $label."\t\x7F",
        ]));

        $this->assertSame(trim($label), $criteria['course']);
        $this->assertSame(trim($label), $criteria['subject']);
    }

    public function test_invalid_utf8_does_not_silently_remove_a_filter(): void
    {
        $criteria = CourseRepositorySearch::criteria(Request::create('/', 'GET', [
            'course' => "Law\xFF",
            'subject' => "Policy\xFF",
        ]));

        $this->assertNotSame('', $criteria['course']);
        $this->assertNotSame('', $criteria['subject']);
        $this->assertTrue(mb_check_encoding($criteria['course'], 'UTF-8'));
        $this->assertTrue(mb_check_encoding($criteria['subject'], 'UTF-8'));
    }

    public function test_search_beyond_six_words_is_marked_instead_of_silently_truncated(): void
    {
        $criteria = CourseRepositorySearch::criteria(Request::create('/', 'GET', [
            'q' => 'one two three four five six distinguishing',
        ]));

        $this->assertTrue($criteria['token_limit_exceeded']);
        $this->assertCount(CourseRepositorySearch::MAX_TOKENS, $criteria['tokens']);
        $this->assertSame('one two three four five six distinguishing', $criteria['q']);
    }

    public static function searchFailures(): array
    {
        return [
            'search' => ['userSearch', 'Course repository universal search failed'],
            'suggestions' => ['userSearchSuggest', 'Course repository search suggest failed'],
        ];
    }

    /** @dataProvider searchFailures */
    public function test_failed_search_logs_only_a_fixed_message_and_exception_class(string $method, string $message): void
    {
        $input = "Law\r\n[2026-10-13] production.INFO: forged log entry";
        $exception = new QueryException('select * from repository where course = ?', [$input], new \PDOException('Query failed'));
        $this->assertStringContainsString($input, $exception->getMessage());
        DB::shouldReceive('table')->once()->andThrow($exception);
        Log::shouldReceive('error')->once()->with($message, ['exception' => QueryException::class]);

        $response = app(CourseRepositoryController::class)->{$method}(
            Request::create('/', 'GET', ['q' => $input, 'course' => $input, 'subject' => $input])
        );

        if ($method === 'userSearch') {
            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('Search is unavailable right now. Please try again.', session('error'));
        } else {
            $this->assertSame(['suggestions' => []], $response->getData(true));
        }
    }
}
