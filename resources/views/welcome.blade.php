<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome | {{ config('app.name', 'Laravel') }}</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        p {
            font-size: 1.1rem;
            margin-bottom: 25px;
            color: #f1f1f1;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-login {
            background: #4f46e5;
            color: #fff;
        }

        .btn-login:hover {
            background: #3730a3;
        }

        .btn-register {
            background: #ec4899;
            color: #fff;
        }

        .btn-register:hover {
            background: #be185d;
        }

        footer {
            position: absolute;
            bottom: 15px;
            text-align: center;
            width: 100%;
            font-size: 0.9rem;
            color: #f1f1f1;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>🚀 Welcome to {{ config('app.name', 'Laravel') }}</h1>
        <p>Your Admin is ready. Please Login!</p>
        <div>
            <div>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('admin/dashboard') }}" class="btn btn-login">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-register">Login</a>
                    @endauth
                @endif
            </div>
            {{-- @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn-register">Register</a>
            @endif --}}
        </div>
    </div>

    <footer>
        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </footer>
</body>

</html>
