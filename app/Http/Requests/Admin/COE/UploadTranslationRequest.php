<?php

namespace App\Http\Requests\Admin\COE;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload Translated Paper (Rajbhasha): the Hindi .docx, same file checks as
 * the faculty's paper. Who may upload, and to which paper, is checked by the
 * controller and RajbhashaPaperService.
 */
class UploadTranslationRequest extends FormRequest
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
        return ['translated_paper' => UploadQuestionPaperRequest::paperFileRules()];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return UploadQuestionPaperRequest::paperFileMessages('translated_paper');
    }
}
