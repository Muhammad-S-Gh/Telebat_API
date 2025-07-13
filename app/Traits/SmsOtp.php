<?php

namespace App\Traits;

use App\Models\Phone;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait SmsOtp
{
    protected int $minutes = 60;

    protected function fullfill(User $user, string $message, string $phone_number)
    {
        $code = $this->generate(4);
        $this->set($user, $code);
        return $this->send($user, $code, $message, $phone_number);
    }

    protected function generate(int $digits)
    {
        return rand(pow(10, $digits - 1), pow(10, $digits) - 1);
    }


    protected function set(User $user, int $otp)
    {
        $phone = $user->phone()->first();

        $phone->update([
            'phone_number_otp_code' => Hash::make($otp),
            'phone_number_otp_expired_date' => Carbon::now()->addMinutes($this->minutes),
        ]);
    }


    protected function send(User $user, int $otp, $message, string $phone_number)
    {
        $key = config('sms.gateway.api_key');
        $url = config('sms.gateway.url');

        Log::info(optional($user->phone)->phone_number);

        $response = Http::withOptions([
            'verify' => false,
        ])
            ->withHeaders([
                'Authorization' => $key,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                "message" => "$message $otp",
                "to" => $phone_number,
            ]);

        return json_decode($response);
    }

    protected function verifyOtp(User $user, string $otp)
    {
        $phone = $user->phone()->first();
        if (Hash::check($otp, $phone->phone_number_otp_code) and $phone->phone_number_otp_expired_date >= Carbon::now()) {
            $phone->update([
                'phone_number_verified_at' => Carbon::now()
            ]);
            return true;
        }
        return false;
    }
}
