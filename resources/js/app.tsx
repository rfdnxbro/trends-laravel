import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import App from './components/App';
import './styles/app.css';

// React Query クライアント設定
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: 1,
            refetchOnWindowFocus: false,
        },
    },
});

// アプリケーションコンポーネント
function AppWithProviders() {
    return (
        <QueryClientProvider client={queryClient}>
            <BrowserRouter>
                <App />
            </BrowserRouter>
        </QueryClientProvider>
    );
}

// DOM初期化
function initApp() {
    const container = document.getElementById('root');
    
    if (container) {
        try {
            const root = createRoot(container);
            root.render(<AppWithProviders />);
        } catch {
            // エラーハンドリングは必要に応じて実装
        }
    }
    // root要素が見つからない場合は静かに終了
}

// DOM読み込み完了時に初期化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}