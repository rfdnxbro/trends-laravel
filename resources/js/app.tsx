import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/App';

console.log('app.tsx が読み込まれました');

// 複数のタイミングで初期化を試行
function initApp() {
    console.log('initApp 関数が呼ばれました');
    const container = document.getElementById('root');
    console.log('container:', container);

    if (container) {
        try {
            console.log('React アプリケーションを初期化中...');
            const root = createRoot(container);
            root.render(
                <React.StrictMode>
                    <App />
                </React.StrictMode>
            );
            console.log('React アプリケーションが正常に初期化されました');
            return true;
        } catch (error) {
            console.error('React アプリケーションの初期化に失敗:', error);
            return false;
        }
    } else {
        console.error('root 要素が見つかりません');
        return false;
    }
}

// DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded イベントが発火');
    initApp();
});

// document.readyState チェック
if (document.readyState === 'loading') {
    console.log('document は読み込み中です');
} else {
    console.log('document は既に読み込み完了、即座に初期化します');
    initApp();
}