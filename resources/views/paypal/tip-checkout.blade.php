<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <meta name="csrf-token" content="{{ csrf_token() }}" />
   <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
   <title>Moboeats</title>
   <style>
    .checkout-img {
        width: 120px;
        height: 120px;
        object-fit: contain;
        margin-right: auto;
        margin-left: auto;
        padding: 5px;
        width: 100%;
    }
   </style>
</head>
<body style="background: #2E5945">
    <div class="container">
        <img src="{{ asset('assets/img/1024.png') }}" alt="" class="checkout-img">
        <h1>Send a Tip to the Rider</h1>
        <div class="row" style="color: white">
            <div class="col-6">
                <h3>Rider:</h3>
                <h2>Amount:</h2>
            </div>
            <div class="col-6 text-end">
                <h3>{{ $order->rider->name }}</h3>
                <h2>{{ Str::upper(config('paypal.currency')) }} {{ $total_amount }}</h2>
            </div>
        </div>
        <div id="paypal-button-container" style="max-width:500pxx;" class="mx-auto"></div>
    </div>
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
        return fetch("/api/v1/order/tip/payment/create-tip-paypal-order", {
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
            console.log("Success"+ data)
            // This function captures the funds from the transaction.
            return fetch("/api/v1/order/tip/payment/capture-tip-paypal-order", {
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
                    amount: total_amount
                })
            })
            .then((response) => response.json())
            .then((details) => {
                // This function shows a transaction success message to your buyer.
                window.location.href = "{{ route('paypal.checkout.success') }}"
            });
        },
        onCancel(data) {
            console.log('Cancelled: '+data);
            window.location.href = "{{ route('paypal.checkout.failed') }}"
        },
        onError(err) {
            console.log("Error: " + err);
            window.location.href = "{{ route('paypal.checkout.failed') }}"
        }
      }).render('#paypal-button-container');
   </script>
</body>
</html>
