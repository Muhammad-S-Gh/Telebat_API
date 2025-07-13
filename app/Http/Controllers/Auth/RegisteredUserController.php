<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Phone;
use App\Models\User;
use App\Traits\SmsOtp;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;


class RegisteredUserController extends Controller
{
    use SmsOtp;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'string', 'min:8', 'confirmed', Rules\Password::defaults()],
            'phone_number' => ['required', 'string', 'unique:' . Phone::class],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'role' => ['nullable', Rule::in(['vendor', 'user'])],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')->trim()),
        ]);

        $role = $validated['role'] ?? 'user';
        $user->assignRole($role);

        $user->phone()->create(['phone_number' => $request->phone_number]);
        $update_email = false;

        if ($request->hasFile('profile_picture')) {
            $user->storeProfilePicture($request->file('profile_picture'));
        }

        event(new Registered($user, $update_email)); // automatically invoke the $user->sendEmailVerificationNotification

        return $this->fullfill($user, "Please use the code to verify your phone number !!", $request->phone_number);
    }
}
