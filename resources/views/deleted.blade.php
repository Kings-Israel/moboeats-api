<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moboeats</title>
  <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
  <style>
    body {
      background: #2E5945;
    }
    .container {
      text-align: center;
      margin-top: 50px;
    }
    .success-text {
      margin: 0 20px 0 20px;
    }
    .success-text h3 {
        color: white;
        font-family: system-ui,
            -apple-system, /* Firefox supports this but not yet `system-ui` */
            'Segoe UI',
            Roboto,
            Helvetica,
            Arial,
            sans-serif,
            'Apple Color Emoji',
            'Segoe UI Emoji';
    }

    .checkmark-circle .white-space {
      background-color: white;
      height: 10px;
      left: 32px;
      position: absolute;
      top: 85px;
      width: 50px;
      z-index: 2;
    }

    .checkout-img {
        width: 120px;
        height: 120px;
        object-fit: contain;
        margin-right: auto;
        margin-left: auto;
        padding: 5px;
        width: 100%;
    }

    .delete-btn {
        border-radius: 5px;
        background: #F8C410;
        border: 2px solid #F8C410;
        font-weight: 600;
        font-size: 18px;
    }
  </style>
</head>
<body class="font-comfortaa">
  <div class="container">
    <img src="{{ asset('assets/img/1024.png') }}" alt="" class="checkout-img">
    <div class="success-text">
      <h3>Your account has been deleted successfully.</h3>
    </div>
  </div>
</body>
</html>
