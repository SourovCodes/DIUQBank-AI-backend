<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Semester;
use Illuminate\Http\JsonResponse;

class FilterOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'departments' => Department::query()
                ->whereHas('questions')
                ->orderBy('name')
                ->get(['id', 'name', 'short_name']),
            'courses' => Course::query()
                ->whereHas('questions')
                ->orderBy('name')
                ->get(['id', 'department_id', 'name']),
            'semesters' => Semester::query()
                ->whereHas('questions')
                ->orderBy('name')
                ->get(['id', 'name']),
            'exam_types' => ExamType::query()
                ->whereHas('questions')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
