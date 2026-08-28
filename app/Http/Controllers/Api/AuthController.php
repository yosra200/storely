<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Traits\ApiResponse;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\forgotPasswordRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetOtpMail;
use App\Http\Requests\resetPasswordRequest;
use App\Http\Requests\verifyOtpRequest;

class AuthController extends Controller
{
    use ApiResponse;
public function register(RegisterRequest $request)
{
    $data = array_merge(
        $request->validated(),
        ['role' => 'delivery']
    );

    User::create($data);

    return $this->successMessage(__('auth.register_success'));
}
   public function login(LoginRequest $request)
{
    $user = User::where('phone', $request->phone)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return $this->errorResponse(__('auth.invalid_credentials'), 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return $this->successResponse([
        'user' => new UserResource($user),
        'access_token' => $token,
    ], __('auth.login_success'));
}


    public function forgotPassword(forgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse(
                __('messages.password_reset_user_not_found'),
                422
            );
        }

        $otp = random_int(100000, 999999);

        // Save OTP
        $user->update([
            'password_reset_otp' => $otp,
            'password_reset_otp_expires_at' => now()->addMinutes(5),
        ]);

        // Send OTP
        Mail::to($user->email)->send(
            new PasswordResetOtpMail($otp)
        );

        return $this->successResponse(
            [],
            __('messages.otp_sent_successfully'),
            200
        );
    }

    public function verifyOtp(verifyOtpRequest $request)
    {


        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse(
                __('messages.password_reset_user_not_found'),
                422
            );
        }

        if ($user->password_reset_otp !== $request->otp) {
            return $this->errorResponse(
                __('messages.invalid_otp'),
                422
            );
        }

        if (now()->greaterThan($user->password_reset_otp_expires_at)) {
            return $this->errorResponse(
                __('messages.otp_expired'),
                422
            );
        }


        $user->update([
            'password_reset_otp' => null,
            'password_reset_otp_expires_at' => null,
        ]);

        return $this->successResponse(
            [],
            __('messages.otp_verified_successfully'),
            200
        );
    }



    public function resetPassword(Request $request)
    {


        $status = Password::reset(
            $request->only('password', 'password_confirmation'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse(
                [],
                __('messages.password_reset_success'),
                200
            );
        }

        return $this->errorResponse(
            __('messages.password_reset_failed'),
            422
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successMessage(__('auth.logout_success'));
    }


        public function changePassword(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse(__('messages.unauthorized'), 401);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:current_password',
            ],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return $this->errorResponse(
                __('messages.current_password_incorrect'),
                422
            );
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return $this->successResponse(
            null,
            __('messages.password_changed_successfully')
        );
    }
}
