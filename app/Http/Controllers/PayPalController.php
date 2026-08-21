<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\PayPalCheckoutRequest;
use App\Services\Payment\PayPal\PayPalService;
use Illuminate\Http\Request;

class PayPalController extends Controller
{
    public function __construct(protected PayPalService $payPalService) {}

    public function checkout(PayPalCheckoutRequest $request)
    {
        $validated = $request->validated();
        return success($this->payPalService->checkout(
            $request->user(),
            $validated['payment_request_id'],
            $validated['currency_id'],
        ));
    }

    public function handleSuccess(Request $request)
    {
        $validated = $request->validate(['token' => 'required|string']);
        return $this->payPalService->captureOrder($validated['token']);
    }

    public function handleCancel(Request $request)
    {
        $orderId = $request->query('token');
        if ($orderId) {
            $this->payPalService->cancel((string) $orderId);
        }

        return response()->json(['status' => 'cancelled', 'message' => __('messages.payment_cancelled')], 200);
    }
}
