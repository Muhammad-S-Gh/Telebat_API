<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Traits\SmsOtp;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Storage;

class ProfileController extends Controller
{
    use SmsOtp;

    public function index(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user' => $user
        ]);
    }


    public function update(ProfileUpdateRequest $request)
    {
        $update_email = true;
        $user = $request->user();
        $validated = $request->validated();
        $user->fill($validated);

        if ($request->hasFile('profile_picture')) {
            $user->storeProfilePicture($request->file('profile_picture'));
        }

        if ($user->isDirty('email')) {
            $user->update([
                'email_verified_at' => null,
                'updated_at' => Carbon::now()
            ]);
            event(new Registered($user, $update_email));
        }

        if ($request->phone_number) {
            $user->phone()->update([
                'phone_number' => $request->phone_number,
                'phone_number_verified_at' => null,
                'updated_at' => Carbon::now(),
            ]);
            $user->verified_at = null;
            $this->fullfill($user, "Please use this code to verify your new phone number!! (Updated)", $request->phone_number);
        }

        $user->save();

        return success(['user' => $user->fresh()], 200, 'Profile updated successfully');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        if (Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $user->tokens()->delete();
        $user->delete();

        return message('Sorry to see you go :(', 200);
    }
}
