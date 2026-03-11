<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexQuestionRequest extends FormRequest
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
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'semester_id' => ['nullable', 'integer', Rule::exists('semesters', 'id')],
            'exam_type_id' => ['nullable', 'integer', Rule::exists('exam_types', 'id')],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, int>
     */
    public function filters(): array
    {
        $filters = $this->safe()->only([
            'department_id',
            'course_id',
            'semester_id',
            'exam_type_id',
            'user_id',
        ]);

        /** @var array<string, int> $normalizedFilters */
        $normalizedFilters = collect($filters)
            ->filter(fn (mixed $value): bool => $value !== null)
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        return $normalizedFilters;
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }
}
