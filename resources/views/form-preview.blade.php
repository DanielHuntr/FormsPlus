<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: #f1f5f9;
            padding: 48px 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
        }
        .preview-banner {
            max-width: 680px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            letter-spacing: .02em;
        }
        .preview-banner svg { opacity: .6; }
        .preview-card {
            max-width: 680px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.04);
        }
        /* Base form layout — overridden by theme Tailwind classes */
        .flexible-form__fields { display: flex; flex-wrap: wrap; gap: 1rem; }
        .flexible-form__field  { box-sizing: border-box; width: var(--field-width, 100%); }
        .flexible-form__submit { margin-top: 1.25rem; }
        input[name="_token"], input[name="_redirect"], input[name="_error_redirect"] { display: none !important; }
    </style>
</head>
<body>
    <p class="preview-banner">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Preview — reflects saved fields &amp; theme settings
    </p>
    <div class="preview-card">
        @include('forms-plus::partials.form')
    </div>
</body>
</html>
