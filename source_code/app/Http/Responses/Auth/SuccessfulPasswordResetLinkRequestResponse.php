<?php

namespace App\Http\Responses\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulPasswordResetLinkRequestResponseContract;

class SuccessfulPasswordResetLinkRequestResponse implements SuccessfulPasswordResetLinkRequestResponseContract
{
    /**
     * The response status language key.
     */
    public function __construct(protected string $status) {}

    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request)
    {
        Log::info('auth.password_reset_link.succeeded', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'status' => $this->status,
        ]);

        return $request->wantsJson()
            ? new JsonResponse(['message' => trans($this->status)], 200)
            : back()->with('status', trans($this->status));
    }
}
