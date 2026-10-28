<?php

namespace App\Http\Requests\Admin\COE;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Translate Question Paper (Rajbhasha): the translation written in the editor -
 * Rajbhasha no longer uploads a .docx. Which paper may be translated is checked
 * by RajbhashaPaperService, not here.
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
            'paper_html.required' => 'Write the translated paper before uploading it.',
        ];
    }
}
