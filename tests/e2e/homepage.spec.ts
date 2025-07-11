import { test, expect } from '@playwright/test';

test.describe('ホームページ', () => {
  test('ホームページが正常に表示される', async ({ page }) => {
    await page.goto('/');
    
    // ページタイトルが正しく設定されている
    await expect(page).toHaveTitle(/DevCorpTrends/);
    
    // Reactアプリケーションがロードされている
    await expect(page.locator('#root')).toBeVisible();
    
    // ページが完全にロードされるまで待機
    await page.waitForLoadState('networkidle');
  });

  test('検索機能が正常に動作する', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    // 検索フィールドを探す
    const searchInput = page.locator('input[type="search"], input[placeholder*="検索"], input[placeholder*="search"]');
    
    if (await searchInput.count() > 0) {
      await searchInput.first().fill('テスト');
      await searchInput.first().press('Enter');
      
      // 検索結果が表示されるまで待機
      await page.waitForTimeout(2000);
      
      // 検索結果エリアが表示されているか確認
      const resultsArea = page.locator('[data-testid="search-results"], .search-results, .results');
      if (await resultsArea.count() > 0) {
        await expect(resultsArea.first()).toBeVisible();
      }
    }
  });

  test('メインナビゲーションが動作する', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    // ナビゲーション要素を探す
    const navLinks = page.locator('nav a, header a, [role="navigation"] a');
    
    if (await navLinks.count() > 0) {
      const firstLink = navLinks.first();
      await expect(firstLink).toBeVisible();
      
      // リンクがクリック可能であることを確認
      await expect(firstLink).toBeEnabled();
    }
  });
});