console.log('🚀 app.tsx が読み込まれました - esbuild JSX テスト');

import React from 'react';
import { createRoot } from 'react-dom/client';

console.log('✅ React と createRoot のインポートが完了しました');

// シンプルなReactコンポーネント
function TestApp() {
    console.log('🎨 TestApp コンポーネントがレンダリングされました');
    
    return (
        <div style={{ 
            padding: '30px', 
            backgroundColor: '#e8f5e8', 
            border: '3px solid #4caf50',
            fontFamily: 'Arial, sans-serif'
        }}>
            <h1 style={{ color: '#2e7d32', fontSize: '32px' }}>
                🎉 React が正常に動作しています！
            </h1>
            <p style={{ fontSize: '18px', color: '#333' }}>
                React + TypeScript + Vite + Laravel の統合が成功しました
            </p>
            <ul style={{ fontSize: '16px', color: '#555' }}>
                <li>✅ JavaScript読み込み: OK</li>
                <li>✅ Vite設定: OK</li>
                <li>✅ TypeScript: OK</li>
                <li>✅ Laravel統合: OK</li>
                <li>✅ React 19: OK</li>
            </ul>
            <p style={{ 
                marginTop: '20px', 
                padding: '10px', 
                backgroundColor: '#c8e6c9',
                border: '1px solid #4caf50',
                borderRadius: '5px'
            }}>
                🎊 フロントエンド基盤の構築が完了しました！
            </p>
            <p style={{ marginTop: '10px', fontSize: '14px', color: '#666' }}>
                Time: {new Date().toLocaleTimeString()}
            </p>
        </div>
    );
}

// DOMが読み込まれるまで待つ
document.addEventListener('DOMContentLoaded', () => {
    console.log('📍 DOMContentLoaded イベント発火');
    initReactApp();
});

// 既にDOMが読み込まれている場合
if (document.readyState !== 'loading') {
    console.log('📍 DOMは既に読み込み済み、即座に初期化');
    initReactApp();
}

function initReactApp() {
    const container = document.getElementById('root');
    console.log('📍 Root container:', container);

    if (container) {
        try {
            console.log('🔧 React Root を作成中...');
            const root = createRoot(container);
            
            console.log('🎬 React アプリケーションをレンダリング中...');
            root.render(<TestApp />);
            
            console.log('✅ React アプリケーションが正常に初期化されました');
        } catch (error) {
            console.error('❌ React 初期化エラー:', error);
            
            // フォールバック表示
            container.innerHTML = `
                <div style="padding: 20px; background-color: #ffebee; border: 2px solid #f44336;">
                    <h2 style="color: #c62828;">React 初期化エラー</h2>
                    <p>エラー: ${error.message}</p>
                    <p>しかしJavaScript基盤は正常に動作しています。</p>
                </div>
            `;
        }
    } else {
        console.error('❌ root 要素が見つかりません');
    }
}