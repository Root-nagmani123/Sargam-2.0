<?php

namespace App\Http\Requests\Admin\COE;

use App\Rules\SafeUploadedDocument;
use App\Rules\SingleFileExtension;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add Question Paper (faculty): either a .docx upload or the paper written in
 * the editor. Which paper the faculty may add is checked by the service, not
 * here - authorize() only gates the shape of the request.
 */
class UploadQuestionPaperRequest extends FormRequest
{
    public const MODE_UPLOAD = 'upload';

    public const MODE_CREATE = 'create';

    private const MAX_FILE_KB = 10240;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in([self::MODE_UPLOAD, self::MODE_CREATE])],
            'question_paper' => array_merge(['exclude_unless:mode,'.self::MODE_UPLOAD], self::paperFileRules()),
            'paper_html' => ['exclude_unless:mode,'.self::MODE_CREATE, 'required', 'string', 'max:2000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::paperFileMessages() + [
            'paper_html.required' => 'Write the question paper before uploading it.',
        ];
    }

    /**
     * A question paper .docx: shared by Add, Re-upload and Rajbhasha's translation.
     *
     * @return array<int, mixed>
     */
    public static function paperFileRules(): array
    {
        return [
            // bail: the checks below call UploadedFile methods.
            'bail', 'required', 'file',
            // Some libmagic builds sniff a .docx as a plain zip; SafeUploadedDocument
            // then pins the bytes to a real Word container without macros.
            'mimes:docx,zip', 'max:'.SafeUploadedDocument::maxKilobytes(self::MAX_FILE_KB),
            // Laravel 9 has no `extensions:` rule, and a real .zip passes both checks above.
            function (string $attribute, $value, \Closure $fail) {
                if (strtolower($value->getClientOriginalExtension()) !== 'docx') {
                    $fail('Only .docx files are allowed.');
                }
            },
            new SingleFileExtension(),
            new SafeUploadedDocument(['docx']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paperFileMessages(string $field = 'question_paper'): array
    {
        return [
            $field.'.required' => 'Choose the question paper file.',
            $field.'.mimes' => 'Only .docx files are allowed.',
            $field.'.max' => 'The file may not be larger than '.SafeUploadedDocument::maxLabel(self::MAX_FILE_KB).'.',
        ];
    }

    public function isEditorPaper(): bool
    {
        return $this->input('mode') === self::MODE_CREATE;
    }
}
