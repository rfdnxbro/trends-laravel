import React from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { 
    ArrowLeftIcon,
    CalendarDaysIcon,
    BuildingOfficeIcon,
    GlobeAltIcon,
    ChartBarIcon,
    EyeIcon,
    ShareIcon,
    ArrowTopRightOnSquareIcon
} from '@heroicons/react/24/outline';
import { apiService } from '../services/api';
import { Article, QueryKeys } from '../types';

// ユーティリティ関数
const formatDate = (dateString: string) => {
    if (!dateString) {
        return '日時不明';
    }
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return '日時不明';
        }
        return date.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            weekday: 'long'
        });
    } catch {
        return '日時不明';
    }
};

const formatDateTime = (dateString: string) => {
    if (!dateString) {
        return '日時不明';
    }
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return '日時不明';
        }
        return date.toLocaleString('ja-JP', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return '日時不明';
    }
};

// 共有機能
const handleShare = async (title: string, url: string) => {
    if (typeof window !== 'undefined' && window.navigator?.share) {
        try {
            await window.navigator.share({ title, url });
        } catch {
            // Share cancelled
        }
    } else if (typeof window !== 'undefined' && window.navigator?.clipboard) {
        await window.navigator.clipboard.writeText(url);
        window.alert('URLをクリップボードにコピーしました');
    }
};

// URL コピー機能
const handleCopyUrl = async (url: string) => {
    if (typeof window !== 'undefined' && window.navigator?.clipboard) {
        try {
            await window.navigator.clipboard.writeText(url);
        } catch {
            // Copy failed
        }
    }
};

// サブコンポーネント
interface ArticleHeaderProps {
    article: Article;
    onShare: () => void;
}

