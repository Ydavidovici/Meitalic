<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        // Validate request input (shipping_address and cart items)
        $data = $request->validate([
            'shipping_address' => 'required|string',
            'cart' => 'required|array', // Each item should have product_id and quantity
        ]);

        // Wrap in a transaction for data consistency
        DB::beginTransaction();
        try {
            // Create the order with temporary total=0 and pending status
            $order = Order::create([
                'user_id'          => auth()->id(),
                'shipping_address' => $data['shipping_address'],
                'total'            => 0, // Will be updated below
                'status'           => 'pending',
            ]);

            $total = 0;
            // Process each cart item
            foreach ($data['cart'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                ]);
                $total += $product->price * $item['quantity'];
            }

            // Update the order total
            $order->update(['total' => $total]);

            // --- Initiate Payment via Stripe ---
            // Set your Stripe secret key
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create a PaymentIntent with the total (amount in cents for Stripe)
            $paymentIntent = PaymentIntent::create([
                'amount'   => (int) ($total * 100), // convert dollars to cents
                'currency' => 'usd',
                // Optional: you can pass metadata to link this PaymentIntent to the order
                'metadata' => ['order_id' => $order->id],
            ]);

            // Retrieve the PaymentIntent ID (simulate this as our stripe_payment_id)
            $stripePaymentId = $paymentIntent->id;

            // Record the payment (status pending) via PaymentController helper method
            app(PaymentController::class)->recordPayment($order, $stripePaymentId, $total);

            DB::commit();

            // Redirect to a page where the user can confirm payment (or show a message)
            // In a full integration, you might redirect to a Stripe Checkout page or embed Stripe Elements.
            return redirect()->route('order.show', $order->id)
                ->with('success', 'Order placed. Please complete your payment.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors('Order processing failed: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $order = Order::findOrFail($id);
        return view('pages.orders.show', compact('order'));
    }
}
