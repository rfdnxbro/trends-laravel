import { useQuery, UseQueryOptions } from '@tanstack/react-query';
import api from '../services/api';
import { ApiResponse, PaginatedResponse, Company, CompanyRanking } from '../types';

export const useApi = <T>(
    key: string[],
    url: string,
    options?: UseQueryOptions<T>
) => {
    return useQuery({
        queryKey: key,
        queryFn: async () => {
            const response = await api.get<T>(url);
            return response.data;
        },
        ...options,
    });
};

export const useCompanies = (page: number = 1, limit: number = 10) => {
    return useApi<PaginatedResponse<Company>>(
        ['companies', page.toString(), limit.toString()],
        `/companies?page=${page}&limit=${limit}`
    );
};

export const useCompany = (id: number) => {
    return useApi<ApiResponse<Company>>(
        ['company', id.toString()],
        `/companies/${id}`
    );
};

export const useRankings = (period: string = 'monthly') => {
    return useApi<PaginatedResponse<CompanyRanking>>(
        ['rankings', period],
        `/rankings?period=${period}`
    );
};