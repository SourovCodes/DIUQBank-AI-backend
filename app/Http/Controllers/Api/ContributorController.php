<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexContributorRequest;
use App\Http\Resources\ContributorResource;
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
