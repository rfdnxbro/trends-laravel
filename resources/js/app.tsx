import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import App from './components/App';

const queryClient = new QueryClient();

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('root');

    if (container) {
        try {
            const root = createRoot(container);
            root.render(
                <React.StrictMode>
                    <QueryClientProvider client={queryClient}>
                        <BrowserRouter>
                            <App />
                        </BrowserRouter>
                    </QueryClientProvider>
                </React.StrictMode>
            );
            console.log('React アプリケーションが正常に初期化されました');
        } catch (error) {
            console.error('React アプリケーションの初期化に失敗:', error);
        }
    } else {
        console.error('root 要素が見つかりません');
    }
});