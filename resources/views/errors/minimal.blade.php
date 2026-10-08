@php
    $code = trim($__env->yieldContent('code')) ?: '500';
    [$title, $message] = match ($code) {
        '401' => ['Inloggen nodig', 'Log in om deze pagina te bekijken.'],
        '403' => ['Geen toegang', 'Je account heeft geen toestemming voor deze pagina of actie.'],
        '404' => ['Niet gevonden', 'Deze pagina of dit apparaat is niet beschikbaar.'],
        '409' => ['Actie niet mogelijk', 'Het apparaat is inmiddels gewijzigd of gereserveerd. Ga terug naar het overzicht en probeer opnieuw.'],
        '419' => ['Sessie verlopen', 'Je sessie is verlopen. Vernieuw de pagina en probeer opnieuw.'],
        '429' => ['Te veel verzoeken', 'Wacht even en probeer het daarna opnieuw.'],
        '503' => ['Tijdelijk niet beschikbaar', 'Reboot is tijdelijk niet beschikbaar. Probeer het later opnieuw.'],
        default => ['Er ging iets mis', 'De actie kon niet worden afgerond. Probeer het later opnieuw.'],
    };
@endphp
<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} | Reboot</title>
        <style>
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1rem; background: #F3F4F6; color: #111827; font-family: system-ui, sans-serif; }
            main { width: 100%; max-width: 36rem; padding: 2rem; border: 1px solid #e5e7eb; border-radius: 1rem; background: white; }
            .code { color: #047857; font-weight: 700; }
            h1 { font-size: 1.75rem; }
            p { line-height: 1.6; }
            a { display: inline-block; margin-top: 1rem; padding: .75rem 1rem; border-radius: .5rem; background: #10B981; color: #111827; font-weight: 600; text-decoration: none; }
            a:focus-visible { outline: 3px solid #111827; outline-offset: 3px; }
        </style>
    </head>
    <body>
        <main>
            <p class="code">Reboot ? {{ $code }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a href="{{ route('home') }}">Terug naar Reboot</a>
        </main>
    </body>
</html>
