<x-mail::message>
# Hello {{ $order->user->name }},

<h2>Your Order has been delivered, enjoy</h2>

<h4>Find the details of your order below.</h4>

<x-mail::panel>
<x-mail::table>
| Menu   | Price    |
| :----- | --------:|
@foreach ($order->orderItems as $order_item)
| <strong>{{ $order_item->quantity }}x</strong> {{ $order_item->menu->title }} | {{ $order->user->currency }}{{ number_format($order_item->subtotal, 2) }} |
@endforeach
</x-mail::table>
<hr>
<x-mail::table>
| Products  | {{ $order->user->currency }}{{ number_format($order->menu_total, 2) }}    |
| :-------- | -------------------------------:|
</x-mail::table>
@if ($order->delivery)
<x-mail::table>
| Delivery  | {{ $order->user->currency }}{{ number_format($order->delivery_fee, 2) }}    |
| :-------- | -------------------------------:|
</x-mail::table>
@endif
<x-mail::table>
| <strong>Total</strong>  | <strong>{{ $order->user->currency }}{{ number_format($order->total_amount, 2) }}</strong> |
| :---------------------- | -----------------------------------------------------------------------------------------:|
</x-mail::table>
</x-mail::panel>

<small>Delivery From</small>
<h2>{{ $order->restaurant->name }}</h2>
<h4>{{ $order->restaurant->address }}</h4>
<hr>
<small>Delivery To</small>
<h4>{{ $order->delivery_address }}</h4>
<hr>
@if ($order->rider_id)
<small>Delivered By</small>
<h2>{{ $order->rider->name }}</h2>
@endif

Regards,<br>
<strong>{{ config('app.name') }}</strong>
</x-mail::message>
