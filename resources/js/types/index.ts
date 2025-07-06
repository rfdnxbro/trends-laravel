export interface Company {
    id: number;
    name: string;
    description?: string;
    website?: string;
    hatena_username?: string;
    qiita_username?: string;
    zenn_username?: string;
    created_at: string;
    updated_at: string;
}

export interface Article {
    id: number;
    title: string;
    url: string;
    published_at: string;
    bookmark_count: number;
    like_count: number;
    view_count: number;
    company_id: number;
    platform: string;
    created_at: string;
    updated_at: string;
}

export interface CompanyRanking {
    id: number;
    company_id: number;
    ranking_date: string;
    period: string;
    influence_score: number;
    rank: number;
    company: Company;
}

export interface ApiResponse<T> {
    data: T;
    message?: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}