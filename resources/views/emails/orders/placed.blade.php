{{-- resources/views/emails/orders/placed.blade.php --}}
@component('mail::message')
    # New Order Placed

    Order **#{{ $order->id }}** was just placed by {{ $order->email }}.

    @component('mail::table')
        | Item       | Qty  | Price  |
        | ---------- | ---- | ------ |
        @foreach($order->orderItems as $item)
            | {{ $item->name }} | {{ $item->quantity }} | ${{ number_format($item->price * $item->quantity,2) }} |
        @endforeach
    @endcomponent

    **Total:** ${{ number_format($order->total,2) }}

@endcomponent
