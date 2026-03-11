<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteQuickUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pdf_path' => [
                'required',
                'string',
                'max:255',
                'regex:/\.pdf$/i',
                Rule::unique('quick_uploads', 'pdf_path'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pdf_path.regex' => 'The upload path must reference a PDF file.',
            'pdf_path.unique' => 'This uploaded file has already been submitted.',
        ];
    }

    public function pdfPath(): string
    {
        return trim((string) $this->string('pdf_path'));
    }
}
