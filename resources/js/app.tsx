import React from 'react';
import { createRoot } from 'react-dom/client';

console.log('🚀 app.tsx が読み込まれました');

function SimpleApp() {
    console.log('🎨 SimpleApp コンポーネントがレンダリングされました');
    return (
        <div style={{ 
            padding: '30px', 
            backgroundColor: '#e3f2fd', 
            border: '3px solid #1976d2',
            fontFamily: 'Arial, sans-serif'
        }}>
            <h1 style={{ color: '#1976d2', fontSize: '32px' }}>
                ✅ React が正常に動作しています！
            </h1>
            <p style={{ fontSize: '18px', color: '#333' }}>
                これが表示されればReactアプリは成功です
            </p>
            <ul style={{ fontSize: '16px', color: '#555' }}>
                <li>✅ Vite 設定: OK</li>
                <li>✅ React 19: OK</li>
                <li>✅ TypeScript: OK</li>
                <li>✅ Laravel 統合: OK</li>
            </ul>
            <p style={{ 
                marginTop: '20px', 
                padding: '10px', 
                backgroundColor: '#c8e6c9',
                border: '1px solid #4caf50',
                borderRadius: '5px'
            }}>
                🎉 セットアップが完了しました！
            </p>
        </div>
    );
}

const container = document.getElementById('root');
console.log('📍 Root container:', container);

if (container) {
    console.log('🔧 React Root を作成中...');
    const root = createRoot(container);
    
    console.log('🎬 React アプリケーションをレンダリング中...');
    root.render(<SimpleApp />);
    
    console.log('✅ React アプリケーションが正常に初期化されました');
} else {
    console.error('❌ root 要素が見つかりません');
}