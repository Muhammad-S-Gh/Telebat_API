<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Stripe\PaymentIntent;

class PaymentService
{
    protected function savePayment(User|Authenticatable $user, PaymentIntent $paymentIntent, PaymentRequest $pr, string $currencyCode, ?string $status = null): void
    {
        Payment::create([
            'user_id' => $user->id,
            'payment_request_id' => $pr->id,
            'stripe_client_secret' => $paymentIntent->client_secret,
            'model_id' => $pr->id,
            'model_type' => get_class($pr),
            'status' => $status ?? Payment::status()->pending,
            'payment_method' => $paymentIntent->payment_method_types[0],
            'price' => $pr->price,
            'currency' => strtolower($currencyCode)
        ]);
    }


    public function getInvoice(Payment $payment, bool $download)
    {
        $data = [
            'payment_id' => $payment->id,
            'payment_request_id' => $payment->paymentRequest->id,
            'completed_at' => $payment->completed_at,
            'price' => $payment->price,
            'payment_method' => $payment->payment_method,
            'currency' => $payment->currency,
            'title' => $payment->paymentRequest->title,
            'description' => $payment->paymentRequest->description,
        ];

        return exportPdf('pdf.invoice', $data, $download, 'invoice.pdf');
    }
}
