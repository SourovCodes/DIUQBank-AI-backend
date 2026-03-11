<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;

class SubmissionController extends Controller
{
    public function incrementViews(Submission $submission): JsonResponse
    {
        $submission->increment('views');
        $submission->refresh();

        return response()->json([
            'data' => [
                'id' => $submission->id,
                'question_id' => $submission->question_id,
                'views' => $submission->views,
            ],
        ]);
    }
}
