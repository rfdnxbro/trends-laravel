import { render, screen } from '@testing-library/react'
import { describe, it, expect } from 'vitest'
import { BrowserRouter } from 'react-router-dom'
import Header from '../Header'

describe('Header', () => {
  it('ヘッダーが正常にレンダリングされる', () => {
    render(
      <BrowserRouter>
        <Header />
      </BrowserRouter>
    )
    
    // ヘッダーのアプリ名が表示されることを確認
    expect(screen.getByText('DevCorpTrends')).toBeInTheDocument()
  })
  
  it('ナビゲーションリンクが表示される', () => {
    render(
      <BrowserRouter>
        <Header />
      </BrowserRouter>
    )
    
    // ダッシュボードリンクが表示されることを確認
    expect(screen.getByText('ダッシュボード')).toBeInTheDocument()
    expect(screen.getByText('検索')).toBeInTheDocument()
    expect(screen.getByText('ランキング')).toBeInTheDocument()
    expect(screen.getByText('企業一覧')).toBeInTheDocument()
  })
})