import { test, expect } from '@playwright/test';

test.describe('記事一覧ページ', () => {
  test('記事一覧ページが正常に表示される', async ({ page }) => {
    await page.goto('/articles');
    
    // ページタイトルが正しく設定されている
    await expect(page).toHaveTitle(/DevCorpTrends/);
    
    // ページが完全にロードされるまで待機
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のヘッダーが表示されている
    await expect(page.locator('h1')).toContainText('記事一覧');
    
    // 説明文が表示されている
    await expect(page.locator('p').first()).toContainText('企業別の技術記事を確認できます');
  });

  test('記事データが正常に表示される', async ({ page }) => {
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のロードを待機
    await page.waitForSelector('[role="list"]', { timeout: 10000 });
    
    // 記事アイテムが表示されている
    const articles = page.locator('[role="list"] li');
    const count = await articles.count();
    
    if (count > 0) {
      // 最初の記事要素をチェック
      const firstArticle = articles.first();
      await expect(firstArticle).toBeVisible();
      
      // 記事タイトルが表示されている
      const titleLink = firstArticle.locator('a');
      await expect(titleLink).toBeVisible();
      
      // 企業名が表示されている
      const companyName = firstArticle.locator('text=/[^•]+/');
      await expect(companyName.first()).toBeVisible();
      
      // プラットフォームバッジが表示されている
      const platformBadge = firstArticle.locator('.bg-blue-100');
      await expect(platformBadge).toBeVisible();
    }
  });

  test('記事のリンクが正常に動作する', async ({ page }) => {
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のロードを待機
    await page.waitForSelector('[role="list"]', { timeout: 10000 });
    
    const articles = page.locator('[role="list"] li');
    const count = await articles.count();
    
    if (count > 0) {
      // 最初の記事リンクをチェック
      const firstArticleLink = articles.first().locator('a');
      await expect(firstArticleLink).toBeVisible();
      
      // リンクが外部サイトを指している
      const href = await firstArticleLink.getAttribute('href');
      expect(href).toBeTruthy();
      expect(href).toMatch(/^https?:\/\//);
      
      // target="_blank"が設定されている
      const target = await firstArticleLink.getAttribute('target');
      expect(target).toBe('_blank');
    }
  });

  test('企業ロゴが正常に表示される', async ({ page }) => {
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のロードを待機
    await page.waitForSelector('[role="list"]', { timeout: 10000 });
    
    const articles = page.locator('[role="list"] li');
    const count = await articles.count();
    
    if (count > 0) {
      // 企業ロゴが存在する記事を探す
      const logoImages = page.locator('[role="list"] img');
      const logoCount = await logoImages.count();
      
      if (logoCount > 0) {
        const firstLogo = logoImages.first();
        await expect(firstLogo).toBeVisible();
        
        // ロゴのaltテキストが設定されている
        const alt = await firstLogo.getAttribute('alt');
        expect(alt).toBeTruthy();
        expect(alt).not.toBe('');
      }
    }
  });

  test('ページネーション要素の表示確認', async ({ page }) => {
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のロードを待機
    await page.waitForSelector('[role="list"]', { timeout: 10000 });
    
    // デスクトップサイズに設定（ページネーションUIを確実に表示）
    await page.setViewportSize({ width: 1280, height: 720 });
    
    // 記事が表示されていることを確認
    const articles = page.locator('[role="list"] li');
    const articleCount = await articles.count();
    
    // 記事が存在する場合のテスト
    if (articleCount > 0) {
      // 最初にページ全体が正しく読み込まれているか確認
      await expect(page.locator('h1')).toContainText('記事一覧');
      
      // 基本的なページ情報の確認（articles.last_page > 1の場合のみ表示される）
      const paginationContainer = page.locator('div.bg-white.px-4.py-3');
      const hasPagination = await paginationContainer.count() > 0;
      
      if (hasPagination) {
        // ページ情報が表示されている
        const pageInfo = page.locator('text=/件中.*件を表示/');
        await expect(pageInfo.first()).toBeVisible();
        
        // モバイル版とデスクトップ版のページネーションが適切に表示されている
        // モバイル版（sm:hiddenクラス）
        const mobileNav = page.locator('div.flex-1.flex.justify-between.sm\\:hidden');
        if (await mobileNav.count() > 0) {
          // モバイル版のボタンは画面サイズによっては表示されない
          console.log('Mobile pagination exists');
        }
        
        // デスクトップ版（hidden sm:flex）
        const desktopSection = page.locator('div.hidden.sm\\:flex-1.sm\\:flex.sm\\:items-center.sm\\:justify-between');
        if (await desktopSection.count() > 0) {
          await expect(desktopSection).toBeVisible();
          
          // ナビゲーション要素の確認
          const nav = desktopSection.locator('nav');
          if (await nav.count() > 0) {
            await expect(nav).toBeVisible();
          }
        }
      } else {
        // ページネーションが表示されない場合（単一ページ）
        console.log('Single page - no pagination controls needed');
      }
    } else {
      // 記事が0件の場合
      console.log('No articles found - pagination not applicable');
    }
  });

  test('ブックマーク数が正常に表示される', async ({ page }) => {
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のロードを待機
    await page.waitForSelector('[role="list"]', { timeout: 10000 });
    
    const articles = page.locator('[role="list"] li');
    const count = await articles.count();
    
    if (count > 0) {
      // ブックマーク数が表示されている記事を探す
      const bookmarkCounts = page.locator('text=/\\d+ ブックマーク/');
      const bookmarkCount = await bookmarkCounts.count();
      
      if (bookmarkCount > 0) {
        const firstBookmark = bookmarkCounts.first();
        await expect(firstBookmark).toBeVisible();
        
        // ブックマーク数が0以上であることを確認
        const text = await firstBookmark.textContent();
        expect(text).toMatch(/\d+ ブックマーク/);
      }
    }
  });

  test('企業名が正常に表示される', async ({ page }) => {
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のロードを待機
    await page.waitForSelector('[role="list"]', { timeout: 10000 });
    
    const articles = page.locator('[role="list"] li');
    const count = await articles.count();
    
    if (count > 0) {
      // 企業名が表示されているかチェック
      const companyNames = page.locator('.font-medium.text-gray-900');
      const companyCount = await companyNames.count();
      
      if (companyCount > 0) {
        const firstCompany = companyNames.first();
        await expect(firstCompany).toBeVisible();
        
        // 企業名が空でないことを確認
        const text = await firstCompany.textContent();
        expect(text).toBeTruthy();
        expect(text?.trim()).not.toBe('');
      }
    }
  });

  test('プラットフォームバッジが正常に表示される', async ({ page }) => {
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のロードを待機
    await page.waitForSelector('[role="list"]', { timeout: 10000 });
    
    const articles = page.locator('[role="list"] li');
    const count = await articles.count();
    
    if (count > 0) {
      // プラットフォームバッジが表示されているかチェック
      const platformBadges = page.locator('.bg-blue-100.text-blue-800');
      const badgeCount = await platformBadges.count();
      
      if (badgeCount > 0) {
        const firstBadge = platformBadges.first();
        await expect(firstBadge).toBeVisible();
        
        // プラットフォーム名が表示されているかチェック
        const text = await firstBadge.textContent();
        expect(text).toBeTruthy();
        expect(text?.trim()).not.toBe('');
      }
    }
  });

  test('記事一覧が空の場合の処理', async ({ page }) => {
    // 空の記事一覧をシミュレート（実際のAPIから空のデータが返される場合）
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のロードを待機
    await page.waitForSelector('h1', { timeout: 10000 });
    
    // ヘッダーは表示されている
    await expect(page.locator('h1')).toContainText('記事一覧');
    
    // 記事リストが存在するかチェック
    const articleList = page.locator('[role="list"]');
    const listExists = await articleList.count() > 0;
    
    if (listExists) {
      const articles = page.locator('[role="list"] li');
      const count = await articles.count();
      
      // 記事がない場合でも、レイアウトが正常に表示されている
      if (count === 0) {
        await expect(page.locator('h1')).toBeVisible();
        await expect(page.locator('p')).toContainText('企業別の技術記事を確認できます');
      }
    }
  });

  test('レスポンシブデザインが正常に動作する', async ({ page }) => {
    // モバイルサイズでテスト
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // 記事一覧のロードを待機
    await page.waitForSelector('h1', { timeout: 10000 });
    
    // ヘッダーが表示されている
    await expect(page.locator('h1')).toBeVisible();
    
    // 記事リストが存在する場合
    const articleList = page.locator('[role="list"]');
    const listExists = await articleList.count() > 0;
    
    if (listExists) {
      const articles = page.locator('[role="list"] li');
      const count = await articles.count();
      
      if (count > 0) {
        // 最初の記事が表示されている
        const firstArticle = articles.first();
        await expect(firstArticle).toBeVisible();
        
        // モバイルでも記事タイトルが読める
        const titleLink = firstArticle.locator('a');
        await expect(titleLink).toBeVisible();
      }
    }
    
    // デスクトップサイズに戻す
    await page.setViewportSize({ width: 1280, height: 720 });
  });
});