const ArticleHeader: React.FC<ArticleHeaderProps> = ({ article, onShare }) => (
    <div className="border-b border-gray-200 pb-6 mb-6">
        <div className="flex justify-between items-start mb-4">
            <div className="flex-1">
                <h1 className="text-2xl font-bold text-gray-900 leading-tight mb-4">
                    {article.title}
                </h1>
                
                <div className="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                    <div className="flex items-center" data-testid="published-date">
                        <CalendarDaysIcon className="h-4 w-4 mr-2" />
                        <span>{formatDate(article.published_at)}</span>
                    </div>

                    {article.platform && (
                        <div className="flex items-center" data-testid="platform-badge">
                            <GlobeAltIcon className="h-4 w-4 mr-2" />
                            <span className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                {article.platform.name}
                            </span>
                        </div>
                    )}

                    {article.author_name && (
                        <div className="flex items-center">
                            <span className="text-gray-400 mr-1">by</span>
                            <span className="font-medium">{article.author_name}</span>
                        </div>
                    )}
                </div>
            </div>

            <div className="flex items-center space-x-3 ml-4">
                <button
                    onClick={onShare}
                    className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <ShareIcon className="h-4 w-4 mr-2" />
                    共有
                </button>
                <a
                    href={article.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    記事を読む
                    <ArrowTopRightOnSquareIcon className="h-4 w-4 ml-2" />
                </a>
            </div>
        </div>
    </div>
);

interface CompanyInfoProps {
    company: Article['company'];
}

const CompanyInfo: React.FC<CompanyInfoProps> = ({ company }) => {
    if (!company) return null;

    return (
        <div className="bg-gray-50 rounded-lg p-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <BuildingOfficeIcon className="h-5 w-5 mr-2" />
                企業情報
            </h3>
            <div className="flex items-start space-x-4">
                {company.logo_url && (
                    <img
                        src={company.logo_url}
                        alt={company.name}
                        className="h-16 w-16 rounded-lg object-cover flex-shrink-0"
                    />
                )}
                <div className="flex-1">
                    <h4 className="font-medium text-gray-900 text-lg">
                        <Link
                            to={`/companies/${company.id}`}
                            className="hover:text-blue-600"
                            data-testid="company-link"
                        >
                            {company.name}
                        </Link>
                    </h4>
                    {company.domain && (
                        <p className="text-sm text-gray-600 mt-1">
                            {company.domain}
                        </p>
                    )}
                    {company.description && (
                        <p className="text-sm text-gray-700 mt-2">
                            {company.description}
                        </p>
                    )}
                    {company.website_url && (
                        <a
                            href={company.website_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 mt-2"
                        >
                            <GlobeAltIcon className="h-4 w-4 mr-1" />
                            ウェブサイト
                            <ArrowTopRightOnSquareIcon className="h-3 w-3 ml-1" />
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
};

interface ArticleStatsProps {
    article: Article;
}

const ArticleStats: React.FC<ArticleStatsProps> = ({ article }) => (
    <div className="bg-white border border-gray-200 rounded-lg p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">統計情報</h3>
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div className="flex items-center">
                    <ChartBarIcon className="h-5 w-5 text-orange-500 mr-2" />
                    <span className="text-sm text-gray-600">エンゲージメント</span>
                </div>
                <span className="text-lg font-semibold text-orange-600">
                    {(article.engagement_count || 0).toLocaleString()}
                </span>
            </div>

            {article.view_count !== undefined && article.view_count > 0 && (
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <EyeIcon className="h-5 w-5 text-blue-500 mr-2" />
                        <span className="text-sm text-gray-600">閲覧数</span>
                    </div>
                    <span className="text-lg font-semibold text-blue-600">
                        {(article.view_count || 0).toLocaleString()}
                    </span>
                </div>
            )}
        </div>
    </div>
);

const ArticleDetail: React.FC = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const articleId = parseInt(id || '0', 10);

    const { data: article, isLoading, error } = useQuery({
        queryKey: QueryKeys.ARTICLE_DETAIL(articleId),
        queryFn: () => apiService.getArticleDetail(articleId).then(res => res.data.data as Article),
        retry: 1,
        enabled: !!articleId,
    });

    const onShare = () => {
        if (article) {
            handleShare(article.title, window.location.href);
        }
    };

    const onCopyUrl = () => {
        if (article) {
            handleCopyUrl(article.url);
        }
    };

    if (isLoading) {
        return (
            <div className="flex justify-center items-center min-h-64">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p className="mt-4 text-gray-600">記事を読み込み中...</p>
                </div>
            </div>
        );
    }

    if (error || !article) {
        return (
            <div className="text-center py-12">
                <div className="text-red-600 mb-4">記事の読み込みに失敗しました</div>
                <div className="space-x-4">
                    <button 
                        onClick={() => navigate(-1)}
                        className="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                    >
                        戻る
                    </button>
                    <button 
                        onClick={() => window.location.reload()} 
                        className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        再読み込み
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto">
            {/* ナビゲーション */}
            <div className="mb-6">
                <button
                    onClick={() => navigate(-1)}
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeftIcon className="h-4 w-4 mr-2" />
                    戻る
                </button>
            </div>

            {/* メインコンテンツ */}
            <div className="dashboard-card">
                <ArticleHeader article={article} onShare={onShare} />

                {/* 詳細情報 */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* メイン情報 */}
                    <div className="lg:col-span-2 space-y-6">
                        <CompanyInfo company={article.company} />

                        {/* 記事URL */}
                        <div className="bg-gray-50 rounded-lg p-4">
                            <h3 className="text-sm font-medium text-gray-900 mb-2">記事URL</h3>
                            <div className="flex items-center space-x-2">
                                <code className="flex-1 text-sm bg-white px-3 py-2 rounded border font-mono text-gray-700 break-all">
                                    {article.url}
                                </code>
                                <button
                                    onClick={onCopyUrl}
                                    className="px-3 py-2 text-sm border border-gray-300 rounded hover:bg-gray-100"
                                >
                                    コピー
                                </button>
                            </div>
                        </div>

                        {/* 著者詳細 */}
                        {article.author && (
                            <div className="bg-gray-50 rounded-lg p-4">
                                <h3 className="text-sm font-medium text-gray-900 mb-2">著者詳細</h3>
                                <div className="space-y-2">
                                    <div>
                                        <span className="text-sm text-gray-600">著者名: </span>
                                        <span className="text-sm font-medium">{article.author}</span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* サイドバー */}
                    <div className="space-y-6">
                        <ArticleStats article={article} />

                        {/* プラットフォーム詳細 */}
                        <div className="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">プラットフォーム</h3>
                            <div className="space-y-2">
                                <div>
                                    <span className="text-sm text-gray-600">名前: </span>
                                    <span className="text-sm font-medium">{article.platform?.name || 'N/A'}</span>
                                </div>
                                {article.platform?.base_url && (
                                    <div>
                                        <span className="text-sm text-gray-600">ベースURL: </span>
                                        <a
                                            href={article.platform.base_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-sm text-blue-600 hover:text-blue-800"
                                        >
                                            {article.platform.base_url}
                                            <ArrowTopRightOnSquareIcon className="h-3 w-3 ml-1 inline" />
                                        </a>
                                    </div>
                                )}
                                {article.domain && (
                                    <div>
                                        <span className="text-sm text-gray-600">ドメイン: </span>
                                        <span className="text-sm font-medium">{article.domain}</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* メタデータ */}
                        <div className="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">メタデータ</h3>
                            <div className="space-y-2 text-sm">
                                <div>
                                    <span className="text-gray-600">記事ID: </span>
                                    <span className="font-mono">{article.id}</span>
                                </div>
                                {article.scraped_at && (
                                    <div>
                                        <span className="text-gray-600">取得日時: </span>
                                        <span className="font-mono">{formatDateTime(article.scraped_at)}</span>
                                    </div>
                                )}
                                <div>
                                    <span className="text-gray-600">登録日時: </span>
                                    <span className="font-mono">{formatDateTime(article.created_at)}</span>
                                </div>
                                <div>
                                    <span className="text-gray-600">更新日時: </span>
                                    <span className="font-mono">{formatDateTime(article.updated_at)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* アクション */}
                <div className="border-t border-gray-200 pt-6 mt-6">
                    <div className="flex justify-between items-center">
                        <Link
                            to="/articles"
                            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                        >
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            記事一覧に戻る
                        </Link>
                        
                        <a
                            href={article.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            記事を読む
                            <ArrowTopRightOnSquareIcon className="h-4 w-4 ml-2" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ArticleDetail;