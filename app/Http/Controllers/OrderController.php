<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Mail\OrderReceiptMail;
use App\Mail\AdminOrderNotificationMail;

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        Log::info('📦 placeOrder called', ['user_id' => auth()->id(), 'payload' => $request->all()]);

        $data = $request->validate([
            'shipping_address' => 'required|string',
            'cart' => 'required|array',
            'cart.*.product_id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id'          => auth()->id(),
                'shipping_address' => $data['shipping_address'],
                'total'            => 0,
                'status'           => 'pending',
            ]);

            $total = 0;

            foreach ($data['cart'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $lineTotal = $product->price * $quantity;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'quantity'   => $quantity,
                    'price'      => $product->price,
                    'total'      => $lineTotal,
                ]);

                $total += $lineTotal;
            }

            $order->update(['total' => $total]);

            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount'   => (int) round($total * 100),
                'currency' => 'usd',
                'metadata' => ['order_id' => $order->id],
            ]);

            app(PaymentController::class)->recordPayment($order, $paymentIntent->id, $total);

            DB::commit();

            if ($order->user && $order->user->email) {
                Mail::to($order->user->email)->send(new OrderReceiptMail($order));
            }

            $adminEmail = config('mail.admin_address', 'admin@example.com');
            Mail::to($adminEmail)->send(new AdminOrderNotificationMail($order));

            return redirect()->route('order.show', $order->id)
                ->with('success', 'Order placed. Please complete your payment.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order placement failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors('Order processing failed: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $order = Order::findOrFail($id);
        return view('pages.orders.show', compact('order'));
    }
}
