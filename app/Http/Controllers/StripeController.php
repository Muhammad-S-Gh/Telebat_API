<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\CreatePaymentIntentRequest;
use App\Services\Payment\Stripe\StripeService;
use Illuminate\Http\Request;

class StripeController extends Controller
{
    public function __construct(protected StripeService $stripeService) {}

    public function createPaymentIntent(CreatePaymentIntentRequest $request)
    {
        return success($this->stripeService->createPaymentIntent($request));
    }

    public function handleWebhook(Request $request)
    {
        return $this->stripeService->handleWebhook($request);
    }
}
