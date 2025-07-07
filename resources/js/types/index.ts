// 基本エンティティ型定義
export interface Company {
    id: number;
    name: string;
    description?: string;
    website?: string;
    hatena_username?: string;
    qiita_username?: string;
    zenn_username?: string;
    influence_score?: number;
    ranking?: number;
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
    company?: Company;
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

export interface TopCompany extends Company {
    influence_score: number;
    ranking: number;
    recent_articles?: Article[];
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
    links?: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
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
    COMPANY_DETAIL: (id: number) => ['company-detail', id] as const,
    SEARCH_COMPANIES: (query: string) => ['search-companies', query] as const,
    SEARCH_RESULTS: (query: string, filters?: SearchFilters) => ['search-results', query, filters] as const,
    COMPANY_RANKINGS: (filters: RankingFilters) => ['company-rankings', filters] as const,
    INFLUENCE_CHART: (companyIds: number[], period: string) => ['influence-chart', companyIds, period] as const,
    TREND_CHART: (period: string) => ['trend-chart', period] as const,
    RANKING_HISTORY: (companyId: number, period: string) => ['ranking-history', companyId, period] as const,
} as const;