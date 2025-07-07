import { render, screen } from '@testing-library/react'
import { describe, it, expect } from 'vitest'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import Dashboard from '../Dashboard'

// テスト用のQueryClientを作成
const createTestQueryClient = () => new QueryClient({
  defaultOptions: {
    queries: {
      retry: false,
    },
  },
})

describe('Dashboard', () => {
  it('ダッシュボードが正常にレンダリングされる', () => {
    const queryClient = createTestQueryClient()
    
    render(
      <QueryClientProvider client={queryClient}>
        <Dashboard />
      </QueryClientProvider>
    )
    
    // ダッシュボードのタイトルが表示されることを確認
    expect(screen.getByText('ダッシュボード')).toBeInTheDocument()
    expect(screen.getByText('企業の影響力スコアとランキング情報を表示します')).toBeInTheDocument()
  })
  
  it('企業ランキングセクションが表示される', () => {
    const queryClient = createTestQueryClient()
    
    render(
      <QueryClientProvider client={queryClient}>
        <Dashboard />
      </QueryClientProvider>
    )
    
    // 企業ランキングタイトルが表示されることを確認
    expect(screen.getByText('最新ランキング（TOP 10）')).toBeInTheDocument()
  })
  
  it('統計メトリクスが表示される', () => {
    const queryClient = createTestQueryClient()
    
    render(
      <QueryClientProvider client={queryClient}>
        <Dashboard />
      </QueryClientProvider>
    )
    
    // 統計項目が表示されることを確認
    expect(screen.getByText('総企業数')).toBeInTheDocument()
    expect(screen.getByText('記事総数')).toBeInTheDocument()
    expect(screen.getByText('プラットフォーム数')).toBeInTheDocument()
  })
})