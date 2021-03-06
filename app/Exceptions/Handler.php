<?php
/**
 * Handler.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Exceptions;

use Auth;
use ErrorException;
use Exception;
use FireflyIII\Jobs\MailError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class Handler
 *
 * @package FireflyIII\Exceptions
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport
        = [
            AuthorizationException::class,
            HttpException::class,
            ModelNotFoundException::class,
        ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof FireflyException || $exception instanceof ErrorException) {

            $isDebug = env('APP_DEBUG', false);

            return response()->view('errors.FireflyException', ['exception' => $exception, 'debug' => $isDebug], 500);
        }

        return parent::render($request, $exception);
    }


    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  Exception $exception
     *
     * @return void
     */
    public function report(Exception $exception)
    {

        if ($exception instanceof FireflyException || $exception instanceof ErrorException) {
            $userData = [
                'id'    => 0,
                'email' => 'unknown@example.com',
            ];
            if (Auth::check()) {
                $userData['id']    = Auth::user()->id;
                $userData['email'] = Auth::user()->email;
            }
            $data = [
                'class'        => get_class($exception),
                'errorMessage' => $exception->getMessage(),
                'time'         => date('r'),
                'stackTrace'   => $exception->getTraceAsString(),
                'file'         => $exception->getFile(),
                'line'         => $exception->getLine(),
                'code'         => $exception->getCode(),
            ];

            // create job that will mail.
            $job = new MailError($userData, env('SITE_OWNER'), Request::ip(), $data);
            dispatch($job);
        }

        parent::report($exception);
    }
}
