<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AuthenticatedSessionController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'required|string'
        ]);

        $phone = Phone::where('phone_number', $request->phone_number)->first();
        $user = $phone->user;

        if (!$phone || !$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone_number' => 'The provided credentials are incorrect'
            ]);
        }

        $user->phone_number = $phone->phone_number;

        $token = $user->createToken('API Token');
        $plainTextToken = $token->plainTextToken;
        $accessTokenId = $token->accessToken->id;

        if ($request->filled('fcm_token')) {
            $user->fcm_tokens()->updateOrCreate(
                ['fcm_token' => $request->fcm_token],
                [
                    'token_id' => $accessTokenId,
                    'platform' => $request->platform ?? 'unknown'
                ]
            );
        }

        return success([
            'message' => 'Login successful',
            'token' => $plainTextToken,
            'user' => $user,
        ], 200);
    }


    public function destroy(Request $request)
    {
        $accessToken = $request->user()->currentAccessToken();

        $request->user()->fcm_tokens()->where('token_id', $accessToken->id)->delete();

        $accessToken->delete();

        return message('Logout successful', 200);
    }
}
