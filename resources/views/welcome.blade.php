<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/ico" href="{{ asset('images/logo-EOB-only.ico') }}">
        <title>PTEO Application</title>
        <!-- Fonts -->
        <link href="{{ asset('assets/fonts/Nunito/static/Nunito-Regular.ttf') }}" rel="stylesheet">
        <link href="{{ asset('assets/fonts/Nunito/static/Nunito-Bold.ttf') }}" rel="stylesheet">
        <link href="{{ asset('assets/fonts/Nunito/static/Nunito-SemiBold.ttf') }}" rel="stylesheet">
        <!-- Styles -->
        <link href="{{ asset('assets/css/normalize.css') }}" rel="stylesheet">

        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
            #res_img {
                background: url('{{ asset('images/Logo EOB with name - cover.png')}}');
                width: 100%;
                height: 500px;
                background-repeat: no-repeat;
                background-size: cover;
                background-position: center;
            }
            @media screen and (min-width:300px) and (max-width:500px) {
                #res_img {
                    width: 100%;
                    height: 350px;
                }
            }
            #res_button {
                padding-top:5px
            }
            @media screen and (min-width:300px) and (max-width:500px) {
                #res_button {
                    margin-top:-120px
                }
            }
            .login-button {
                display: inline-block;
                padding: 10px 20px;
                height: 35px;
                background-color: #1ca11d; /* Adjust the color as needed */
                color: #fff;
                border-radius: 5px;
                text-decoration: none;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }
            .login-button:hover {
                background-color: #0b6d0c; /* Adjust the hover color as needed */
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="relative flex items-top justify-center min-h-screen bg-white-100 dark:bg-white-900 sm:items-center py-4 sm:pt-0">
            <div class="row flex justify-center" id="res_img" style="padding-top:350px">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/home') }}" class="login-button" id="res_button">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="login-button" id="res_button">Log in</a>
                    @endauth
                @endif
            </div>
        </div>
    </body>
</html>
