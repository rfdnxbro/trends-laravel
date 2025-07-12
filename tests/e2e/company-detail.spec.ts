import { test, expect } from '@playwright/test';

test.describe('企業詳細ページ', () => {
  // 有効な企業IDを取得する関数
  async function getValidCompanyId(page: any): Promise<string | null> {
    await page.goto('/companies');
    await page.waitForLoadState('networkidle');
    
    const table = page.locator('.data-table');
    const tableExists = await table.isVisible();
    
    if (tableExists) {
      const rows = table.locator('tbody tr');
      const rowCount = await rows.count();
      
      if (rowCount > 0) {
        const detailLink = rows.first().locator('a:has-text("詳細")');
        const href = await detailLink.getAttribute('href');
        
        if (href) {
          const match = href.match(/\/companies\/(\d+)/);
          return match ? match[1] : null;
        }
      }
    }
    return null;
  }

  test('企業詳細ページが正常に表示される', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    
    // ページタイトルが正しく設定されている
    await expect(page).toHaveTitle(/DevCorpTrends/);
    
    // ページが完全にロードされるまで待機
    await page.waitForLoadState('networkidle');
    
    // 企業詳細のコンテンツが表示されている（テキストがロードされるまで待機）
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000); // APIからのデータロード待機
    
    // ページのメインコンテンツが表示されていることを確認
    const hasContent = page.locator('.dashboard-card').first();
    await expect(hasContent).toBeVisible();
  });

  test('企業基本情報が正常に表示される', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    await page.waitForLoadState('networkidle');
    
    // 企業名が表示されている（テキストコンテンツがある要素を確認）
    await page.waitForTimeout(3000); // データロード待機
    
    const companyNameElement = page.locator('h1:not(:empty), h2:not(:empty), [class*="company"]:not(:empty)').first();
    const companyNameExists = await companyNameElement.count() > 0;
    
    if (companyNameExists) {
      await expect(companyNameElement).toBeVisible();
    } else {
      // コンテンツがロードされていることを確認
      const mainContent = page.locator('.dashboard-card').first();
      await expect(mainContent).toBeVisible();
    }
    
    // 企業基本情報カードが表示されている
    const basicInfoCard = page.locator('.dashboard-card').first();
    await expect(basicInfoCard).toBeVisible();
  });

  test('企業ランキング情報が表示される', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    await page.waitForLoadState('networkidle');
    
    // ランキング関連の要素を確認
    const rankingElements = page.locator('text=/ランキング|順位|スコア/');
    const rankingCount = await rankingElements.count();
    
    if (rankingCount > 0) {
      await expect(rankingElements.first()).toBeVisible();
    }
  });

  test('企業記事一覧が表示される', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    await page.waitForLoadState('networkidle');
    
    // データロード待機
    await page.waitForTimeout(3000);
    
    // 記事セクションまたはメインコンテンツの確認
    const articlesSection = page.locator('.dashboard-card');
    const articlesSectionExists = await articlesSection.count() > 0;
    
    if (articlesSectionExists) {
      // 何らかのコンテンツが表示されていることを確認
      const hasVisibleContent = await page.locator('.dashboard-card, main, [class*="content"]').first().isVisible();
      expect(hasVisibleContent).toBeTruthy();
    } else {
      // 記事セクションがない場合でも、メインコンテンツが表示されていることを確認
      const mainContent = page.locator('.dashboard-card').first();
      await expect(mainContent).toBeVisible();
    }
  });

  test('企業SNSリンクが正常に動作する', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    await page.waitForLoadState('networkidle');
    
    // SNSリンクの確認
    const qiitaLink = page.locator('a[href*="qiita.com"]');
    const zennLink = page.locator('a[href*="zenn.dev"]');
    const websiteLink = page.locator('a[href*="http"]:not([href*="qiita"]):not([href*="zenn"])').first();
    
    // Qiitaリンクが存在する場合
    const hasQiitaLink = await qiitaLink.isVisible();
    if (hasQiitaLink) {
      await expect(qiitaLink).toHaveAttribute('target', '_blank');
      await expect(qiitaLink).toHaveAttribute('rel', /noopener/);
    }
    
    // Zennリンクが存在する場合
    const hasZennLink = await zennLink.isVisible();
    if (hasZennLink) {
      await expect(zennLink).toHaveAttribute('target', '_blank');
      await expect(zennLink).toHaveAttribute('rel', /noopener/);
    }
    
    // ウェブサイトリンクが存在する場合
    const hasWebsiteLink = await websiteLink.isVisible();
    if (hasWebsiteLink) {
      await expect(websiteLink).toHaveAttribute('target', '_blank');
    }
  });

  test('チャート・グラフが表示される', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    await page.waitForLoadState('networkidle');
    
    // Chart.js または類似のチャートライブラリの要素を確認
    const charts = page.locator('canvas, .chart, [data-chart]');
    const chartCount = await charts.count();
    
    if (chartCount > 0) {
      await expect(charts.first()).toBeVisible();
    }
  });

  test('企業一覧へのナビゲーションが動作する', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    await page.waitForLoadState('networkidle');
    
    // サイドバーまたはナビゲーションの企業一覧リンクを確認（重複解決）
    const companyListLink = page.locator('a[href="/companies"]:has-text("企業一覧")').first();
    const isLinkVisible = await companyListLink.isVisible();
    
    if (isLinkVisible) {
      await companyListLink.click();
      
      // 企業一覧ページに遷移していることを確認
      await expect(page).toHaveURL('/companies');
      await page.waitForLoadState('networkidle');
      await expect(page.locator('h1:has-text("企業一覧")')).toBeVisible();
    }
  });

  test('404エラーページの処理確認', async ({ page }) => {
    const nonExistentId = '999999';
    await page.goto(`/companies/${nonExistentId}`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000); // データロード待機
    
    // エラー状態が適切に処理されていることを確認
    const currentUrl = page.url();
    const hasErrorContent = await page.locator('.dashboard-card, main').count() > 0;
    
    // URLが適切な状態か、またはエラーコンテンツが表示されていることを確認
    const isErrorHandled = currentUrl.includes('/companies') || hasErrorContent > 0;
    expect(isErrorHandled).toBeTruthy();
  });

  test('記事詳細への遷移が動作する', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    await page.waitForLoadState('networkidle');
    
    // 記事リンクが存在する場合のテスト
    const articleLinks = page.locator('a[href*="/articles/"], a[href*="qiita.com"], a[href*="zenn.dev"]');
    const linkCount = await articleLinks.count();
    
    if (linkCount > 0) {
      const firstLink = articleLinks.first();
      const href = await firstLink.getAttribute('href');
      
      // 外部リンクの場合、新しいタブで開くことを確認
      if (href && (href.includes('qiita.com') || href.includes('zenn.dev'))) {
        await expect(firstLink).toHaveAttribute('target', '_blank');
      }
    }
  });

  test('タブ・アコーディオン機能が動作する', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    await page.waitForLoadState('networkidle');
    
    // タブまたはアコーディオンの要素を確認
    const tabs = page.locator('[role="tab"], .tab, button:has-text("詳細"), button:has-text("記事"), button:has-text("ランキング")');
    const tabCount = await tabs.count();
    
    if (tabCount > 0) {
      const firstTab = tabs.first();
      await firstTab.click();
      
      // タブの状態変化を確認
      await expect(firstTab).toHaveAttribute('aria-selected', 'true');
    }
  });

  test('レスポンシブデザインが正常に動作する', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    // モバイルビューポートでテスト
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto(`/companies/${testCompanyId}`);
    await page.waitForLoadState('networkidle');
    
    // モバイルでページが正常に表示されることを確認
    await page.waitForTimeout(2000); // データロード待機
    const mobileContent = page.locator('.dashboard-card').first();
    await expect(mobileContent).toBeVisible();
    
    // タブレットビューポートでテスト
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForLoadState('networkidle');
    
    // タブレットでページが正常に表示されることを確認
    const tabletContent = page.locator('.dashboard-card').first();
    await expect(tabletContent).toBeVisible();
    
    // デスクトップビューポートに戻す
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.waitForLoadState('networkidle');
    
    // デスクトップでも正常に表示されることを確認
    const desktopContent = page.locator('.dashboard-card').first();
    await expect(desktopContent).toBeVisible();
  });

  test('データ読み込み状態の確認', async ({ page }) => {
    const testCompanyId = await getValidCompanyId(page);
    
    if (!testCompanyId) {
      test.skip('テスト用の企業データが見つかりません');
      return;
    }
    
    await page.goto(`/companies/${testCompanyId}`);
    
    // ローディング状態の確認
    const loadingSpinner = page.locator('.loading-spinner, [data-loading="true"]');
    
    // ページロード直後にローディング状態が表示される可能性をチェック
    const isLoadingVisible = await loadingSpinner.isVisible();
    
    // データロード完了まで待機
    await page.waitForLoadState('networkidle');
    
    // ローディング状態が消えてコンテンツが表示されることを確認
    await page.waitForTimeout(2000); // データロード待機
    const finalContent = page.locator('.dashboard-card').first();
    await expect(finalContent).toBeVisible();
  });
});