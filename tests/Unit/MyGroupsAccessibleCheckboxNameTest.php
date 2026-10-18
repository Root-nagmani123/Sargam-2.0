<?php

namespace Tests\Unit;

use Tests\TestCase;

class MyGroupsAccessibleCheckboxNameTest extends TestCase
{
    public function test_generated_student_checkbox_name_uses_the_student_name_as_text(): void
    {
        $view = file_get_contents(resource_path('views/admin/dashboard/my_groups.blade.php'));

        $this->assertStringContainsString(
            "aria-label=\"' + escapeHtml('Select ' + (s.name || 'officer trainee'))",
            $view
        );
    }
}
