import { test, expect } from '@playwright/test';

test.describe('記事詳細ページ', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/articles/1');
  });

  test('記事詳細ページが正しく表示される', async ({ page }) => {
    // ページ読み込み完了まで待機
    await page.waitForLoadState('networkidle');
    
    // タイトルが表示されることを確認
    await expect(page.locator('h1').first()).toBeVisible({ timeout: 10000 });
    
    // 公開日が表示されることを確認（待機時間を長めに）
    await expect(page.locator('[data-testid="published-date"]')).toBeVisible({ timeout: 10000 });
    
    // 「記事を読む」ボタンが表示されることを確認
    await expect(page.locator('text=記事を読む').first()).toBeVisible();
  });

  test('企業情報セクションが表示される', async ({ page }) => {
    // ページ読み込み完了まで待機
    await page.waitForLoadState('networkidle');
    
    // 企業情報セクションが存在することを確認
    await expect(page.locator('text=企業情報').first()).toBeVisible({ timeout: 10000 });
    
    // 企業名リンクが機能することを確認
    const companyLink = page.locator('[data-testid="company-link"]').first();
    if (await companyLink.isVisible()) {
      await expect(companyLink).toHaveAttribute('href', /\/companies\/\d+/);
    }
  });

  test('統計情報が表示される', async ({ page }) => {
    // ページ読み込み完了まで待機
    await page.waitForLoadState('networkidle');
    
    // 統計情報セクションが存在することを確認
    await expect(page.locator('text=統計情報').first()).toBeVisible({ timeout: 10000 });
    
    // ブックマーク数が表示されることを確認
    await expect(page.locator('text=ブックマーク')).toBeVisible();
  });

  test('記事URLセクションが機能する', async ({ page }) => {
    // ページ読み込み完了まで待機
    await page.waitForLoadState('domcontentloaded');
    await page.waitForSelector('h1, .main-content, [data-testid="article-detail"]', { timeout: 15000 });
    
    // 記事URLセクションが存在することを確認
    await expect(page.locator('text=記事URL').first()).toBeVisible({ timeout: 10000 });
    
    // コピーボタンが存在することを確認
    await expect(page.locator('button:has-text("コピー")').first()).toBeVisible({ timeout: 10000 });
  });

  test('プラットフォーム詳細が表示される', async ({ page }) => {
    // ページ読み込み完了まで待機
    await page.waitForLoadState('networkidle');
    
    // プラットフォームセクションが存在することを確認
    await expect(page.locator('text=プラットフォーム').first()).toBeVisible({ timeout: 10000 });
  });

  test('メタデータが表示される', async ({ page }) => {
    // メタデータセクションが存在することを確認
    await expect(page.locator('text=メタデータ')).toBeVisible();
    
    // 記事IDが表示されることを確認
    await expect(page.locator('text=記事ID:')).toBeVisible();
    
    // 登録日時が表示されることを確認
    await expect(page.locator('text=登録日時:')).toBeVisible();
    
    // 更新日時が表示されることを確認
    await expect(page.locator('text=更新日時:')).toBeVisible();
  });

  test('戻るボタンが機能する', async ({ page }) => {
    // 戻るボタンが存在することを確認
    await expect(page.locator('button:has-text("戻る")').first()).toBeVisible();
  });

  test('記事一覧に戻るリンクが機能する', async ({ page }) => {
    // 記事一覧に戻るリンクが存在することを確認
    const backToListLink = page.locator('text=記事一覧に戻る');
    await expect(backToListLink).toBeVisible();
    await expect(backToListLink).toHaveAttribute('href', '/articles');
  });

  test('記事を読むボタンが外部リンクとして機能する', async ({ page }) => {
    // 記事を読むボタンを探す（最初の要素）
    const readArticleButton = page.locator('a:has-text("記事を読む")').first();
    await expect(readArticleButton).toBeVisible();
    
    // 外部リンクの属性を確認
    await expect(readArticleButton).toHaveAttribute('target', '_blank');
    await expect(readArticleButton).toHaveAttribute('rel', 'noopener noreferrer');
  });

  test('共有ボタンが存在する', async ({ page }) => {
    // 共有ボタンが存在することを確認
    const shareButton = page.locator('button:has-text("共有")');
    await expect(shareButton).toBeVisible();
  });

  test('レスポンシブ対応：モバイル表示', async ({ page }) => {
    // モバイルサイズに変更
    await page.setViewportSize({ width: 375, height: 667 });
    
    // コンテンツが適切に表示されることを確認
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('text=記事を読む').first()).toBeVisible();
    
    // サイドバー要素がモバイルで適切に配置されることを確認
    await expect(page.locator('text=統計情報')).toBeVisible();
  });

  test('レスポンシブ対応：デスクトップ表示', async ({ page }) => {
    // デスクトップサイズに変更
    await page.setViewportSize({ width: 1280, height: 800 });
    
    // すべてのセクションが表示されることを確認
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('text=企業情報')).toBeVisible();
    await expect(page.locator('text=統計情報')).toBeVisible();
    await expect(page.locator('text=プラットフォーム')).toBeVisible();
    await expect(page.locator('text=メタデータ')).toBeVisible();
  });

  test('存在しない記事IDでアクセスした場合のエラーハンドリング', async ({ page }) => {
    // 存在しない記事IDでアクセス
    await page.goto('/articles/99999');
    
    // エラーメッセージが表示されることを確認
    await expect(page.locator('text=記事の読み込みに失敗しました')).toBeVisible();
    
    // 戻るボタンと再読み込みボタンが表示されることを確認
    await expect(page.locator('button:has-text("戻る")')).toBeVisible();
    await expect(page.locator('button:has-text("再読み込み")')).toBeVisible();
  });

  test('ローディング状態が適切に表示される', async ({ page }) => {
    // ネットワークを遅くしてローディング状態をテスト
    await page.route('/api/articles/*', async route => {
      await new Promise(resolve => setTimeout(resolve, 1000));
      await route.continue();
    });
    
    await page.goto('/articles/1');
    
    // ローディングメッセージが表示されることを確認
    await expect(page.locator('text=記事を読み込み中...')).toBeVisible();
  });
});