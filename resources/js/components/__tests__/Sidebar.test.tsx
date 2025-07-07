import { render, screen } from '@testing-library/react'
import { describe, it, expect } from 'vitest'
import { BrowserRouter } from 'react-router-dom'
import Sidebar from '../Sidebar'

describe('Sidebar', () => {
  it('サイドバーが正常にレンダリングされる', () => {
    render(
      <BrowserRouter>
        <Sidebar />
      </BrowserRouter>
    )
    
    // サイドバーのナビゲーション要素が表示されることを確認
    expect(screen.getByRole('navigation')).toBeInTheDocument()
  })
  
  it('ダッシュボードリンクが表示される', () => {
    render(
      <BrowserRouter>
        <Sidebar />
      </BrowserRouter>
    )
    
    // ダッシュボードリンクが表示されることを確認
    expect(screen.getByText('ダッシュボード')).toBeInTheDocument()
  })
})