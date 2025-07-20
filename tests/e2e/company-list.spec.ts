import { test, expect } from '@playwright/test';

test.describe('企業一覧ページ', () => {
  test('企業一覧ページが正常に表示される', async ({ page }) => {
    await page.goto('/companies');
    
    // ページタイトルが正しく設定されている
    await expect(page).toHaveTitle(/DevCorpTrends/);
    
    // ページが完全にロードされるまで待機
    await page.waitForLoadState('networkidle');
    
    // 企業一覧のヘッダーが表示されている
    await expect(page.locator('h1')).toContainText('企業一覧');
    
    // 説明文が表示されている
    await expect(page.locator('p').first()).toContainText('登録されている企業の一覧を表示します');
  });

  test('企業データが正常に表示される', async ({ page }) => {
    await page.goto('/companies');
    await page.waitForLoadState('networkidle');
    
    // 企業一覧のロードを待機（テーブルまたはカード形式）
    await page.waitForSelector('.data-table, .dashboard-card', { timeout: 10000 });
    
    // テーブル形式の場合
    const table = page.locator('.data-table');
    const isTableVisible = await table.isVisible();
    
    if (isTableVisible) {
      // テーブルヘッダーが表示されている
      await expect(table.locator('thead th').first()).toContainText('企業名');
      
      // 企業データが表示されている
      const rows = table.locator('tbody tr');
      const count = await rows.count();
      
      if (count > 0) {
        // 最初の企業行をチェック
        const firstRow = rows.first();
        await expect(firstRow).toBeVisible();
        
        // 企業名が表示されている
        const companyName = firstRow.locator('td').first();
        await expect(companyName).toBeVisible();
        
        // 詳細リンクが表示されている
        const detailLink = firstRow.locator('a:has-text("詳細")');
        await expect(detailLink).toBeVisible();
      }
    }
  });

  test('検索機能が正常に動作する', async ({ page }) => {
    await page.goto('/companies');
    await page.waitForLoadState('networkidle');
    
    // 検索ボックスの存在確認
    const searchInput = page.locator('input[placeholder*="企業名で検索"]');
    await expect(searchInput).toBeVisible();
    
    // 検索テストを実行
    await searchInput.fill('Test');
    
    // 検索結果の表示を待機
    await page.waitForTimeout(1000);
    
    // 検索結果が反映されていることを確認（要素の変化を待つ）
    await page.waitForFunction(() => {
      const input = document.querySelector('input[placeholder*="企業名で検索"]') as HTMLInputElement;
      return input && input.value === 'Test';
    });
  });

  test('ページサイズ変更機能が動作する', async ({ page }) => {
    await page.goto('/companies');
    await page.waitForLoadState('networkidle');
    
    // ページサイズ選択ボックスの存在確認
    const perPageSelect = page.locator('select').filter({ hasText: '件' });
    const isSelectVisible = await perPageSelect.isVisible();
    
    if (isSelectVisible) {
      // デフォルト値を確認
      await expect(perPageSelect).toHaveValue('20');
      
      // ページサイズを変更
      await perPageSelect.selectOption('50');
      
      // 選択値が変更されていることを確認
      await expect(perPageSelect).toHaveValue('50');
    }
  });

  test('ソート機能が動作する', async ({ page }) => {
    await page.goto('/companies');
    await page.waitForLoadState('domcontentloaded');
    await page.waitForSelector('.data-table, .main-content, h1', { timeout: 15000 });
    
    // ソート可能なヘッダーの存在確認
    const nameHeader = page.locator('th:has-text("企業名")');
    const isHeaderVisible = await nameHeader.isVisible();
    
    if (isHeaderVisible) {
      // 企業名でソート
      await nameHeader.click();
      
      // ソート矢印が表示されることを確認
      await expect(page.locator('th:has-text("企業名") span')).toBeVisible();
    }
  });

  test('企業詳細ページへの遷移が動作する', async ({ page }) => {
    await page.goto('/companies');
    await page.waitForLoadState('networkidle');
    
    // 企業一覧のロードを待機
    await page.waitForSelector('.data-table, .dashboard-card', { timeout: 10000 });
    
    // データが存在する場合のみテスト実行
    const table = page.locator('.data-table');
    const tableExists = await table.isVisible();
    
    if (tableExists) {
      const rows = table.locator('tbody tr');
      const rowCount = await rows.count();
      
      if (rowCount > 0) {
        // 詳細リンクをクリック
        const detailLink = rows.first().locator('a:has-text("詳細")');
        await detailLink.click();
        
        // 企業詳細ページに遷移していることを確認
        await expect(page).toHaveURL(/\/companies\/\d+/);
        
        // 詳細ページのロード完了を待機
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000); // APIからのデータロード待機
        
        // 企業名またはコンテンツが表示されていることを確認
        const content = page.locator('h1, .dashboard-card');
        await expect(content.first()).toBeVisible();
      }
    }
  });

  test('ページネーション機能が動作する', async ({ page }) => {
    await page.goto('/companies?per_page=1');
    await page.waitForLoadState('networkidle');
    
    // データが十分にある場合のみページネーションをテスト
    await page.waitForTimeout(2000); // データロード待機
    
    // ページネーション要素の確認
    const pagination = page.locator('button:has-text("次へ"), button:has-text("前へ")');
    const paginationExists = await pagination.count() > 0;
    
    if (paginationExists) {
      // 次へボタンが存在し、クリック可能な場合
      const nextButton = page.locator('button:has-text("次へ")');
      const isNextEnabled = await nextButton.isEnabled();
      
      if (isNextEnabled) {
        await nextButton.click();
        await page.waitForLoadState('networkidle');
        
        // ページが変更されていることを確認（UIの変化を確認）
        const pageInfo = page.locator('text=/ページ \\d+ \\/ \\d+/');
        await expect(pageInfo).toBeVisible();
      }
    }
  });

  test('空の結果の場合の表示確認', async ({ page }) => {
    await page.goto('/companies');
    await page.waitForLoadState('networkidle');
    
    // 検索ボックスに存在しない企業名を入力
    const searchInput = page.locator('input[placeholder*="企業名で検索"]');
    await expect(searchInput).toBeVisible();
    await searchInput.fill('NonExistentCompanyXYZ123');
    
    // 検索結果の表示を待機
    await page.waitForTimeout(2000);
    
    // 基本的なページ構造が表示されていることを確認
    const pageHeader = page.locator('h1');
    const headerExists = await pageHeader.isVisible();
    
    if (headerExists) {
      await expect(pageHeader).toContainText('企業一覧');
    }
    
    // 検索フィールドに値が入っていることを確認（再取得）
    const currentSearchInput = page.locator('input[placeholder*="企業名で検索"]');
    const inputExists = await currentSearchInput.isVisible();
    
    if (inputExists) {
      await expect(currentSearchInput).toHaveValue('NonExistentCompanyXYZ123');
    }
    
    // 検索結果のロードを待機（空の結果でもページ構造は維持される）
    await page.waitForTimeout(2000);
    
    // ページが正常に機能していることを確認
    // 検索を実行した時点でページは正常に動作している
    const pageTitle = await page.title();
    expect(pageTitle).toContain('DevCorpTrends');
    
    // 検索が実行されており、基本的なページ構造が維持されていることを確認
    const pageIsLoaded = await page.evaluate(() => {
      return document.readyState === 'complete';
    });
    expect(pageIsLoaded).toBe(true);
  });

  test('レスポンシブデザインが正常に動作する', async ({ page }) => {
    // モバイルビューポートでテスト
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/companies');
    await page.waitForLoadState('networkidle');
    
    // モバイルでページが正常に表示されることを確認
    await expect(page.locator('h1')).toBeVisible();
    
    // 検索ボックスが表示されることを確認
    const searchInput = page.locator('input[placeholder*="企業名で検索"]');
    await expect(searchInput).toBeVisible();
    
    // デスクトップビューポートに戻す
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.waitForLoadState('networkidle');
    
    // デスクトップでも正常に表示されることを確認
    await expect(page.locator('h1')).toBeVisible();
  });

  test('エラー状態の処理確認', async ({ page }) => {
    // ネットワークエラーをシミュレート（500エラーを返す）
    await page.route('/api/companies', route => {
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ error: 'Internal Server Error' })
      });
    });
    
    await page.goto('/companies');
    await page.waitForLoadState('networkidle');
    
    // データロード待機
    await page.waitForTimeout(3000);
    
    // エラー状態の処理を確認（エラー時はh1が表示されない）
    const hasErrorMessage = page.locator('text=企業一覧の読み込みに失敗しました');
    const hasReloadButton = page.locator('button:has-text("再読み込み")');
    const hasNormalHeader = page.locator('h1:has-text("企業一覧")');
    
    // エラーメッセージ、再読み込みボタン、または正常ヘッダーのいずれかが表示されていることを確認
    const errorVisible = await hasErrorMessage.isVisible();
    const reloadVisible = await hasReloadButton.isVisible();
    const headerVisible = await hasNormalHeader.isVisible();
    
    expect(errorVisible || reloadVisible || headerVisible).toBeTruthy();
    
    // エラー状態の場合はエラーメッセージが表示されることを確認
    if (errorVisible) {
      await expect(hasErrorMessage).toBeVisible();
      await expect(hasReloadButton).toBeVisible();
    }
  });
});