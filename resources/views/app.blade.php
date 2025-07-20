<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    
    @production
        @php
            $manifestPath = public_path('build/manifest.json');
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                $entrypoint = $manifest['resources/js/app.tsx'] ?? null;
            }
        @endphp
        @if(isset($entrypoint))
            @if(isset($entrypoint['css']))
                <link rel="stylesheet" href="{{ asset('build/' . $entrypoint['css'][0]) }}">
            @endif
            <script type="module" src="{{ asset('build/' . $entrypoint['file']) }}"></script>
        @else
            @vite('resources/js/app.tsx')
        @endif
    @else
        @vite('resources/js/app.tsx')
    @endproduction
</head>
<body>
    <div id="root">
        <p style="padding: 20px; color: red; font-size: 18px;">
            📦 React が読み込まれていません...
        </p>
    </div>
</body>
</html>