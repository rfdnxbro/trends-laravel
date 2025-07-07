import React from 'react';
import { Routes, Route } from 'react-router-dom';
import Layout from './Layout';
import Dashboard from '../pages/Dashboard';
import CompanyDetail from '../pages/CompanyDetail';

const App: React.FC = () => {
    return (
        <Layout>
            <Routes>
                <Route path="/" element={<Dashboard />} />
                <Route path="/dashboard" element={<Dashboard />} />
                <Route path="/companies/:id" element={<CompanyDetail />} />
            </Routes>
        </Layout>
    );
};

export default App;