<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordOtp;
use App\Mail\RegistationOtp;
use App\Mail\RegistrationOtp;
use App\Models\EmailOtp;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    use ApiResponse;

    /**
     * Send a Forgot Password (OTP) to the user via email.
     *
     * @param  \App\Models\User  $user
     * @return void
     */

    private function sendOtp(User $user)
    {
        // Check if OTP already exists and not expired
        $existingOtp = EmailOtp::where('user_id', $user->id)->first();

        if ($existingOtp) {
            $expiresAt = $existingOtp->expires_at instanceof \Carbon\Carbon
                ? $existingOtp->expires_at
                : \Carbon\Carbon::parse($existingOtp->expires_at)->setTimezone('UTC');

            $now = \Carbon\Carbon::now('UTC');

            if ($now->lt($expiresAt)) {
                $remaining = $now->diffInSeconds($expiresAt);
                return response()->json([
                    'status' => false,
                    'message' => "Please wait {$remaining} seconds before requesting a new OTP.",
                    'data' => [],
                ], 422);
            }
        }

        // OTP expired or not exists
        $code = rand(1000, 9999);

        $otp = EmailOtp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'avatar' => $user->avatar ?? null,
                'verification_code' => $code,
                'expires_at' => Carbon::now('UTC')->addMinute(),
            ]
        );


        Mail::to($user->email)->send(new ForgotPasswordOtp($user, $code));

        return response()->json([
            'status' => true,
            'message' => 'An OTP has been sent to your email',
            'data' => [],
        ], 200);
    }


    /**
     * Send a Register (OTP) to the user via email.
     *
     * @param  \App\Models\User  $user
     * @return void
     */

    private function verifyOTP($user)
    {
        $code = rand(1000, 9999);

        // Store verification code in the database
        $verification = EmailOtp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'verification_code' => $code,
                'expires_at' => Carbon::now()->addMinutes(15)
            ]
        );

        Mail::to($user->email)->send(new RegistrationOtp($user, $code));
    }

    /**
     * User Login
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request with the Login query.
     * @return \Illuminate\Http\JsonResponse  JSON response with success or error.
     */

    public function userLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $credentials = $request->only('email', 'password');

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            // Save device token if provided
            if ($request->has('device_token')) {
                $user->device_token = $request->input('device_token');
                $user->save();
            }

            // Email not verified
            if (is_null($user->email_verified_at)) {
                $this->verifyOTP($user);
                $user->setAttribute('token', null);
            } else {
                // Generate Sanctum token
                $token = $user->createToken('auth_token')->plainTextToken;
                $user->setAttribute('token', $token);
            }

            return $this->success($user, 'User authenticated successfully', 200);
        }

        return $this->error([], 'Invalid credentials', 401);
    }
    public function guestLogin(Request $request)
    {
        try {

            $uniqueId = substr(uniqid(), -4);
            $guestName = "Guest_" . $uniqueId;
            $guestEmail = "guest_" . uniqid() . "@yourdomain.com";

            $user = User::create([
                'name'     => $guestName,
                'email'    => $guestEmail,
                'password' => Hash::make(Str::random(16)),
                'role'     => User::ROLE_GUEST,
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
     * Verify Email to send otp
     *
     * @param  \Illuminate\Http\Request  $request .
     * @return \Illuminate\Http\JsonResponse  JSON response with success or error.
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
            // Retrieve the user by email
            $user = User::where('email', $request->input('email'))->first();

            $this->sendOtp($user);

            return $this->success($user, 'OTP has been sent successfully.', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Resend an OTP to the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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
            // Retrieve the user by email
            $user = User::where('email', $request->input('email'))->first();

            $this->sendOtp($user);

            return $this->success($user, 'OTP has been sent successfully.', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Verify the OTP sent to the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function otpVerify(Request $request)
    {

        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|numeric|digits:4',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        try {
            // Retrieve the user by email
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
            } else {

                return $this->error([], 'Invalid or expired OTP', 400);
            }
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Password Reset to the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'password.min' => 'The password must be at least 8 characters long.',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), "Validation Error", 422);
        }

        try {
            // Retrieve the user by email
            $user = User::where('email', $request->input('email'))->first();

            $user->password = Hash::make($request->input('password'));
            $user->save();

            return $this->success($user, 'Password Reset successfully.', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return $this->success([], 'Logged out successfully', 200);
    }
}
