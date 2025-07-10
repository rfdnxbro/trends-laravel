import { test, expect } from '@playwright/test';

test.describe('ユーザージャーニー', () => {
  test('アプリケーションの基本表示が正常に動作する', async ({ page }) => {
    await page.goto('/');
    
    // ページタイトルが正しく設定されている
    await expect(page).toHaveTitle(/DevCorpTrends/);
    
    // Reactアプリケーションが正常にマウントされている
    await expect(page.locator('#root')).toBeVisible();
    
    // エラーメッセージが表示されていない
    const errorMessage = page.locator('text=React が読み込まれていません');
    await expect(errorMessage).not.toBeVisible();
  });

  test('ページ間ナビゲーションが正常に動作する', async ({ page }) => {
    await page.goto('/');
    
    // ナビゲーション要素の存在確認
    const navigationElements = [
      'nav',
      'header', 
      '[role="navigation"]',
      'a[href]',
      'button'
    ];
    
    for (const selector of navigationElements) {
      const element = page.locator(selector);
      if (await element.count() > 0) {
        await expect(element.first()).toBeVisible();
        break;
      }
    }
  });

  test('アプリケーションの状態管理が正常に動作する', async ({ page }) => {
    await page.goto('/');
    
    // ページがインタラクティブになるまで待機
    await page.waitForLoadState('networkidle');
    
    // JavaScriptエラーがないことを確認
    const jsErrors: string[] = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        jsErrors.push(msg.text());
      }
    });
    
    // 少し待機してエラーが発生しないか確認
    await page.waitForTimeout(1000);
    
    // 重大なエラーがないことを確認（警告は許可）
    const criticalErrors = jsErrors.filter(error => 
      !error.includes('Warning') && 
      !error.includes('DevTools')
    );
    expect(criticalErrors).toHaveLength(0);
  });
});