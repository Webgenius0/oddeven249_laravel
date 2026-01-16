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

    /**
     * Send OTP to temporary storage (5-minute validity)
     */
    private function sendOtpTemp($data)
    {

        $validator = Validator::make($data, [
            'email' => 'required|email',
            'name' => 'required|string',
            'password' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
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
        // upload avatar if exists
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
                'verification_code' => $code,
                'expires_at' => Carbon::now('UTC')->addMinute(2),
                'user_id' => null,
                'avatar' => $avatarPath, // new line
            ]
        );

        // Mail::to($tempUser->email)->send(new RegistrationOtp($tempUser, $code));
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
            'agree_to_terms' => 'required|boolean',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
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

        return $this->sendOtpTemp($request->only('name', 'email', 'password', 'phone_code', 'phone', 'avatar'));
    }

    /**
     * Verify OTP and create user
     */
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
            'phone' => $tempUser->phone,
            'email_verified_at' => now(),
            'avatar' => $tempUser->avatar,
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
}
