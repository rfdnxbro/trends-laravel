import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const Sidebar: React.FC = () => {
    const location = useLocation();

    const navigation = [
        { name: 'ダッシュボード', href: '/', current: location.pathname === '/' },
        { name: 'ランキング', href: '/rankings', current: location.pathname === '/rankings' },
        { name: '企業一覧', href: '/companies', current: location.pathname === '/companies' },
        { name: '記事一覧', href: '/articles', current: location.pathname === '/articles' },
        { name: '検索', href: '/search', current: location.pathname === '/search' },
    ];

    return (
        <div className="w-64 bg-white shadow-sm">
            <div className="px-3 py-6">
                <nav className="space-y-1">
                    {navigation.map((item) => (
                        <Link
                            key={item.name}
                            to={item.href}
                            className={`${
                                item.current
                                    ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-500'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                            } group flex items-center px-2 py-2 text-sm font-medium rounded-md`}
                        >
                            {item.name}
                        </Link>
                    ))}
                </nav>
            </div>
        </div>
    );
};

export default Sidebar;