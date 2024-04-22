<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use App\Traits\HttpResponses;

class Handler extends ExceptionHandler
{
    use HttpResponses;

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
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/v1/restaurant/menu/*')) { // <- Add your condition here
                return $this->error('Menu not found', 'Menu record not found.', 404);
            }
            if ($request->is('api/v1/orderer/orderer-restaurants/*')) { // <- Add your condition here
                return $this->error('Restaurant Not Found', 'Restaurant record not found.', 404);
            }
        });
    }
}
