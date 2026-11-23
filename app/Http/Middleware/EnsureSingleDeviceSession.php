<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleDeviceSession
{
    /**
     * Ensure only one device can stay logged into an account at a time.
     * If another device logs in, this device is automatically logged out.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user()->fresh();
            $sessionToken = $request->session()->get('active_session_token');

            if ($user && !empty($user->active_session_token) && $user->active_session_token !== $sessionToken) {
                $message = 'There is another device that opened this account. You have been automatically logged out.';

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'authenticated' => false,
                        'reason'        => 'other_device',
                        'message'       => $message,
                    ], 401);
                }

                return redirect()->route('login', ['kicked' => 1])
                    ->with('session_kicked', $message);
            }
        }

        return $next($request);
    }
}
