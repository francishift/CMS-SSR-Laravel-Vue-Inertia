<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Página no encontrada</title>
    @php
        $errorThemeSettings = \App\Models\SiteSetting::cached();
    @endphp
    <script>{!! \App\Support\CspInlineScripts::publicThemeScript($errorThemeSettings->frontend_theme_mode ?? 'light', $errorThemeSettings->frontend_default_theme ?? 'light') !!}</script>
    <style>
        :root {
            --bg-light: #ffffff;
            --text-light: #18181b;
            --bg-dark: #09090b;
            --text-dark: #f4f4f5;
        }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        html.dark body {
            background-color: var(--bg-dark);
            color: var(--text-dark);
        }
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 32rem;
        }
        .code {
            font-size: 6rem;
            font-weight: 900;
            letter-spacing: -0.05em;
            background: linear-gradient(135deg, #10b981, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }
        h1 {
            font-size: 1.875rem;
            font-weight: 800;
            margin-top: 0.5rem;
            margin-bottom: 0.75rem;
        }
        p {
            font-size: 1rem;
            opacity: 0.75;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        a {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff;
            font-weight: 600;
            padding: 0.875rem 1.75rem;
            border-radius: 0.75rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        a:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">404</div>
        <h1>Página no encontrada</h1>
        <p>Lo sentimos, la página que buscas no existe, ha sido movida o la dirección tipeada es incorrecta.</p>
        <a href="/">Volver a la Página Principal</a>
    </div>
</body>
</html>
