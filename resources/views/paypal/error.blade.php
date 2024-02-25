<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moboeats</title>
  <style>
    body {
      background: #2E5945;
    }
    .container {
      text-align: center;
      margin-top: 50px;
    }
    .success-text {
      color: #fff;
      width: 100%;
      margin-left: auto;
      margin-right: auto;
    }

    .success-text h3, h4 {
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

    .checkout-img {
        width: 120px;
        height: 120px;
        object-fit: contain;
        margin-right: auto;
        margin-left: auto;
        padding: 5px;
        width: 100%;
    }

    .close-container{
      position: relative;
      width: 28px;
      height: 50px;
      margin-top: 50px;
      margin-left: auto;
      margin-right: auto;
      cursor: pointer;
    }

    .leftright{
      height: 4px;
      width: 50px;
      position: absolute;
      margin-top: 24px;
      background-color: #F4A259;
      border-radius: 2px;
      transform: rotate(45deg);
      transition: all .3s ease-in;
    }

    .rightleft{
      height: 4px;
      width: 50px;
      position: absolute;
      margin-top: 24px;
      background-color: #F4A259;
      border-radius: 2px;
      transform: rotate(-45deg);
      transition: all .3s ease-in;
    }

    label{
      color: white;
      font-family: Helvetica, Arial, sans-serif;
      font-size: .6em;
      text-transform: uppercase;
      letter-spacing: 2px;
      transition: all .3s ease-in;
      opacity: 0;
    }
    .close{
      margin: 60px 0 0 5px;
      position: absolute;
    }

    .close-container:hover .leftright{
      transform: rotate(-45deg);
      background-color: #F25C66;
    }
    .close-container:hover .rightleft{
      transform: rotate(45deg);
      background-color: #F25C66;
    }
    .close-container:hover label{
      opacity: 1;
    }
  </style>
</head>
<body>
  <div class="container">
    <img src="{{ asset('assets/img/1024.png') }}" alt="" class="checkout-img">
    <div class="close-container">
      <div class="leftright"></div>
      <div class="rightleft"></div>
      <label class="close">close</label>
    </div>
    <div class="success-text">
      <h3>The transaction was cancelled or failed.</h3>
    </div>
    <div class="success-text">
        @if ($message)
            <h4>{{ $message }}</h4>
        @endif
    </div>
  </div>
</body>
</html>
