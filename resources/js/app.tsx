import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import App from './components/App';
import './styles/app.css';

console.log('🚀 フル機能アプリケーション初期化開始');

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
            console.log('✅ 企業影響力ダッシュボード アプリケーション初期化完了');
        } catch (error) {
            console.error('❌ アプリケーション初期化エラー:', error);
        }
    } else {
        console.error('❌ root 要素が見つかりません');
    }
}

// DOM読み込み完了時に初期化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}