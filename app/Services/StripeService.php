<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPaymentIntent(float $amount, string $currency, ?string $description = null): PaymentIntent
    {
        $amountInCents = (int) round($amount * 100);

        return PaymentIntent::create([
            'amount' => $amountInCents,
            'currency' => strtolower($currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'description' => $description ?? 'Pago de prueba',
            ],
        ]);
    }

    public function constructWebhookEvent(string $payload, ?string $sigHeader)
    {
        $endpointSecret = config('services.stripe.webhook_secret');
        return Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
    }
}