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
    .checkmark-circle {
      width: 140px;
      height: 140px;
      position: relative;
      display: inline-block;
      vertical-align: top;
    }
    .checkmark-circle .background {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      background: white;
      border: 2px solid #F8C410;
      position: absolute;

      -webkit-transition: all 220ms linear;
      -moz-transition: all 220ms linear;
      transition: all 220ms linear;
    }

    .checkmark-circle .checkmark {
      border-radius: 5px;
    }
    .checkmark-circle .checkmark.draw:after {
      -webkit-animation-delay: 300ms;
      -moz-animation-delay: 300ms;
      animation-delay: 300ms;
      -webkit-animation-duration: 800ms;
      -moz-animation-duration: 800ms;
      animation-duration: 800ms;
      -webkit-animation-timing-function: ease;
      -moz-animation-timing-function: ease;
      animation-timing-function: ease;
      -webkit-animation-name: checkmark;
      -moz-animation-name: checkmark;
      animation-name: checkmark;
      -webkit-transform: scaleX(-1) rotate(135deg);
      -moz-transform: scaleX(-1) rotate(135deg);
      -ms-transform: scaleX(-1) rotate(135deg);
      -o-transform: scaleX(-1) rotate(135deg);
      transform: scaleX(-1) rotate(135deg);
      -webkit-animation-fill-mode: forwards;
      -moz-animation-fill-mode: forwards;
      animation-fill-mode: forwards;
    }
    .checkmark-circle .checkmark:after {
      opacity: 1;
      height: 75px;
      width: 37.5px;
      -webkit-transform-origin: left top;
      -moz-transform-origin: left top;
      -ms-transform-origin: left top;
      -o-transform-origin: left top;
      transform-origin: left top;
      border-right: 11px solid #F8C410;
      border-top: 11px solid #F8C410;
      border-radius: 2.5px !important;
      content: '';
      left: 25px;
      top: 75px;
      position: absolute;
    }

    @-webkit-keyframes checkmark {
      0% {
        height: 0;
        width: 0;
        opacity: 1;
      }
      20% {
        height: 0;
        width: 37.5px;
        opacity: 1;
      }
      40% {
        height: 75px;
        width: 37.5px;
        opacity: 1;
      }
      100% {
        height: 75px;
        width: 37.5px;
        opacity: 1;
      }
    }
    @-moz-keyframes checkmark {
      0% {
        height: 0;
        width: 0;
        opacity: 1;
      }
      20% {
        height: 0;
        width: 37.5px;
        opacity: 1;
      }
      40% {
        height: 75px;
        width: 37.5px;
        opacity: 1;
      }
      100% {
        height: 75px;
        width: 37.5px;
        opacity: 1;
      }
    }
    @keyframes checkmark {
      0% {
        height: 0;
        width: 0;
        opacity: 1;
      }
      20% {
        height: 0;
        width: 37.5px;
        opacity: 1;
      }
      40% {
        height: 75px;
        width: 37.5px;
        opacity: 1;
      }
      100% {
        height: 75px;
        width: 37.5px;
        opacity: 1;
      }
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
  </style>
</head>
<body>
  <div class="container">
    <img src="{{ asset('assets/img/1024.png') }}" alt="" class="checkout-img">
    <div class="checkmark-circle">
      <div class="background"></div>
      <div class="checkmark draw"></div>
    </div>
    <div class="success-text">
      <h3>The transaction was successful. Go to the app to view your order.</h3>
    </div>
  </div>
</body>
</html>
