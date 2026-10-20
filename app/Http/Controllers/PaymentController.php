<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StripeService;
use App\Models\Payment;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected StripeService $stripeService;

    /**
     * Inyección de la capa de servicio (RNF 1)
     */
    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Muestra la vista del formulario de checkout (RF 1.1).
     */
    public function showCheckout()
    {
        return view('checkout');
    }

    /**
     * Crear un PaymentIntent dinámico delegando al StripeService (RF 1.2).
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent(
                (float) $request->amount,
                $request->currency,
                $request->description
            );

            // Guardar registro inmediato en MySQL al crear el pago en checkout (RF 2.1)
            Payment::updateOrCreate(
                ['payment_intent_id' => $paymentIntent->id],
                [
                    'amount' => $request->amount,
                    'currency' => strtoupper($request->currency),
                    'status' => 'succeeded',
                    'description' => $request->description ?? 'Pago desde formulario Checkout',
                ]
            );

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Webhook para capturar la confirmación del pago y guardarlo en MySQL (RF 2.1).
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (\UnexpectedValueException | SignatureVerificationException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;

            // Guardar o actualizar registro en la base de datos MySQL (RF 2.1)
            Payment::updateOrCreate(
                ['payment_intent_id' => $paymentIntent->id],
                [
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => strtoupper($paymentIntent->currency),
                    'status' => $paymentIntent->status,
                    'description' => $paymentIntent->metadata->description ?? null,
                ]
            );

            Log::info("¡Pago exitoso registrado en BD vía Webhook! ID: {$paymentIntent->id}");
        }

        return response()->json(['status' => 'success'], 200);
    }
}