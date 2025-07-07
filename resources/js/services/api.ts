import axios, { AxiosResponse } from 'axios';

// Axios インスタンスの作成
export const api = axios.create({
    baseURL: window.location.origin,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    timeout: 10000,
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
        if (error.response?.status === 419) {
            // CSRF トークンエラー
            console.error('CSRF token mismatch. Please refresh the page.');
        } else if (error.response?.status >= 500) {
            console.error('Server error:', error.response?.data?.message || 'Internal server error');
        }
        return Promise.reject(error);
    }
);

// API エンドポイント関数
export const apiService = {
    // ダッシュボード統計
    getDashboardStats: () => api.get('/api/dashboard/stats'),
    
    // 企業関連
    getTopCompanies: (limit = 10) => api.get(`/api/companies/top?limit=${limit}`),
    getCompanyDetail: (id: number) => api.get(`/api/companies/${id}`),
    searchCompanies: (query: string) => api.get(`/api/companies/search?q=${query}`),
    
    // 検索
    search: (query: string, filters?: Record<string, any>) => 
        api.post('/api/search', { query, filters }),
};

export default api;