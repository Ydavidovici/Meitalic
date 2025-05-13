<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    /**
     * Endpoint for Stripe to send webhook events
     */
    public function handleWebhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $intent = $event->data->object;
                $payment = Payment::where('stripe_payment_id', $intent->id)->first();
                if ($payment && $payment->status !== 'succeeded') {
                    $payment->update(['status' => 'succeeded']);
                    $order = $payment->order;
                    if ($order && $order->status !== 'paid') {
                        $order->update(['status' => 'paid']);
                    }
                }
                break;

            case 'payment_intent.payment_failed':
                $intent = $event->data->object;
                $payment = Payment::where('stripe_payment_id', $intent->id)->first();
                if ($payment && $payment->status !== 'failed') {
                    $payment->update(['status' => 'failed']);
                    $order = $payment->order;
                    if ($order && $order->status !== 'payment_failed') {
                        $order->update(['status' => 'payment_failed']);
                    }
                }
                break;

            case 'charge.refunded':
                $charge = $event->data->object;
                $payment = Payment::where('stripe_payment_id', $charge->payment_intent)->first();
                if ($payment && $payment->status !== 'refunded') {
                    $payment->update(['status' => 'refunded']);
                    $order = $payment->order;
                    if ($order && $order->status !== 'refunded') {
                        $order->update(['status' => 'refunded']);
                    }
                }
                break;

            default:
                // no-op for other event types
                break;
        }

        return response('Webhook Handled', Response::HTTP_OK);
    }

    /**
     * Record a payment when an order is placed (called from CheckoutController)
     */
    public function recordPayment(Order $order, string $stripePaymentId, int $amount)
    {
        return Payment::create([
            'order_id'          => $order->id,
            'stripe_payment_id' => $stripePaymentId,
            'amount'            => $amount,
            'status'            => 'pending',
        ]);
    }
}
