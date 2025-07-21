import React from 'react';
import { XMarkIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import { Article } from '../types';

interface ArticleDeleteModalProps {
    isOpen: boolean;
    article: Article | null;
    onClose: () => void;
    onConfirm: () => Promise<void>;
    loading?: boolean;
}

const ArticleDeleteModal: React.FC<ArticleDeleteModalProps> = ({
    isOpen,
    article,
    onClose,
    onConfirm,
    loading = false
}) => {
    const handleConfirm = async () => {
        try {
            await onConfirm();
            onClose();
        } catch {
            // エラーハンドリングは親コンポーネントで行う
        }
    };

    if (!isOpen || !article) return null;

    return (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" data-testid="article-delete-modal">
            <div className="relative top-20 mx-auto p-5 border w-full max-w-md bg-white rounded-lg shadow-lg">
                <div className="flex items-start justify-between pb-4 border-b">
                    <div className="flex items-center">
                        <ExclamationTriangleIcon className="h-6 w-6 text-red-600 mr-2" />
                        <h3 className="text-lg font-semibold text-gray-900">
                            記事を削除
                        </h3>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600"
                        data-testid="close-button"
                    >
                        <XMarkIcon className="h-6 w-6" />
                    </button>
                </div>

                <div className="mt-4">
                    <p className="text-sm text-gray-500 mb-4">
                        以下の記事を削除してもよろしいですか？この操作は取り消すことができません。
                    </p>
                    
                    <div className="bg-gray-50 p-3 rounded-md">
                        <h4 className="font-medium text-gray-900 line-clamp-2" data-testid="article-title">
                            {article.title}
                        </h4>
                        {article.company && (
                            <p className="text-sm text-gray-600 mt-1" data-testid="article-company">
                                {article.company.name}
                            </p>
                        )}
                        <p className="text-sm text-gray-500 mt-1" data-testid="article-platform">
                            {article.platform.name}
                        </p>
                    </div>
                </div>

                <div className="mt-6 flex items-center justify-end space-x-3 pt-4 border-t">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        data-testid="cancel-button"
                        disabled={loading}
                    >
                        キャンセル
                    </button>
                    <button
                        type="button"
                        onClick={handleConfirm}
                        disabled={loading}
                        className="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        data-testid="confirm-button"
                    >
                        {loading ? '削除中...' : '削除'}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ArticleDeleteModal;