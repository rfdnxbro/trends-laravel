// 基本エンティティ型定義
export interface Company {
    id: number;
    name: string;
    domain: string;
    description?: string;
    logo_url?: string;
    website_url?: string;
    website?: string;
    hatena_username?: string;
    qiita_username?: string;
    zenn_username?: string;
    influence_score?: number;
    ranking?: number;
    is_active?: boolean;
    created_at: string;
    updated_at: string;
}

export interface Platform {
    id: number;
    name: string;
    base_url?: string;
    is_active?: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface Article {
    id: number;
    title: string;
    url: string;
    author_name?: string;
    published_at: string;
    engagement_count: number;
    view_count?: number;
    company_id?: number;
    platform_id?: number;
    platform: Platform;
    domain?: string;
    author?: string;
    author_url?: string;
    scraped_at?: string;
    created_at: string;
    updated_at: string;
    company: Company | null;
}

export interface CompanyRanking {
    id: number;
    company_id: number;
    ranking_date: string;
    period: string;
    influence_score: number;
    rank: number;
    company: Company;
    previous_rank?: number;
    rank_change?: number;
}

// ダッシュボード関連型定義
export interface DashboardStats {
    totalCompanies: number;
    totalArticles: number;
    totalPlatforms: number;
    lastUpdated?: string;
}

export interface PeriodStats {
    total_companies: number;
    average_score: number;
    max_score: number;
    min_score: number;
    total_articles: number;
    total_bookmarks: number;
    last_calculated: string;
}

export interface RankingStatsResponse {
    data: {
        "1w": PeriodStats;
        "1m": PeriodStats;
        "3m": PeriodStats;
        "6m": PeriodStats;
        "1y": PeriodStats;
        "3y": PeriodStats;
        "all": PeriodStats;
    };
}

export interface TopCompany {
    id: number | null;
    company: {
        id: number | null;
        name: string;
        domain: string;
        logo_url: string | null;
    };
    rank_position: number;
    total_score: number;
    article_count: number;
    total_bookmarks: number;
    rank_change: number | null;
    period: {
        start: string;
        end: string;
    };
    calculated_at: string;
}

export interface TopCompaniesResponse {
    data: TopCompany[];
    meta: {
        period: string;
        limit: number;
        total: number;
    };
}

export interface CompaniesListResponse {
    data: Company[];
    meta: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
        filters: {
            search?: string;
            domain?: string;
            is_active?: boolean;
            sort_by: string;
            sort_order: string;
        };
    };
}

export interface CompanyListFilters {
    search?: string;
    domain?: string;
    is_active?: boolean;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
    page?: number;
    per_page?: number;
}

export interface ArticleListFilters {
    search?: string;
    company_id?: number;
    platform_id?: number;
    start_date?: string;
    end_date?: string;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
    page?: number;
    per_page?: number;
}

export interface ArticlesListResponse {
    data: Article[];
    meta: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
        filters?: ArticleListFilters;
    };
}

// 検索関連型定義
export interface SearchFilters {
    platforms?: string[];
    dateRange?: {
        start: string;
        end: string;
    };
    scoreRange?: {
        min: number;
        max: number;
    };
}

export interface SearchResult {
    companies: Company[];
    articles: Article[];
    total: number;
}

// API レスポンス型定義
export interface ApiResponse<T> {
    data: T;
    message?: string;
    status: 'success' | 'error';
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    first_page_url?: string;
    last_page_url?: string;
    next_page_url?: string;
    prev_page_url?: string;
    path?: string;
    links?: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

// 記事一覧専用のページネーション型（後方互換性のため）
export interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: Article[];
}

// フォーム関連型定義
export interface CompanyFormData {
    name: string;
    description?: string;
    website?: string;
    hatena_username?: string;
    qiita_username?: string;
    zenn_username?: string;
}

export interface ArticleFormData {
    title?: string;
    url?: string;
    company_id?: number | null;
    platform_id?: number;
    author_name?: string;
    author?: string;
    author_url?: string;
    published_at?: string;
    engagement_count?: number;
    domain?: string;
    platform?: string;
    scraped_at?: string;
}

