{{-- resources/views/emails/orders/receipt.blade.php --}}
@component('mail::message')
    # Thank you for your order!

    **Order #{{ $order->id }}**
    Placed on {{ $order->created_at->toDayDateTimeString() }}

    @component('mail::table')
        | Item       | Qty  | Price  |
        | ---------- | ---- | ------ |
        @foreach($order->orderItems as $item)
            | {{ $item->name }} | {{ $item->quantity }} | ${{ number_format($item->price * $item->quantity,2) }} |
        @endforeach
    @endcomponent

    **Subtotal:** ${{ number_format($order->orderItems->sum(fn($i)=>$i->price*$i->quantity),2) }}
    @if($order->meta['discount'] ?? false)
        **Discount:** -${{ number_format($order->meta['discount'],2) }}
    @endif
    **Total:** ${{ number_format($order->total,2) }}

    Thanks for shopping with us!
@endcomponent
