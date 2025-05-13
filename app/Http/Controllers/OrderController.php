<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Jobs\NotifyReturnReceived;
use App\Jobs\ProcessReturn;
use App\Jobs\ProcessCancellation;
use App\Mail\OrderCancelledMailable;
use App\Mail\ReturnRequestMailable;

class OrderController extends Controller
{
    /**
     * Send a mailable to the customer (queued).
     */
    protected function notifyCustomer(string $mailableClass, Order $order)
    {
        if ($order->user && $order->user->email) {
            Mail::to($order->user->email)
                ->queue(new $mailableClass($order));
        }
    }

    /**
     * Send a mailable to the admin (queued).
     */
    protected function notifyAdmin(string $mailableClass, Order $order)
    {
        Mail::to(config('mail.admin_address', 'admin@example.com'))
            ->queue(new $mailableClass($order));
    }

    /**
     * Customer requests a return.
     */
    public function requestReturn(Request $request, Order $order)
    {
        $order->update(['status' => 'pending_return']);
        NotifyReturnReceived::dispatch($order);
        return back()->with('success', 'Your return request has been submitted.');
    }

    /**
     * Admin approves a return.
     */
    public function approveReturn(Request $request, Order $order)
    {
        // only admins may approve returns
        abort_if(! $request->user()?->is_admin, 403);

        if ($order->status !== 'pending_return') {
            return back()->withErrors('This order is not pending return.');
        }

        $order->update(['status' => 'returned']);
        ProcessReturn::dispatch($order);

        return back()->with('success', 'Return approved and refund is being processed.');
    }

    /**
     * Admin rejects a return.
     */
    public function rejectReturn(Request $request, Order $order)
    {
        // only admins may reject returns
        abort_if(! $request->user()?->is_admin, 403);

        if ($order->status !== 'pending_return') {
            return back()->withErrors('This order is not pending return.');
        }

        $order->update(['status' => 'return_rejected']);
        return back()->with('success', 'Return rejected.');
    }

    /**
     * Customer cancels an order (if not yet shipped).
     */
    public function cancel(Request $request, Order $order)
    {
        if (in_array($order->status, ['shipped', 'delivered'])) {
            return back()->withErrors('Cannot cancel an order that has already shipped.');
        }

        $order->update(['status' => 'canceled']);

        // notify customer & kick off refund job
        $this->notifyCustomer(OrderCancelledMailable::class, $order);
        ProcessCancellation::dispatch($order);

        return back()->with('success', 'Your order has been canceled; refund is processing.');
    }

    /**
     * Show a single order.
     */
    public function show(Order $order)
    {
        return view('pages.orders.show', compact('order'));
    }
}
