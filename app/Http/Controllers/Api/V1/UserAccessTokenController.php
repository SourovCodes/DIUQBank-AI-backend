<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserAccessTokenController extends Controller
{
    public function __invoke(User $user): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken('local-dev-token')->plainTextToken,
            'token_type' => 'Bearer',
            'user_id' => $user->id,
        ]);
    }
}
