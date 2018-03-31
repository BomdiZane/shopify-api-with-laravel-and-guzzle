<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title>@yield('title') - {{config('app.name')}}</title>
        <link rel="stylesheet" href='css/app.css' />
        <link rel="stylesheet" href=@yield('stylesheet') />
    </head>
    <body>
        <div class="container">
            @yield('body')
        </div>
    </body>
</html>