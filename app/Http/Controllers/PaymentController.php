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
    // Endpoint for Stripe to send webhook events
    public function handleWebhook(Request $request)
    {
        $payload    = $request->getContent();
        $sigHeader  = $request->header('Stripe-Signature');
        $secret     = config('services.stripe.webhook_secret'); // set this in .env

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        // Handle the event type
        switch ($event->type) {
            case 'payment_intent.succeeded':
                // Find the payment record and update its status
                $paymentIntent = $event->data->object;
                Payment::where('stripe_payment_id', $paymentIntent->id)
                    ->update(['status' => 'succeeded']);
                // Optionally, update the order status to 'paid'
                break;
            // Add other event types as needed
            default:
                // Unexpected event type
                break;
        }

        return response('Webhook Handled', 200);
    }

    // Record a payment when an order is placed (called from OrderController)
    public function recordPayment(Order $order, $stripePaymentId, $amount)
    {
        return Payment::create([
            'order_id'          => $order->id,
            'stripe_payment_id' => $stripePaymentId,
            'amount'            => $amount,
            'status'            => 'pending', // will be updated by webhook
        ]);
    }
}
