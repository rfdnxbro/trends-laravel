import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  // コンソールエラーを収集
  const consoleMessages = [];
  page.on('console', msg => {
    consoleMessages.push(`${msg.type()}: ${msg.text()}`);
  });
  
  const errors = [];
  page.on('pageerror', error => {
    errors.push(error.message);
  });
  
  // ネットワークリクエストを監視
  const networkRequests = [];
  page.on('request', request => {
    networkRequests.push(`${request.method()} ${request.url()}`);
  });
  
  const networkResponses = [];
  const apiResponses = [];
  page.on('response', async response => {
    networkResponses.push(`${response.status()} ${response.url()}`);
    if (response.url().includes('/api/articles/1')) {
      try {
        const responseData = await response.text();
        apiResponses.push(responseData);
      } catch (e) {
        apiResponses.push('Failed to read response');
      }
    }
  });
  
  try {
    await page.goto('http://127.0.0.1:8000/articles/1');
    await page.waitForTimeout(5000); // 5秒待機
    
    console.log('=== ページタイトル ===');
    console.log(await page.title());
    
    console.log('\n=== body内容 ===');
    const bodyContent = await page.locator('body').innerHTML();
    console.log(bodyContent.substring(0, 1000) + '...');
    
    console.log('\n=== コンソールメッセージ ===');
    consoleMessages.forEach(msg => console.log(msg));
    
    console.log('\n=== エラー ===');
    errors.forEach(err => console.log(err));
    
    console.log('\n=== h1要素の存在確認 ===');
    const h1Count = await page.locator('h1').count();
    console.log(`h1要素の数: ${h1Count}`);
    
    if (h1Count > 0) {
      // タイトルに実際のテキストが入るまで待機
      try {
        await page.waitForFunction(() => {
          const h1 = document.querySelector('h1');
          return h1 && h1.textContent && h1.textContent.trim().length > 0;
        }, { timeout: 10000 });
      } catch (e) {
        console.log('タイトルの読み込みがタイムアウトしました');
      }
      
      const h1Text = await page.locator('h1').first().textContent();
      console.log(`h1テキスト: "${h1Text}"`);
    }
    
    console.log('\n=== ネットワークリクエスト ===');
    networkRequests.forEach(req => console.log(req));
    
    console.log('\n=== ネットワークレスポンス ===');
    networkResponses.forEach(res => console.log(res));
    
    console.log('\n=== APIレスポンス内容 ===');
    apiResponses.forEach(res => console.log(res.substring(0, 300) + '...'));
    
    console.log('\n=== ローディング・エラー状態の確認 ===');
    const loadingText = await page.locator('text=記事を読み込み中').count();
    const errorText = await page.locator('text=記事の読み込みに失敗').count();
    console.log(`ローディングテキスト: ${loadingText}`);
    console.log(`エラーテキスト: ${errorText}`);
    
    console.log('\n=== 現在のURL ===');
    console.log(await page.url());
    
    console.log('\n=== メインコンテンツエリア ===');
    const mainContent = await page.locator('main, .main-content, .dashboard-card').count();
    console.log(`メインコンテンツエリア数: ${mainContent}`);
    
    console.log('\n=== article詳細関連の要素 ===');
    const articleElements = await page.locator('[class*="article"], [data-testid*="article"]').count();
    console.log(`記事関連要素数: ${articleElements}`);
    
    console.log('\n=== 全体のテキスト内容 ===');
    const fullText = await page.locator('body').textContent();
    console.log('Body text:', fullText?.substring(0, 1000) + '...');
    
  } catch (error) {
    console.error('エラーが発生しました:', error);
  }
  
  await browser.close();
})();