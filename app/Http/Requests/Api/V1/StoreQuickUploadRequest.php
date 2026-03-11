<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuickUploadRequest extends FormRequest
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
            'file_name' => ['required', 'string', 'max:255', 'regex:/\.pdf$/i'],
            'content_type' => ['required', 'string', 'in:application/pdf'],
            'file_size' => ['required', 'integer', 'min:1', 'max:10485760'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file_name.regex' => 'The file name must end with .pdf.',
            'content_type.in' => 'Only PDF uploads are allowed.',
            'file_size.max' => 'The PDF may not be greater than 10 MB.',
        ];
    }

    public function fileName(): string
    {
        return trim((string) $this->string('file_name'));
    }

    public function contentType(): string
    {
        return (string) $this->string('content_type');
    }

    public function fileSize(): int
    {
        return $this->integer('file_size');
    }
}
