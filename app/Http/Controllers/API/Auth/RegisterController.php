<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtp;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; // added for Str::slug()

class RegisterController extends Controller
{
    use ApiResponse;

    private function sendOtpTemp($data)
    {
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'name' => 'required|string',
            'password' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'phone_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:20',
            'role' => 'required|in:influencer,adviser,agency,business_manager,guest', // New validation
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'website_link' => 'nullable|url', // Added
        'category_id' => 'nullable|exists:categories,id', // Added
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tempUser = EmailOtp::where('email', $data['email'])->first();

        if ($tempUser) {
            $expiresAt = Carbon::parse($tempUser->expires_at)->setTimezone('UTC');
            $now = Carbon::now('UTC');

            if ($now->lt($expiresAt)) {
                $remaining = ceil($now->diffInSeconds($expiresAt));
                return response()->json([
                    'status' => false,
                    'message' => "Please wait {$remaining} seconds before requesting a new OTP.",
                    'data' => [],
                ], 422);
            }
        }

        $avatarPath = null;
        if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
            $avatarPath = uploadImage($data['avatar'], 'avatars');
        }

        $code = rand(1000, 9999);

        $tempUser = EmailOtp::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'phone_code' => $data['phone_code'] ?? null,
                'country' => $data['country'] ?? null,
                'verification_code' => $code,
                'expires_at' => Carbon::now('UTC')->addMinute(2),
                'user_id' => null,
                'avatar' => $avatarPath,
                'role' => $data['role'],
                'website_link' => $data['website_link'] ?? null, // Added
                'category_id' => $data['category_id'] ?? null,   // Added
            ]
        );

        return response()->json([
            'status' => true,
            'otp' => $code,
            'message' => 'An OTP has been sent to your email',
            'data' => [],
        ], 200);
    }

    /**
     * Register User - temporary storage with OTP
     */
    public function userRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => 'nullable|string|max:20',
            'phone_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:50',
            'role' => 'required|in:influencer,adviser,agency,business_manager,guest', // New validation
            'agree_to_terms' => 'required|boolean',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'website_link' => 'nullable|url', // Added
            'category_id' => 'nullable|exists:categories,id', // Added
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        if (User::where('email', $request->email)->exists()) {
            return $this->error(['email' => ['The email has already been taken.']], "Validation Error", 422);
        }

        $existingOtp = EmailOtp::where('email', $request->email)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingOtp) {
            $expiresAt = Carbon::parse($existingOtp->expires_at)->setTimezone('UTC');
            $remaining = ceil($expiresAt->floatDiffInSeconds(now()));
            if ($remaining > 0) {
                return $this->error([], "Please wait {$remaining} seconds before requesting a new OTP.", 422);
            }
        }

        return $this->sendOtpTemp($request->only('name', 'email', 'password', 'phone_code', 'country', 'phone', 'avatar', 'role', 'website_link', 'category_id'));
    }
    public function otpVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:email_otps,email',
            'otp' => 'required|numeric|digits:4',
            'device_token' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        $tempUser = EmailOtp::where('email', $request->email)
            ->where('verification_code', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tempUser) {
            return $this->error([], 'Invalid or expired OTP', 400);
        }

        $user = User::create([
            'name' => $tempUser->name,
            'email' => $tempUser->email,
            'password' => $tempUser->password,
            'phone_code' => $tempUser->phone_code,
            'country' => $tempUser->country,
            'phone' => $tempUser->phone,
            'email_verified_at' => now(),
            'avatar' => $tempUser->avatar,
            'role' => $tempUser->role, // Add role
            'website_link' => $tempUser->website_link, // Added
            'category_id' => $tempUser->category_id,   // Added
        ]);

        if ($request->has('device_token')) {
            $user->device_token = $request->device_token;
            $user->save();
            NotificationService::sendWelcomeNotification($user);
        }

        $tempUser->delete();

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->setAttribute('token', $token);

        return $this->success($user, 'OTP verified successfully', 200);
    }

    /**
     * Resend OTP
     */
    public function otpResend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:email_otps,email',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        $tempUser = EmailOtp::where('email', $request->email)->first();
        $expiresAt = Carbon::parse($tempUser->expires_at)->setTimezone('UTC');
        $now = Carbon::now('UTC');

        if ($now->lt($expiresAt)) {
            $remaining = ceil($now->diffInSeconds($expiresAt));
            return $this->error([], "Please wait {$remaining} seconds before requesting a new OTP", 422);
        }

        $code = rand(1000, 9999);
        $tempUser->update([
            'verification_code' => $code,
            'expires_at' => Carbon::now('UTC')->addMinute(5),
        ]);

        Mail::to($tempUser->email)->send(new RegistrationOtp($tempUser, $code));

        return $this->success([], 'OTP Resend has been sent successfully.', 200);
    }

    /**
     * Check if email is available
     */
    public function emailExists(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        $existingUser = User::where('email', $request->email)->exists();
        $existingOtp = EmailOtp::where('email', $request->email)
            ->where('expires_at', '>', now())
            ->exists();

        if ($existingUser || $existingOtp) {
            return $this->error(['email' => ['The email has already been taken.']], "Validation Error", 422);
        }

        return $this->success([], 'Email is available', 200);
    }


    /**
 * Forgot Password - Send OTP
 */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        // Check if there's an existing OTP that hasn't expired
        $existingOtp = EmailOtp::where('email', $request->email)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingOtp) {
            $expiresAt = Carbon::parse($existingOtp->expires_at)->setTimezone('UTC');
            $now = Carbon::now('UTC');
            $remaining = ceil($now->diffInSeconds($expiresAt));

            return $this->error([], "Please wait {$remaining} seconds before requesting a new OTP.", 422);
        }

        $user = User::where('email', $request->email)->first();
        $code = rand(1000, 9999);

        EmailOtp::updateOrCreate(
            ['email' => $request->email],
            [
                'name' => $user->name,
                'password' => $user->password, // Keep existing password
                'phone' => $user->phone,
                'verification_code' => $code,
                'expires_at' => Carbon::now('UTC')->addMinute(5),
                'user_id' => $user->id,
                'avatar' => $user->avatar,
            ]
        );

        // Mail::to($user->email)->send(new ForgotPasswordOtp($user, $code));

        return $this->success([
            'otp' => $code, // Remove this in production
        ], 'Password reset OTP has been sent to your email', 200);
    }

    /**
     * Verify Forgot Password OTP
     */
    public function verifyForgotPasswordOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:4',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        $tempUser = EmailOtp::where('email', $request->email)
            ->where('verification_code', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tempUser) {
            return $this->error([], 'Invalid or expired OTP', 400);
        }

        // Generate a reset token for security
        $resetToken = Str::random(60);

        $tempUser->update([
            'verification_code' => $resetToken, // Store reset token
            'expires_at' => Carbon::now('UTC')->addMinute(15), // 15 minutes to reset password
        ]);

        return $this->success([
            'token' => $resetToken,
            'email' => $request->email,
        ], 'OTP verified successfully. You can now reset your password.', 200);
    }

    /**
     * Reset Password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        $tempUser = EmailOtp::where('email', $request->email)
            ->where('verification_code', $request->token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tempUser) {
            return $this->error([], 'Invalid or expired reset token', 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the OTP record
        $tempUser->delete();

        return $this->success([], 'Password has been reset successfully', 200);
    }

    /**
     * Resend Forgot Password OTP
     */
    public function resendForgotPasswordOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        $tempUser = EmailOtp::where('email', $request->email)->first();

        if (!$tempUser) {
            return $this->error([], 'No OTP request found for this email', 404);
        }

        $expiresAt = Carbon::parse($tempUser->expires_at)->setTimezone('UTC');
        $now = Carbon::now('UTC');

        if ($now->lt($expiresAt)) {
            $remaining = ceil($now->diffInSeconds($expiresAt));
            return $this->error([], "Please wait {$remaining} seconds before requesting a new OTP", 422);
        }

        $code = rand(1000, 9999);
        $tempUser->update([
            'verification_code' => $code,
            'expires_at' => Carbon::now('UTC')->addMinute(5),
        ]);

        // Mail::to($tempUser->email)->send(new ForgotPasswordOtp($tempUser, $code));

        return $this->success([
            'otp' => $code, // Remove this in production
        ], 'Password reset OTP has been resent successfully.', 200);
    }
}
