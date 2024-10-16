<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ProfileRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\AgentResource;
use App\Http\Resources\PlayerResource;
use App\Http\Resources\RegisterResource;
use App\Http\Resources\UserResource;
use App\Models\Admin\UserLog;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use HttpResponses;

    private const PLAYER_ROLE = 3;

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('phone', 'password');

        if (! Auth::attempt($credentials)) {
            return $this->error('', 'Credentials do not match!', 401);
        }
        $user = Auth::user();

        if ($user->status == 0) {
            return $this->error('', 'Your account is not activated!', 401);
        }

        if ($user->is_changed_password == 0) {
            return $this->error($user, 'You have to change password', 200);
        }

        UserLog::create([
            'ip_address' => $request->ip(),
            'user_id' => $user->id,
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success(new UserResource($user), 'User login successfully.');
    }

    public function register(RegisterRequest $request)
    {
        $agent = User::where('referral_code', $request->referral_code)->first();

        if (! $agent) {
            return $this->error('', 'Not Found Agent', 401);
        }

        if ($this->isExistingUserForAgent($request->phone, $agent->id)) {
            return $this->error('', 'Already Exist Account for this number', 401);
        }

        $inputs = $request->validated();

        $user = User::create([
            'phone' => $request->phone,
            'name' => $request->name,
            'user_name' => $this->generateRandomString(),
            'password' => Hash::make($inputs['password']),
            'payment_type_id' => $request->payment_type_id,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'agent_id' => $agent->id,
            'type' => UserType::Player,
        ]);
        $user->roles()->sync(self::PLAYER_ROLE);

        return $this->success(new RegisterResource($user), 'User register successfully.');
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();

        return $this->success([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function getUser()
    {
        return $this->success(new PlayerResource(Auth::user()), 'User Success');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $player = Auth::user();

        if (! Hash::check($request->current_password, $player->password)) {
            return $this->error('', 'Old Password is incorrect', 401);
        }

        $player->update([
            'password' => Hash::make($request->password),
            'status' => 1,
        ]);

        return $this->success($player, 'Password has been changed successfully.');
    }

    public function playerChangePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed'],
            'user_id' => ['required'],
        ]);
        $player = User::where('id', $request->user_id)->first();

        if ($player) {
            $player->update([
                'password' => Hash::make($request->password),
                'is_changed_password' => true,
            ]);

            return $this->success($player, 'Password has been changed successfully.');
        } else {
            return $this->error('', 'Not Found Player', 401);
        }
    }

    public function profile(ProfileRequest $request)
    {

        $player = Auth::user();
        $player->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return $this->success(new PlayerResource($player), 'Update profile');
    }

    public function getAgent()
    {
        $player = Auth::user();

        return $this->success(new AgentResource($player->parent), 'Agent Information List');
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'SB'.$randomNumber;
    }

    private function isExistingUserForAgent($phone, $agent_id): bool
    {
        return User::where('phone', $phone)->where('agent_id', $agent_id)->first();
    }
}
