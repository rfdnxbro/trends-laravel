import React from 'react';
import { createRoot } from 'react-dom/client';

console.log('test-simple.tsx が読み込まれました');

function SimpleApp() {
    return (
        <div style={{ padding: '20px', backgroundColor: 'lightblue' }}>
            <h1>React テストアプリ</h1>
            <p>これが表示されればReactは正常に動作しています</p>
        </div>
    );
}

function initSimpleApp() {
    console.log('SimpleApp を初期化中...');
    const container = document.getElementById('root');
    console.log('container:', container);

    if (container) {
        try {
            const root = createRoot(container);
            root.render(<SimpleApp />);
            console.log('SimpleApp が正常に初期化されました');
        } catch (error) {
            console.error('SimpleApp の初期化に失敗:', error);
        }
    } else {
        console.error('root 要素が見つかりません');
    }
}

// 即座に実行
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSimpleApp);
} else {
    initSimpleApp();
}