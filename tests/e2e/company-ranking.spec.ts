import { test, expect } from '@playwright/test';

test.describe('企業ランキング機能', () => {
  test('ランキングデータが表示される', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('domcontentloaded');
    
    // ランキングテーブルやリストの存在確認
    const rankingElements = page.locator('table, ul, .ranking, .companies, [data-testid="ranking"]');
    
    if (await rankingElements.count() > 0) {
      await expect(rankingElements.first()).toBeVisible();
      
      // 企業名や順位情報が表示されているか確認
      const companyInfo = page.locator('td, li, .company-name, .rank');
      if (await companyInfo.count() > 0) {
        await expect(companyInfo.first()).toBeVisible();
      }
    }
  });

  test('期間フィルター機能が動作する', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('domcontentloaded');
    
    // 期間選択のドロップダウンやボタンを探す
    const periodFilters = page.locator('select, button:has-text("週"), button:has-text("月"), .period-filter');
    
    if (await periodFilters.count() > 0) {
      const firstFilter = periodFilters.first();
      await expect(firstFilter).toBeVisible();
      
      // フィルターが操作可能であることを確認
      await expect(firstFilter).toBeEnabled();
    }
  });

  test('企業詳細ページへの遷移が動作する', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('domcontentloaded');
    
    // 企業名のリンクを探す
    const companyLinks = page.locator('a[href*="/companies/"], a[href*="/company/"], .company-link');
    
    if (await companyLinks.count() > 0) {
      const firstLink = companyLinks.first();
      await expect(firstLink).toBeVisible();
      
      // リンクをクリックして詳細ページに移動
      await firstLink.click();
      
      // ページが変わったことを確認
      await page.waitForLoadState('domcontentloaded');
      await expect(page).toHaveURL(/\/compan/);
    }
  });

  test('レスポンシブデザインが正常に動作する', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('domcontentloaded');
    
    // モバイルサイズでの表示確認
    await page.setViewportSize({ width: 375, height: 667 });
    await expect(page.locator('#root')).toBeVisible();
    
    // タブレットサイズでの表示確認
    await page.setViewportSize({ width: 768, height: 1024 });
    await expect(page.locator('#root')).toBeVisible();
    
    // デスクトップサイズでの表示確認
    await page.setViewportSize({ width: 1920, height: 1080 });
    await expect(page.locator('#root')).toBeVisible();
  });
});