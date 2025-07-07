import React from 'react';
import { Link } from 'react-router-dom';

const Header: React.FC = () => {
    return (
        <header className="bg-white shadow-sm border-b">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center h-16">
                    <div className="flex items-center">
                        <Link to="/" className="text-xl font-bold text-gray-900">
                            {import.meta.env.VITE_APP_NAME || 'DevCorpTrends'}
                        </Link>
                    </div>
                    <nav className="hidden md:flex space-x-8">
                        <Link to="/" className="text-gray-500 hover:text-gray-900">
                            ダッシュボード
                        </Link>
                        <Link to="/rankings" className="text-gray-500 hover:text-gray-900">
                            ランキング
                        </Link>
                        <Link to="/companies" className="text-gray-500 hover:text-gray-900">
                            企業一覧
                        </Link>
                    </nav>
                </div>
            </div>
        </header>
    );
};

export default Header;