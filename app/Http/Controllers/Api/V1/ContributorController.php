<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContributorRequest;
use App\Http\Resources\Api\V1\ContributorResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContributorController extends Controller
{
    public function index(IndexContributorRequest $request): AnonymousResourceCollection
    {
        $contributors = User::query()
            ->contributors()
            ->withCount('submissions')
            ->withSum('submissions as submission_views_sum', 'views')
            ->orderByDesc('submissions_count')
            ->orderBy('name')
            ->paginate($request->perPage())
            ->withQueryString();

        return ContributorResource::collection($contributors);
    }

    public function show(User $contributor): ContributorResource
    {
        $contributor = User::query()
            ->contributors()
            ->withCount('submissions')
            ->withSum('submissions as submission_views_sum', 'views')
            ->findOrFail($contributor->id);

        return new ContributorResource($contributor);
    }
}
