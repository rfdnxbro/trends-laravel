<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    
    @if(file_exists(public_path('build/manifest.json')))
        @php
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $entrypoint = $manifest['resources/js/app.tsx'] ?? null;
        @endphp
        @if($entrypoint)
            @if(isset($entrypoint['css']))
                <link rel="stylesheet" href="{{ asset('build/' . $entrypoint['css'][0]) }}">
            @endif
            <script type="module" src="{{ asset('build/' . $entrypoint['file']) }}"></script>
        @else
            @vite('resources/js/app.tsx')
        @endif
    @else
        @vite('resources/js/app.tsx')
    @endif
</head>
<body>
    <div id="root">
        <p style="padding: 20px; color: red; font-size: 18px;">
            📦 React が読み込まれていません...
        </p>
    </div>
</body>
</html>