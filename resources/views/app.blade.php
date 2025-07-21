<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    
    <!-- ローディング時のスタイル -->
    <style>
        .initial-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8fafc;
            z-index: 9999;
        }
        
        .loading-content {
            text-align: center;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 0.8s linear infinite;
        }
        
        .loading-text {
            margin-top: 16px;
            color: #6b7280;
            font-size: 14px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* ダークモード対応 */
        @media (prefers-color-scheme: dark) {
            .initial-loading {
                background-color: #111827;
            }
            .loading-spinner {
                border-color: #374151;
                border-top-color: #60a5fa;
            }
            .loading-text {
                color: #9ca3af;
            }
        }
    </style>
    
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
        <div class="initial-loading">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div class="loading-text">読み込み中...</div>
            </div>
        </div>
    </div>
</body>
</html>