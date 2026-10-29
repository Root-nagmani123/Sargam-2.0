<?php

namespace App\Http\Requests\Admin\COE;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Add Question Paper (faculty): the paper written in the editor, from a
 * template or from scratch - faculty no longer upload a file. Which paper the
 * faculty may add is checked by the service, not here - authorize() only gates
 * the shape of the request.
 */
class UploadQuestionPaperRequest extends FormRequest
{
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
            'paper_html' => ['required', 'string', 'max:2000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paper_html.required' => 'Write the question paper before uploading it.',
        ];
    }
}
