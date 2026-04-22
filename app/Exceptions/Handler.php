<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->renderable(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please sign in to continue.',
                ], 401);
            }

            return redirect()
                ->route('auth.role-selection', ['intent' => 'login'])
                ->withErrors([
                    'email' => 'Please sign in to continue.',
                ]);
        });

        $this->renderable(function (AuthorizationException|AccessDeniedHttpException|SpatieUnauthorizedException $exception, Request $request) {
            return $this->friendlyResponse(
                request: $request,
                status: 403,
                title: 'Access Denied',
                message: 'You do not have permission to access that page or perform that action.'
            );
        });

        $this->renderable(function (ModelNotFoundException|NotFoundHttpException $exception, Request $request) {
            return $this->friendlyResponse(
                request: $request,
                status: 404,
                title: 'Page Not Found',
                message: 'The page or record you were looking for could not be found.'
            );
        });

        $this->renderable(function (TokenMismatchException $exception, Request $request) {
            return $this->friendlyResponse(
                request: $request,
                status: 419,
                title: 'Session Expired',
                message: 'Your session expired. Please try again.'
            );
        });

        $this->renderable(function (Throwable $exception, Request $request) {
            if (
                $exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof AuthorizationException
                || $exception instanceof ModelNotFoundException
                || $exception instanceof TokenMismatchException
                || $exception instanceof AccessDeniedHttpException
                || $exception instanceof NotFoundHttpException
                || $exception instanceof SpatieUnauthorizedException
            ) {
                return null;
            }

            return $this->friendlyResponse(
                request: $request,
                status: 500,
                title: 'Something Went Wrong',
                message: 'Something went wrong while processing your request. Please try again.'
            );
        });
    }

    private function friendlyResponse(Request $request, int $status, string $title, string $message): Response|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], $status);
        }

        if (!$request->isMethod('GET')) {
            return back()
                ->withInput($request->except($this->dontFlash))
                ->withErrors([
                    'app' => $message,
                ]);
        }

        return response()->view('errors.friendly', [
            'status' => $status,
            'title' => $title,
            'message' => $message,
        ], $status);
    }
}
