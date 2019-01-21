<?php

namespace Preferred\Application\Middlewares;

use Closure;
use Illuminate\Http\Response;
use Preferred\Domain\Users\Entities\User;
use Preferred\Infrastructure\Support\TwoFactorAuthentication;
use Preferred\Interfaces\Http\Controllers\ResponseTrait;

class CheckAgainTwoFactorAuthentication
{
    use ResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        $user = User::with(['profile'])->find(auth()->id());

        if (!empty($user->profile->google2fa_enable)) {
            $twoFactorAuthentication = new TwoFactorAuthentication($request);

            if (!empty($request->one_time_password) && $twoFactorAuthentication->verifyGoogle2FA
                ($user->profile->google2fa_secret, $request->one_time_password) === true) {
                return $next($request);
            }

            $message = __('Invalid 2FA verification code. Please try again');

            return $this->respondWithCustomData([
                'message'       => $message,
                'is_verify2fa'  => 0,
                'is_refresh2fa' => 1,
            ], Response::HTTP_LOCKED);
        }

        return $next($request);
    }
}