// エラー型定義
export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
    status: number;
}

// ランキング関連型定義
export interface RankingPeriod {
    value: string;
    label: string;
}

export interface RankingSortOption {
    value: string;
    label: string;
}

export interface RankingFilters {
    period: string;
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    searchQuery?: string;
}

export interface RankingTableProps {
    rankings: CompanyRanking[];
    loading?: boolean;
    filters: RankingFilters;
    onFiltersChange: (filters: RankingFilters) => void;
    onCompanyClick?: (company: Company) => void;
}

export interface RankingCardProps {
    ranking: CompanyRanking;
    onClick?: () => void;
    showRankChange?: boolean;
}

// React Query キー型定義
// チャート関連型定義
export interface ChartDataPoint {
    x: string | number;
    y: number;
    label?: string;
}

export interface TimeSeriesData {
    date: string;
    value: number;
    label?: string;
}

export interface InfluenceChartData {
    companyId: number;
    companyName: string;
    data: TimeSeriesData[];
}

export interface TrendChartData {
    articleCount: TimeSeriesData[];
    bookmarkCount: TimeSeriesData[];
    period: string;
}

export interface RankingHistoryData {
    date: string;
    rank: number;
    score: number;
    companyName: string;
}

export interface ChartConfig {
    responsive: boolean;
    maintainAspectRatio: boolean;
    showLegend: boolean;
    showTooltips: boolean;
    height?: number;
    width?: number;
}

export interface InfluenceChartProps {
    data: InfluenceChartData[];
    config?: Partial<ChartConfig>;
    className?: string;
    onDataPointClick?: (dataPoint: ChartDataPoint, companyId: number) => void;
}

export interface TrendChartProps {
    data: TrendChartData;
    config?: Partial<ChartConfig>;
    className?: string;
    period: string;
    onPeriodChange?: (period: string) => void;
}

export interface RankingHistoryChartProps {
    data: RankingHistoryData[];
    config?: Partial<ChartConfig>;
    className?: string;
    companyId: number;
    maxRank?: number;
}

export const QueryKeys = {
    DASHBOARD_STATS: ['dashboard-stats'] as const,
    TOP_COMPANIES: ['top-companies'] as const,
    COMPANIES_LIST: (filters?: CompanyListFilters) => ['companies-list', filters] as const,
    COMPANY_DETAIL: (id: number) => ['company-detail', id] as const,
    ARTICLES_LIST: (filters?: ArticleListFilters) => ['articles-list', filters] as const,
    ARTICLE_DETAIL: (id: number) => ['article-detail', id] as const,
    SEARCH_COMPANIES: (query: string) => ['search-companies', query] as const,
    SEARCH_RESULTS: (query: string, filters?: SearchFilters) => ['search-results', query, filters] as const,
    COMPANY_RANKINGS: (filters: RankingFilters) => ['company-rankings', filters] as const,
    INFLUENCE_CHART: (companyIds: number[], period: string) => ['influence-chart', companyIds, period] as const,
    TREND_CHART: (period: string) => ['trend-chart', period] as const,
    RANKING_HISTORY: (companyId: number, period: string) => ['ranking-history', companyId, period] as const,
} as const;

// テスト用の型定義
export interface MockResponse {
    ok: boolean;
    json: () => Promise<PaginationData | ApiError>;
}

export interface MockFetch {
    (url: string, options?: RequestInit): Promise<MockResponse>;
    mockResolvedValueOnce: (value: MockResponse) => MockFetch;
    mockRejectedValueOnce: (error: Error) => MockFetch;
    mockImplementationOnce: (fn: () => Promise<MockResponse>) => MockFetch;
}

// Chart.js テスト用の型定義
export interface MockChartProps {
    data: ChartDataPoint[] | TimeSeriesData[] | TrendChartData | InfluenceChartData[] | RankingHistoryData[];
    options?: ChartConfig;
    [key: string]: unknown;
}