import React from 'react';
import { Link } from 'react-router-dom';
import { 
    DocumentTextIcon, 
    CalendarDaysIcon,
    ChevronRightIcon
} from '@heroicons/react/24/outline';
import { Article } from '../types';

interface ArticleItemProps {
    article: Article;
    formatDate: (dateString: string) => string;
}

const ArticleItem: React.FC<ArticleItemProps> = ({ article, formatDate }) => {
    return (
        <div className="py-4 hover:bg-gray-50" data-testid="article-item">
            <div className="flex items-start justify-between">
                <div className="flex-1 min-w-0">
                    <div className="flex items-start space-x-3">
                        <DocumentTextIcon className="h-5 w-5 text-gray-400 mt-1 flex-shrink-0" />
                        <div className="flex-1">
                            <h3 className="text-sm font-medium text-gray-900 line-clamp-2">
                                <a
                                    href={article.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="hover:text-blue-600"
                                >
                                    {article.title}
                                </a>
                            </h3>
                            
                            <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500">
                                {/* 企業情報 */}
                                {article.company && (
                                    <div className="flex items-center space-x-1">
                                        {article.company.logo_url && (
                                            <img
                                                src={article.company.logo_url}
                                                alt={article.company.name}
                                                className="h-4 w-4 rounded-full"
                                            />
                                        )}
                                        <Link
                                            to={`/companies/${article.company.id}`}
                                            className="font-medium text-gray-900 hover:text-blue-600"
                                            data-testid="company-link"
                                        >
                                            {article.company.name}
                                        </Link>
                                    </div>
                                )}
                                
                                {/* プラットフォーム */}
                                <span className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                    {article.platform.name}
                                </span>
                                
                                {/* 著者 */}
                                {article.author_name && (
                                    <span className="flex items-center">
                                        <span className="text-gray-400 mr-1">by</span>
                                        {article.author_name}
                                    </span>
                                )}
                                
                                {/* 公開日 */}
                                <span className="flex items-center">
                                    <CalendarDaysIcon className="h-4 w-4 mr-1" />
                                    {formatDate(article.published_at)}
                                </span>
                                
                                {/* ブックマーク数 */}
                                {article.bookmark_count > 0 && (
                                    <span className="text-green-600 font-medium">
                                        {article.bookmark_count} ブックマーク
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div className="ml-4 flex-shrink-0">
                    <Link
                        to={`/articles/${article.id}`}
                        className="text-blue-600 hover:text-blue-900 text-sm font-medium flex items-center"
                    >
                        詳細
                        <ChevronRightIcon className="h-4 w-4 ml-1" />
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default ArticleItem;