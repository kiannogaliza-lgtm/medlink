<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use App\Models\User;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhoneChangeController extends Controller
{
    public function verify(Request $request, OtpService $otpService)
    {
        $user = auth()->user();
        $pending = $user?->pending_phone_number;

        if (!$user || empty($pending)) {
            $msg = 'No pending phone number change found.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['code' => $msg]);
        }

        $result = $otpService->verify($pending, 'phone_change', (string) $request->input('code', ''));

        if (!$result['ok']) {
            $msg = $otpService->errorMessage($result);
            if ($request->expectsJson()) {
                return response()->json([
                    'success'       => false,
                    'message'       => $msg,
                    'error'         => $result['error'] ?? null,
                    'attempts_left' => $result['attempts_left'] ?? null,
                    'retry_after'   => $result['retry_after'] ?? null,
                ], 422);
            }
            return back()->withErrors(['code' => $msg]);
        }

        $oldMasked = PhoneNumber::mask($user->phone_number);
        $newMasked = PhoneNumber::mask($pending, $request->session()->get('phone_change_format'));

        $conflict = DB::transaction(function () use ($user, $pending) {
            $exists = User::where('phone_number', $pending)
                ->where('id', '!=', $user->id)
                ->where('registration_unverified', false)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                return true;
            }

            $user->update([
                'phone_number'         => $pending,
                'pending_phone_number' => null,
                'phone_verified_at'    => now(),
            ]);

            return false;
        });

        if ($conflict) {
            $msg = 'This phone number is already in use by another account.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['code' => $msg]);
        }

        AccountLog::record(
            $user,
            'Phone Number Changed',
            "Changed from {$oldMasked} to {$newMasked} via SMS OTP.",
            trim($user->first_name . ' ' . $user->last_name),
            $user->role
        );

        $request->session()->forget('phone_change_format');

        $msg = 'Phone number verified and updated successfully!';
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'user'    => $user->fresh(),
            ]);
        }

        return back()->with('success', $msg);
    }

    public function resend(Request $request, OtpService $otpService)
    {
        $user = auth()->user();
        $pending = $user?->pending_phone_number;

        if (!$user || empty($pending)) {
            $msg = 'No pending phone number change found.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['code' => $msg]);
        }

        $result = $otpService->issue($pending, 'phone_change', $user);

        if (!$result['ok']) {
            $msg = $otpService->errorMessage($result);
            if ($request->expectsJson()) {
                return response()->json([
                    'success'     => false,
                    'message'     => $msg,
                    'error'       => $result['error'] ?? null,
                    'retry_after' => $result['retry_after'] ?? null,
                ], 422);
            }
            return back()->withErrors(['code' => $msg]);
        }

        $msg = 'A new verification code has been sent.';
        if ($request->expectsJson()) {
            return response()->json([
                'success'             => true,
                'message'             => $msg,
                'resend_available_in' => (int) config('otp.resend_cooldown_seconds', 60),
            ]);
        }

        return back()->with('status', $msg);
    }

    public function cancel(Request $request)
    {
        $user = auth()->user();

        if ($user && $user->pending_phone_number !== null) {
            $user->update(['pending_phone_number' => null]);
        }

        $request->session()->forget('phone_change_format');

        $msg = 'Pending phone number change cancelled.';
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'user'    => $user?->fresh(),
            ]);
        }

        return back()->with('status', $msg);
    }
}
