import React from 'react';
import { Routes, Route } from 'react-router-dom';
import Layout from './Layout';
import Dashboard from '../pages/Dashboard';
import CompanyDetail from '../pages/CompanyDetail';
import CompanyList from '../pages/CompanyList';
import ArticleList from '../pages/ArticleList';

const App: React.FC = () => {
    return (
        <Layout>
            <Routes>
                <Route path="/" element={<Dashboard />} />
                <Route path="/dashboard" element={<Dashboard />} />
                <Route path="/companies" element={<CompanyList />} />
                <Route path="/companies/:id" element={<CompanyDetail />} />
                <Route path="/articles" element={<ArticleList />} />
            </Routes>
        </Layout>
    );
};

export default App;