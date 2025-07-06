import React from 'react';

const App: React.FC = () => {
    return (
        <div style={{ padding: '20px', backgroundColor: '#f0f0f0' }}>
            <h1 style={{ color: 'blue' }}>React アプリケーションが動作しています！</h1>
            <p>これが表示されればReactは正常に動作しています</p>
            <ul>
                <li>React 19.1.0</li>
                <li>TypeScript</li>
                <li>Vite + Laravel</li>
            </ul>
        </div>
    );
};

export default App;