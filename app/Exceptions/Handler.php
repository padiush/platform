<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Companion API errors use the same JSON envelope as the rest of the
        // API ({ message, message_type }) so the mobile app parses one shape.
        // Validation (422) is handled by ApiFormRequest; these cover the
        // framework-thrown auth/authorization/not-found cases. The types below
        // are what those exceptions are normalized to before render callbacks
        // run (e.g. a policy AuthorizationException and Sanctum's
        // MissingAbilityException both become AccessDeniedHttpException; a
        // ModelNotFoundException becomes NotFoundHttpException).
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/v1/*')) {
                return $this->apiError('api.unauthenticated', 401);
            }
        });

        $this->renderable(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/v1/*')) {
                return $this->apiError('api.forbidden', 403);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/v1/*')) {
                return $this->apiError('api.not_found', 404);
            }
        });
    }

    private function apiError(string $message, int $status)
    {
        return response()->json(
            ['message' => $message, 'message_type' => 'error'],
            $status
        );
    }
}
