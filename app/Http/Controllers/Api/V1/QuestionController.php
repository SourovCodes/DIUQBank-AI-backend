<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexQuestionRequest;
use App\Http\Resources\Api\V1\QuestionResource;
use App\Models\Question;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuestionController extends Controller
{
    public function index(IndexQuestionRequest $request): AnonymousResourceCollection
    {
        $questions = Question::query()
            ->with($this->questionRelationships())
            ->withCount('submissions')
            ->filter($request->filters())
            ->orderByDesc('created_at')
            ->paginate($request->perPage())
            ->withQueryString();

        return QuestionResource::collection($questions);
    }

    public function show(Question $question): QuestionResource
    {
        $question->load([
            ...$this->questionRelationships(),
            'submissions' => fn (HasMany $query): HasMany => $query
                ->with('uploader:id,name')
                ->latest(),
        ])->loadCount('submissions');

        return new QuestionResource($question);
    }

    public function incrementViews(Question $question): JsonResponse
    {
        $question->increment('views');
        $question->refresh();

        return response()->json([
            'data' => [
                'id' => $question->id,
                'views' => $question->views,
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function questionRelationships(): array
    {
        return [
            'department:id,name,short_name',
            'course:id,department_id,name',
            'semester:id,name',
            'examType:id,name',
        ];
    }
}
