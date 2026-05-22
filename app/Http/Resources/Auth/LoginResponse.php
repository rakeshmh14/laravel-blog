<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\User $resource
 */
class LoginResponse extends JsonResource
{
    public function __construct(
        \App\Models\User $user,
        public readonly string $token,
    ) {
        parent::__construct($user);
        $this->wrap = null;
    }

    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource),
            'token' => $this->token,
        ];
    }
}
