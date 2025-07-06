<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>React Test - 企業影響力ダッシュボード</title>
    
    @vite('resources/js/app.tsx')
</head>
<body>
    <div id="root">
        <p style="padding: 20px; color: red; font-size: 18px;">
            📦 React が読み込まれていません...
        </p>
    </div>
</body>
</html>