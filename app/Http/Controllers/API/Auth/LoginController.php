<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordOtp;
use App\Mail\RegistrationOtp;
use App\Models\EmailOtp;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    use ApiResponse;

    // ----------------------------------------------------------------
    // CONFIG CONSTANTS
    // ----------------------------------------------------------------
    private const MAX_ATTEMPTS        = 5;   // কতবার fail হলে lock হবে
    private const LOCKOUT_MINUTES     = 15;  // কত মিনিট locked থাকবে
    private const TOKEN_EXPIRY_DAYS   = 30;  // access token lifetime
    private const REFRESH_EXPIRY_DAYS = 60;  // refresh token lifetime


    // ================================================================
    // PRIVATE HELPERS
    // ================================================================

    /**
     * Forgot Password OTP পাঠাও
     * (original — অপরিবর্তিত)
     */
    private function sendOtp(User $user)
    {
        $existingOtp = EmailOtp::where('user_id', $user->id)->first();

        if ($existingOtp) {
            $expiresAt = $existingOtp->expires_at instanceof Carbon
                ? $existingOtp->expires_at
                : Carbon::parse($existingOtp->expires_at)->setTimezone('UTC');

            $now = Carbon::now('UTC');

            if ($now->lt($expiresAt)) {
                $remaining = $now->diffInSeconds($expiresAt);
                return response()->json([
                    'status'  => false,
                    'message' => "Please wait {$remaining} seconds before requesting a new OTP.",
                    'data'    => [],
                ], 422);
            }
        }

        $code = rand(1000, 9999);

        EmailOtp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name'              => $user->name,
                'email'             => $user->email,
                'password'          => $user->password,
                'avatar'            => $user->avatar ?? null,
                'verification_code' => $code,
                'expires_at'        => Carbon::now('UTC')->addMinute(),
            ]
        );

        Mail::to($user->email)->send(new ForgotPasswordOtp($user, $code));

        return response()->json([
            'status'  => true,
            'message' => 'An OTP has been sent to your email',
            'data'    => [],
        ], 200);
    }

    /**
     * Registration verification OTP পাঠাও
     * (original — অপরিবর্তিত)
     */
    private function verifyOTP(User $user)
    {
        $code = rand(1000, 9999);

        EmailOtp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'verification_code' => $code,
                'expires_at'        => Carbon::now()->addMinutes(15),
            ]
        );

        Mail::to($user->email)->send(new RegistrationOtp($user, $code));
    }

    // ----------------------------------------------------------------
    // Rate Limiting Helpers
    // ----------------------------------------------------------------

    /** IP + Email দিয়ে unique throttle cache key */
    private function throttleKey(string $email, string $ip): string
    {
        return 'login_attempts_' . md5(strtolower($email) . '|' . $ip);
    }

    /** User lockout-এ আছে কিনা */
    private function isLockedOut(string $key): bool
    {
        return Cache::has("{$key}_locked");
    }

    /** Lockout কত সেকেন্ড বাকি */
    private function remainingLockoutSeconds(string $key): int
    {
        $until = Cache::get("{$key}_locked_until", time());
        return max(0, (int) $until - time());
    }

    /** Failed attempt বাড়াও, limit পার হলে lock করো */
    private function incrementFailedAttempts(string $key): void
    {
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addMinutes(self::LOCKOUT_MINUTES));

        if ($attempts >= self::MAX_ATTEMPTS) {
            $lockUntil = time() + (self::LOCKOUT_MINUTES * 60);
            Cache::put("{$key}_locked", true, now()->addMinutes(self::LOCKOUT_MINUTES));
            Cache::put("{$key}_locked_until", $lockUntil, now()->addMinutes(self::LOCKOUT_MINUTES));
        }
    }

    /** Current failed attempt count */
    private function getFailedAttempts(string $key): int
    {
        return (int) Cache::get($key, 0);
    }

    /** Login সফল হলে counter clear করো */
    private function clearFailedAttempts(string $key): void
    {
        Cache::forget($key);
        Cache::forget("{$key}_locked");
        Cache::forget("{$key}_locked_until");
    }

    // ----------------------------------------------------------------
    // Refresh Token Helpers
    // ----------------------------------------------------------------

    /** Refresh token generate করে Cache এ store করো */
    private function generateRefreshToken(User $user, string $deviceName): string
    {
        $token    = Str::random(80);
        $cacheKey = "refresh_tokens_{$user->id}";

        $tokens         = Cache::get($cacheKey, []);
        $tokens[$token] = [
            'user_id'     => $user->id,
            'device_name' => $deviceName,
            'expires_at'  => now()->addDays(self::REFRESH_EXPIRY_DAYS)->timestamp,
        ];

        Cache::put($cacheKey, $tokens, now()->addDays(self::REFRESH_EXPIRY_DAYS));

        return $token;
    }

    /** Refresh token verify করো (user_id দিয়ে) */
    private function verifyRefreshTokenByUser(string $token, int $userId): ?array
    {
        $tokens  = Cache::get("refresh_tokens_{$userId}", []);
        $payload = $tokens[$token] ?? null;

        if (!$payload || time() > $payload['expires_at']) {
            return null;
        }

        return $payload;
    }

    /** Refresh token invalidate করো */
    private function invalidateRefreshToken(string $token, int $userId): void
    {
        $cacheKey = "refresh_tokens_{$userId}";
        $tokens   = Cache::get($cacheKey, []);
        unset($tokens[$token]);
        Cache::put($cacheKey, $tokens, now()->addDays(self::REFRESH_EXPIRY_DAYS));
    }


    // ================================================================
    // PUBLIC ENDPOINTS
    // ================================================================

    /**
     * User Login
     * ✅ Rate limiting / throttling
     * ✅ Account suspension check
     * ✅ Multi-device token (device_name)
     * ✅ Refresh token
     * ✅ 2FA stub (future phase — commented)
     */
    public function userLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'        => 'required|email|exists:users,email',
            'password'     => 'required',
            'device_token' => 'nullable|string|max:255',
            'device_name'  => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // ── Rate Limiting ────────────────────────────────────────────
        $throttleKey = $this->throttleKey($request->email, $request->ip());

        if ($this->isLockedOut($throttleKey)) {
            $seconds = $this->remainingLockoutSeconds($throttleKey);
            return $this->error(
                [],
                "Too many failed login attempts. Please try again in {$seconds} seconds.",
                429
            );
        }

        $user = User::where('email', $request->email)->first();

        // ── Password Check ───────────────────────────────────────────
        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->incrementFailedAttempts($throttleKey);
            $attempts  = $this->getFailedAttempts($throttleKey);
            $remaining = self::MAX_ATTEMPTS - $attempts;

            $message = $remaining > 0
                ? "Invalid credentials. {$remaining} attempt(s) left before lockout."
                : "Too many failed attempts. Account locked for " . self::LOCKOUT_MINUTES . " minutes.";

            return $this->error([], $message, 401);
        }

        // ── Suspension Check ─────────────────────────────────────────
        if ($user->is_suspended) {
            return $this->error(
                ['reason' => $user->suspension_reason ?? 'Please contact support.'],
                'Your account has been suspended.',
                403
            );
        }

        // ── Email Verification Check ─────────────────────────────────
        if (is_null($user->email_verified_at)) {
            $this->verifyOTP($user);
            $user->setAttribute('token', null);
            return $this->success($user, 'Email not verified. OTP has been sent to your email.', 200);
        }

        // ── 2FA Stub — Future Phase ───────────────────────────────────
        // if ($user->two_fa_enabled) {
        //     $stub = $this->initiate2FA($user);
        //     return $this->success(['2fa_token' => $stub], 'Enter your 2FA code.', 202);
        // }

        // ── Login Success ─────────────────────────────────────────────
        $this->clearFailedAttempts($throttleKey);

        // Push notification device token
        if ($request->filled('device_token')) {
            $user->device_token = $request->input('device_token');
        }

        $user->last_login_at = now();
        $user->save();

        // Multi-device named token
        $deviceName  = $request->input('device_name', 'default_device');
        $accessToken = $user->createToken($deviceName)->plainTextToken;

        // Refresh token
        $refreshToken = $this->generateRefreshToken($user, $deviceName);

        $user->setAttribute('token', $accessToken);
        $user->setAttribute('refresh_token', $refreshToken);
        $user->setAttribute('token_expires_at', now()->addDays(self::TOKEN_EXPIRY_DAYS)->toISOString());

        return $this->success($user, 'User authenticated successfully', 200);
    }

    /**
     * Guest Login
     * (original — অপরিবর্তিত)
     */
    public function guestLogin(Request $request)
    {
        try {
            $uniqueId   = substr(uniqid(), -4);
            $guestName  = "Guest_" . $uniqueId;
            $guestEmail = "guest_" . uniqid() . "@yourdomain.com";

            $user = User::create([
                'name'              => $guestName,
                'email'             => $guestEmail,
                'password'          => Hash::make(Str::random(16)),
                'role'              => User::ROLE_GUEST,
                'email_verified_at' => now(),
            ]);

            $token = $user->createToken('guest_token')->plainTextToken;
            $user->setAttribute('token', $token);

            return $this->success($user, 'Logged in as Guest successfully', 200);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    /**
     * Email Verify — OTP পাঠাও
     * (original — অপরিবর্তিত)
     */
    public function emailVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        try {
            $user = User::where('email', $request->input('email'))->first();
            $this->sendOtp($user);
            return $this->success($user, 'OTP has been sent successfully.', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * OTP Resend
     * (original — অপরিবর্তিত)
     */
    public function otpResend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        try {
            $user = User::where('email', $request->input('email'))->first();
            $this->sendOtp($user);
            return $this->success($user, 'OTP has been sent successfully.', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * OTP Verify
     * (original — অপরিবর্তিত)
     */
    public function otpVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|numeric|digits:4',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        try {
            $user = User::where('email', $request->input('email'))->first();

            $verification = EmailOtp::where('user_id', $user->id)
                ->where('verification_code', $request->input('otp'))
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if ($verification) {
                $user->email_verified_at = Carbon::now();
                $user->save();
                $verification->delete();
                return $this->success($user, 'OTP Verified Successfully', 200);
            }

            return $this->error([], 'Invalid or expired OTP', 400);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Reset Password
     * (original — অপরিবর্তিত)
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'The password must be at least 8 characters long.',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        try {
            $user           = User::where('email', $request->input('email'))->first();
            $user->password = Hash::make($request->input('password'));
            $user->save();
            return $this->success($user, 'Password Reset successfully.', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Logout — current device
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success([], 'Logged out from current device successfully.', 200);
    }

    /**
     * Logout from ALL devices
     */
    public function logoutAllDevices(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        Cache::forget("refresh_tokens_{$user->id}");
        return $this->success([], 'Logged out from all devices successfully.', 200);
    }

    /**
     * Active Sessions — সব active device এর list
     */
    public function activeSessions(Request $request)
    {
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $sessions = $request->user()
            ->tokens()
            ->select(['id', 'name', 'last_used_at', 'created_at'])
            ->get()
            ->map(fn ($token) => [
                'session_id'   => $token->id,
                'device_name'  => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at'   => $token->created_at,
                'is_current'   => $token->id === $currentTokenId,
            ]);

        return $this->success($sessions, 'Active sessions retrieved.', 200);
    }

    /**
     * Revoke Specific Session — নির্দিষ্ট device logout
     */
    public function revokeSession(Request $request, $sessionId)
    {
        $deleted = $request->user()->tokens()->where('id', $sessionId)->delete();

        if (!$deleted) {
            return $this->error([], 'Session not found or already revoked.', 404);
        }

        return $this->success([], 'Session revoked successfully.', 200);
    }

    /**
     * Refresh Token — নতুন access token নাও
     */
    public function refreshToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'       => 'required|integer|exists:users,id',
            'refresh_token' => 'required|string',
            'device_name'   => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        $payload = $this->verifyRefreshTokenByUser($request->refresh_token, $request->user_id);

        if (!$payload) {
            return $this->error([], 'Invalid or expired refresh token. Please login again.', 401);
        }

        $user = User::find($request->user_id);

        if ($user->is_suspended) {
            return $this->error(
                ['reason' => $user->suspension_reason],
                'Your account has been suspended.',
                403
            );
        }

        $deviceName = $request->input('device_name', $payload['device_name']);
        $user->tokens()->where('name', $deviceName)->delete();

        $newAccessToken  = $user->createToken($deviceName)->plainTextToken;
        $newRefreshToken = $this->generateRefreshToken($user, $deviceName);
        $this->invalidateRefreshToken($request->refresh_token, $user->id);

        return $this->success([
            'token'            => $newAccessToken,
            'refresh_token'    => $newRefreshToken,
            'token_expires_at' => now()->addDays(self::TOKEN_EXPIRY_DAYS)->toISOString(),
        ], 'Token refreshed successfully.', 200);
    }
}
