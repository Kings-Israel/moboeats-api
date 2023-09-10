<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <meta name="csrf-token" content="{{ csrf_token() }}" />
   <title>Moboeats</title>
</head>
<body>
   <div id="paypal-button-container" style="max-width:500pxx;"></div>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
   <script src="https://www.paypal.com/sdk/js?client-id=AXrv8Dyv2_hizwzHleDQSomA3PWuOR-FS9ZZFtmW2iO5Nch9E7ZUT9wlL99UW704mjh3TCroj28xNisa&components=buttons&intent=capture&currency=GBP&debug=false"></script>
   <script>
      const total_amount = {!! json_encode($total_amount) !!}
      const currency = {!! json_encode(config('paypal.currency')) !!}
      const checkout_id = {!! json_encode($checkout_id) !!}

      let _token = $('meta[name="csrf-token"]').attr('content')

      //This function displays payment buttons on your web page.
      paypal.Buttons({
        createOrder() {
        // This function sets up the details of the transaction, including the amount and line item details.
        return fetch("/api/v1/order/payment/create-paypal-order", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify({
                _token: $('meta[name="csrf-token"]').attr('content'),
                order_id: checkout_id,
                total_price: total_amount,
            })
        })
        .then(response => response.json())
        .then(order_id => order_id.order_id);
        },
        onApprove(data) {
        // This function captures the funds from the transaction.
        return fetch("/api/v1/order/payment/capture-paypal-order", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify({
            _token: $('meta[name="csrf-token"]').attr('content'),
                order_id: data.orderID,
                transaction_id: data.paymentID,
                checkout_id: checkout_id,
            })
        })
        .then((response) => response.json())
        .then((details) => {
            // This function shows a transaction success message to your buyer.
            window.location.href = "{{ route('paypal.checkout.success') }}"
        });
        },
        onCancel(data) {
        window.location.href = "{{ route('paypal.checkout.failed') }}"
        },
        onError(err) {
        window.location.href = "{{ route('paypal.checkout.failed') }}"
        }
      }).render('#paypal-button-container');
   </script>
</body>
</html>
