<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Redirect unauthenticated users to central login with a clear message.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        $loginUrl = $this->loginUrlForHost((string) $request->getHost());
        return redirect()
            ->guest($loginUrl)
            ->with('error', 'Votre session a expiré, veuillez vous reconnecter.');
    }

    private function loginUrlForHost(string $host): string
    {
        // Single public entrypoint (WordPress UI) for all expired sessions.
        return rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/login';
    }

    public function render($request, Throwable $e)
    {
        // CSRF token mismatch / 419 Page Expired
        if ($e instanceof TokenMismatchException) {
            $loginUrl = $this->loginUrlForHost((string) $request->getHost());
            return redirect()
                ->guest($loginUrl)
                ->with('error', 'Votre session a expiré, veuillez vous reconnecter.');
        }

        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 419) {
            $loginUrl = $this->loginUrlForHost((string) $request->getHost());
            return redirect()
                ->guest($loginUrl)
                ->with('error', 'Votre session a expiré, veuillez vous reconnecter.');
        }

        return parent::render($request, $e);
    }
}
