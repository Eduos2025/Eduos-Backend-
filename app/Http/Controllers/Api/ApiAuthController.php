<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Qs;
use App\Helpers\Usr;
use App\Http\Requests\UserChangePass;
use App\Http\Requests\UserUpdate;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ApiAuthController extends ApiBaseController
{
    /**
     * Authenticate user and return Sanctum token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login'    => 'required|string',
            'password' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $login = $request->input('login');
        $password = $request->input('password');

        // Determine login type: email, phone, or username
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : (is_numeric($login) ? 'phone' : 'username');

        $credentials = [
            $fieldType => $login,
            'password' => $password,
        ];

        if (!Auth::attempt($credentials)) {
            return $this->sendError('Unauthorized.', ['error' => 'Invalid credentials.'], 401);
        }

        $user = User::where($fieldType, $login)->first();

        if ($user->blocked) {
            Auth::logout();
            return $this->sendError('Unauthorized.', ['error' => 'Your account has been blocked. Please contact support.'], 403);
        }

        // Generate Sanctum token
        $token = $user->createToken('api_token')->plainTextToken;

        // Add hashed ID
        $userData = $user->toArray();
        $userData['id'] = Qs::hash($user->id);

        // Load relations based on user type
        if ($user->user_type === 'student') {
            $user->load(['student_record.my_class', 'student_record.section']);
            $userData['student_record'] = $user->student_record ? $user->student_record->toArray() : null;
            if (isset($userData['student_record']['id'])) {
                $userData['student_record']['id'] = Qs::hash($userData['student_record']['id']);
            }
        } elseif (in_array($user->user_type, Qs::getStaff())) {
            $user->load('staff');
            $userData['staff'] = $user->staff ? $user->staff->toArray() : null;
        }

        $response = [
            'token'    => $token,
            'user'     => $userData,
            'settings' => Qs::getSettings()->pluck('description', 'type')->toArray(),
        ];

        return $this->sendResponse($response, 'User logged in successfully.');
    }

    /**
     * Log the authenticated user out (Revoke token).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse([], 'User logged out successfully.');
    }

    /**
     * Get the authenticated user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $userData = $user->toArray();
        $userData['id'] = Qs::hash($user->id);

        if ($user->user_type === 'student') {
            $user->load(['student_record.my_class', 'student_record.section', 'student_record.dorm']);
            $userData['student_record'] = $user->student_record ? $user->student_record->toArray() : null;
            if (isset($userData['student_record']['id'])) {
                $userData['student_record']['id'] = Qs::hash($userData['student_record']['id']);
            }
        } elseif (in_array($user->user_type, Qs::getStaff())) {
            $user->load('staff');
            $userData['staff'] = $user->staff ? $user->staff->toArray() : null;
        }

        return $this->sendResponse($userData, 'User profile retrieved successfully.');
    }

    /**
     * Update user profile.
     *
     * @param UserUpdate $request
     * @return JsonResponse
     */
    public function updateProfile(UserUpdate $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->only(Qs::getUserRecord());
        $data['name'] = strtoupper($request->name);

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $data['photo'] = Qs::getUploadPath($user->user_type) . $user->code . '/' . $f['name'];
            $photo->storeAs($f['path']);
        }

        $user->update($data);

        return $this->sendResponse($user->toArray(), 'Profile updated successfully.');
    }

    /**
     * Change password.
     *
     * @param UserChangePass $request
     * @return JsonResponse
     */
    public function changePassword(UserChangePass $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->sendError('Validation Error.', ['current_password' => ['The specified password does not match the current password.']], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return $this->sendResponse([], 'Password changed successfully.');
    }
}
