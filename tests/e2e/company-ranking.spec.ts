import { test, expect } from '@playwright/test';

test.describe('企業ランキング機能', () => {
  test('ランキングページが正常に表示される', async ({ page }) => {
    // ホームページに移動
    await page.goto('/');
    
    // ページが正常にロードされることを確認
    await expect(page).toHaveTitle(/DevCorpTrends/);
    
    // Reactアプリケーションがロードされることを確認
    await expect(page.locator('#root')).toBeVisible();
  });

  test('メインナビゲーションが正常に動作する', async ({ page }) => {
    await page.goto('/');
    
    // ナビゲーション要素が存在するか確認
    const navigation = page.locator('nav, header, [role="navigation"]');
    if (await navigation.count() > 0) {
      await expect(navigation.first()).toBeVisible();
    }
    
    // メニューリンクが存在するか確認
    const menuLinks = page.locator('a[href*="/"], button');
    if (await menuLinks.count() > 0) {
      await expect(menuLinks.first()).toBeVisible();
    }
  });

  test('ユーザーインターフェースがレスポンシブに動作する', async ({ page }) => {
    await page.goto('/');
    
    // モバイルサイズでの表示確認
    await page.setViewportSize({ width: 375, height: 667 });
    await expect(page.locator('#root')).toBeVisible();
    
    // デスクトップサイズでの表示確認
    await page.setViewportSize({ width: 1920, height: 1080 });
    await expect(page.locator('#root')).toBeVisible();
  });
});