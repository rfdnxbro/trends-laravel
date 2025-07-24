import axios, { AxiosResponse } from 'axios';
import { API_CONSTANTS } from '../constants/api';
import { 
    CompanyListFilters, 
    SearchCompanyParams, 
    SearchArticleParams, 
    SearchUnifiedParams 
} from '../types';

// Axios インスタンスの作成
export const api = axios.create({
    baseURL: window.location.origin,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    timeout: API_CONSTANTS.TIMEOUT,
});

// CSRFトークンを自動設定
api.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

// レスポンス インターセプター（エラーハンドリング）
api.interceptors.response.use(
    (response: AxiosResponse) => response,
    (error) => {
        if (error.response?.status === API_CONSTANTS.CSRF_ERROR_STATUS) {
            // CSRF トークンエラー - ページの再読み込みが必要
        } else if (error.response?.status >= API_CONSTANTS.SERVER_ERROR_START) {
            // サーバーエラー
        }
        return Promise.reject(error);
    }
);

// API エンドポイント関数
export const apiService = {
    // ダッシュボード統計
    getDashboardStats: () => api.get('/api/dashboard/stats'),
    
    // 企業関連
    getCompanies: (filters?: CompanyListFilters) => {
        const params = new URLSearchParams();
        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    params.append(key, String(value));
                }
            });
        }
        const queryString = params.toString();
        return api.get(`/api/companies${queryString ? '?' + queryString : ''}`);
    },
    getTopCompanies: (limit = API_CONSTANTS.DEFAULT_LIMIT) => api.get(`/api/companies/top?limit=${limit}`),
    getCompanyDetail: (id: number) => api.get(`/api/companies/${id}`),
    searchCompanies: (params: SearchCompanyParams) => {
        const queryParams = new URLSearchParams();
        queryParams.append('q', params.q);
        if (params.limit) {
            queryParams.append('limit', params.limit.toString());
        }
        return api.get(`/api/search/companies?${queryParams.toString()}`);
    },
    createCompany: (data: Record<string, unknown>) => api.post('/api/companies', data),
    updateCompany: (id: number, data: Record<string, unknown>) => api.put(`/api/companies/${id}`, data),
    deleteCompany: (id: number) => api.delete(`/api/companies/${id}`),
    
    // 記事関連
    getArticles: (filters?: Record<string, unknown>) => {
        const params = new URLSearchParams();
        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    params.append(key, String(value));
                }
            });
        }
        const queryString = params.toString();
        return api.get(`/api/articles${queryString ? '?' + queryString : ''}`);
    },
    getArticleDetail: (id: number) => api.get(`/api/articles/${id}`),
    updateArticle: (id: number, data: Record<string, unknown>) => api.put(`/api/articles/${id}`, data),
    deleteArticle: (id: number) => api.delete(`/api/articles/${id}`),
    
    // プラットフォーム関連
    getPlatforms: () => api.get('/api/platforms'),
    
    // 検索
    searchArticles: (params: SearchArticleParams) => {
        const queryParams = new URLSearchParams();
        queryParams.append('q', params.q);
        if (params.limit) {
            queryParams.append('limit', params.limit.toString());
        }
        if (params.days) {
            queryParams.append('days', params.days.toString());
        }
        if (params.min_engagement !== undefined) {
            queryParams.append('min_engagement', params.min_engagement.toString());
        }
        return api.get(`/api/search/articles?${queryParams.toString()}`);
    },
    
    searchUnified: (params: SearchUnifiedParams) => {
        const queryParams = new URLSearchParams();
        queryParams.append('q', params.q);
        if (params.type) {
            queryParams.append('type', params.type);
        }
        if (params.limit) {
            queryParams.append('limit', params.limit.toString());
        }
        if (params.days) {
            queryParams.append('days', params.days.toString());
        }
        if (params.min_engagement !== undefined) {
            queryParams.append('min_engagement', params.min_engagement.toString());
        }
        return api.get(`/api/search?${queryParams.toString()}`);
    },
    
    // 汎用検索（後方互換性のため残す）
    search: (query: string, filters?: Record<string, unknown>) => 
        api.post('/api/search', { query, filters }),
};

export default api;