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
        margin-top: 5px;
    }
    .delete-form {
        width: 30%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin: auto;
    }
    .delete-reason {
        border: #F8C410;
        border-radius: 5px;
    }
  </style>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-comfortaa">
  <div class="container">
    <img src="{{ asset('assets/img/1024.png') }}" alt="" class="checkout-img">
    <div class="close-container">
    <div class="leftright"></div>
    <div class="rightleft"></div>
    </div>
    <div class="success-text">
        <h3>Are you sure you want to delete your account?</h3>
    </div>
    <form action="{{ route('delete.confirmation') }}" method="post" class="delete-form">
        @csrf
        <div class="flex flex-col">
            <label class="text-slate-200 text-md font-bold">Full Name</label>
            <input name="name" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
            @if($errors->get('name'))
                <span class="text-red-300 font-bold my-1">{{ $errors->get('name')[0] }}</span>
            @endif
        </div>
        <div class="flex flex-col">
            <label class="text-slate-200 text-md font-bold">Email Address</label>
            <input name="email" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
            @if($errors->get('email'))
                <span class="text-red-300 font-bold my-1">{{ $errors->get('email')[0] }}</span>
            @endif
        </div>
        <div class="flex flex-col">
            <label class="text-slate-200 text-md font-bold">Phone Number</label>
            <input name="phone_number" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
            @if($errors->get('phone_number'))
                <span class="text-red-300 font-bold my-1">{{ $errors->get('phone_number')[0] }}</span>
            @endif
          </div>
        <div class="flex flex-col">
            <label class="text-slate-200 text-md font-bold">Reason for Leaving</label>
            <textarea name="account_delete_reason" id="" rows="5" class="delete-reason">Tell Us Why you are leaving...</textarea>
        </div>
        <button class="btn delete-btn" type="submit">Delete Account</button>
    </form>
  </div>
</body>
</html>
