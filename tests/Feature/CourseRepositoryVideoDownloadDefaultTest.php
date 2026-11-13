<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The upload form always posts the switch's state, and the switch rendered
 * unticked, so every new Course / Other upload with a video stored
 * video_download_enabled = 0 unless the admin noticed it. The migration and
 * product decision (2026-10-08) say downloads default on (PR #334 F-056).
 *
 * form.reset() — run when the upload modal opens fresh and after a successful
 * upload — restores a checkbox to its `checked` attribute, so the attribute is
 * what sets the default; the edit flow overrides it with the stored value.
 */
class CourseRepositoryVideoDownloadDefaultTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function switches(): array
    {
        return [
            'Course category' => ['video_download_enabled_course'],
            'Other category' => ['video_download_enabled_other'],
        ];
    }

    /** @dataProvider switches */
    public function test_the_download_switch_is_on_for_a_new_upload(string $id): void
    {
        $blade = file_get_contents(resource_path('views/admin/course-repository/show.blade.php'));

        $this->assertSame(1, preg_match('/<input\b[^>]*\bid="' . $id . '"[^>]*>/', $blade, $m), "one <input id=\"{$id}\">");
        $this->assertMatchesRegularExpression('/\schecked(\s|>|=)/', $m[0], "{$id} renders ticked");
    }
}
