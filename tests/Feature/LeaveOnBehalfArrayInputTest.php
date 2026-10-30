<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

class LeaveOnBehalfArrayInputTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function payload(): array
    {
        $enrolment = DB::table('student_master_course__map')->where('active_inactive', 1)->first();
        $nature = DB::table('leave_nature_master')->where('leave_type', 'LEAVE')->where('active_inactive', 1)->value('pk');
        if (! $enrolment || $nature === null) {
            $this->markTestSkipped('needs an active enrolment and LEAVE nature');
        }
        DB::table('course_master')->where('pk', $enrolment->course_master_pk)
            ->update(['active_inactive' => 1, 'end_date' => '2099-12-31']);

        return [
            'course_master_pk' => (string) $enrolment->course_master_pk,
            'student_master_pk' => (string) $enrolment->student_master_pk,
            'leave_nature_master_pk' => (string) $nature,
            'from_date' => now()->addDays(5)->toDateString(),
            'to_date' => now()->addDays(5)->toDateString(),
            'time_from' => '09:00',
            'time_to' => '18:00',
            'reason' => 'F-081 scalar <reason> & details',
            'contact_number' => '9876543210',
        ];
    }

    public static function arrayFields(): array
    {
        $fields = [
            'course_master_pk', 'student_master_pk', 'leave_nature_master_pk',
            'from_date', 'to_date', 'time_from', 'time_to', 'reason', 'contact_number',
        ];
        $cases = [];
        foreach ($fields as $field) {
            $cases[$field] = [[$field]];
        }
        $cases['all fields'] = [$fields];

        return $cases;
    }

    private function formValues(string $html): array
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new \DOMXPath($document);
        $values = [];
        foreach (['course_master_pk', 'leave_nature_master_pk'] as $field) {
            $values[$field] = $xpath->evaluate('string(//select[@name="'.$field.'"]/option[@selected]/@value)');
        }
        $values['student_master_pk'] = $xpath->evaluate('string(//select[@name="student_master_pk"]/@data-preselect)');
        foreach (['from_date', 'to_date', 'time_from', 'time_to', 'contact_number'] as $field) {
            $values[$field] = $xpath->evaluate('string(//input[@name="'.$field.'"]/@value)');
        }
        $values['reason'] = $xpath->evaluate('string(//textarea[@name="reason"])');

        return $values;
    }

    /** @dataProvider arrayFields */
    public function test_array_input_redirects_to_a_complete_validation_form_with_empty_scalar_fallbacks(array $fields): void
    {
        $payload = $this->payload();
        foreach ($fields as $field) {
            $payload[$field] = ['nested' => [$payload[$field]]];
        }
        $before = DB::table('leave_application')->count();
        $create = route('admin.leave-on-behalf.create');
        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->from($create)
            ->post(route('admin.leave-on-behalf.store'), $payload)
            ->assertRedirect($create)
            ->assertSessionHasErrors($fields);
        foreach ($fields as $field) {
            $this->assertIsArray(session()->getOldInput($field), 'exercise real flashed arrays');
        }

        $response = $this->get($create);
        $response->assertOk()->assertViewIs('admin.leave.on_behalf.apply');
        $this->assertStringContainsString('</html>', $response->getContent());
        $values = $this->formValues($response->getContent());
        foreach ($payload as $field => $value) {
            $this->assertSame(in_array($field, $fields, true) ? '' : $value, $values[$field], $field);
        }
        $this->assertSame($before, DB::table('leave_application')->count());
    }

    public function test_scalar_input_is_preserved_and_escaped_after_validation_failure(): void
    {
        $payload = $this->payload();
        $payload['contact_number'] = 'invalid-contact';
        $create = route('admin.leave-on-behalf.create');
        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])->from($create)
            ->post(route('admin.leave-on-behalf.store'), $payload)
            ->assertRedirect($create)->assertSessionHasErrors('contact_number');

        $response = $this->get($create)->assertOk();
        $values = $this->formValues($response->getContent());
        foreach ($payload as $field => $value) {
            $this->assertSame($value, $values[$field], $field);
        }
        $this->assertStringContainsString('F-081 scalar &lt;reason&gt; &amp; details', $response->getContent());
    }
}
