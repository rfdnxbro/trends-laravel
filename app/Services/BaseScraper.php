<?php

namespace App\Services;

use App\Contracts\ScrapingService;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseScraper implements ScrapingService
{
    protected int $timeout;

    protected int $maxRetries;

    protected int $delaySeconds;

    protected int $requestsPerMinute;

    protected array $headers = [];

    protected ?Response $lastResponse = null;

    protected array $errorLog = [];

    protected string $userAgent = 'DevCorpTrends/1.0';

    public function __construct()
    {
        $this->timeout = config('constants.api.timeout_seconds');
        $this->maxRetries = config('constants.api.max_retry_count');
        $this->delaySeconds = config('constants.api.retry_delay_seconds');
        $this->requestsPerMinute = config('constants.api.rate_limit_per_minute');

        $this->setDefaultHeaders();
    }

    /**
     * スクレイピングを実行する
     */
    public function scrape(string $url, array $options = []): array
    {
        $this->enforceRateLimit();

        $attempt = 0;
        $exception = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->makeRequest($url, $options);
                $this->lastResponse = $response;

                if ($response->successful()) {
                    $data = $this->parseResponse($response);
                    $this->logSuccess($url, $data);

                    return $data;
                }

                throw new Exception('HTTP Error: '.$response->status());
            } catch (Exception $e) {
                $attempt++;
                $exception = $e;

                $this->logError($url, $e, $attempt);

                if ($attempt < $this->maxRetries) {
                    sleep($this->delaySeconds);
                }
            }
        }

        throw $exception;
    }

    /**
     * レート制限を設定する
     */
    public function setRateLimit(int $requestsPerMinute): void
    {
        $this->requestsPerMinute = $requestsPerMinute;
    }

    /**
     * リトライ設定を行う
     */
    public function setRetryOptions(int $maxRetries, int $delaySeconds): void
    {
        $this->maxRetries = $maxRetries;
        $this->delaySeconds = $delaySeconds;
    }

    /**
     * HTTPヘッダーを設定する
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * タイムアウト設定を行う
     */
    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }

    /**
     * 最後のリクエストの情報を取得する
     */
    public function getLastResponse(): ?array
    {
        if (! $this->lastResponse) {
            return null;
        }

        return [
            'status' => $this->lastResponse->status(),
            'headers' => $this->lastResponse->headers(),
            'body' => $this->lastResponse->body(),
        ];
    }

    /**
     * エラーログを取得する
     */
    public function getErrorLog(): array
    {
        return $this->errorLog;
    }

    /**
     * デフォルトヘッダーを設定
     */
    protected function setDefaultHeaders(): void
    {
        $this->headers = [
            'User-Agent' => $this->userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ja,en-US;q=0.7,en;q=0.3',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
        ];
    }

    /**
     * HTTPリクエストを実行
     */
    protected function makeRequest(string $url, array $options = []): Response
    {
        $httpClient = Http::withHeaders($this->headers)
            ->timeout($this->timeout);

        return $httpClient->get($url, $options);
    }

    /**
     * レート制限を適用
     */
    protected function enforceRateLimit(): void
    {
        $cacheKey = 'rate_limit_'.class_basename($this);
        $requests = Cache::get($cacheKey, 0);

        if ($requests >= $this->requestsPerMinute) {
            $waitTime = config('constants.api.rate_limit_window_seconds') - (time() % config('constants.api.rate_limit_window_seconds'));
            Log::info("Rate limit exceeded. Waiting {$waitTime} seconds.", [
                'scraper' => class_basename($this),
                'requests' => $requests,
                'limit' => $this->requestsPerMinute,
            ]);
            sleep($waitTime);
            Cache::forget($cacheKey);
        }

        Cache::put($cacheKey, $requests + 1, config('constants.api.rate_limit_window_seconds'));
    }

    /**
     * 成功ログを記録
     */
    protected function logSuccess(string $url, array $data): void
    {
        Log::info('Scraping successful', [
            'scraper' => class_basename($this),
            'url' => $url,
            'data_count' => count($data),
        ]);
    }

    /**
     * エラーログを記録
     */
    protected function logError(string $url, Exception $exception, int $attempt): void
    {
        $errorData = [
            'scraper' => class_basename($this),
            'url' => $url,
            'error' => $exception->getMessage(),
            'attempt' => $attempt,
            'timestamp' => now()->toDateTimeString(),
        ];

        $this->errorLog[] = $errorData;

        Log::error('Scraping failed', $errorData);
    }

    /**
     * 設定ベースの優先順位付きセレクタ戦略による要素抽出
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node  検索対象ノード
     * @param  string  $selectorType  セレクタタイプ（title, author, datetime, engagement等）
     * @param  string  $extractionType  抽出タイプ（text, link, number）
     * @param  array  $options  追加オプション
     * @return mixed 抽出結果またはnull
     */
    protected function extractByStrategies(
        \Symfony\Component\DomCrawler\Crawler $node,
        string $selectorType,
        string $extractionType = 'text',
        array $options = []
    ) {
        $selectors = $this->getSelectorsFromConfig($selectorType);

        foreach ($selectors as $categoryName => $categorySelectors) {
            Log::debug("Testing selector category: {$categoryName} for {$selectorType}");
            
            $result = $this->trySelectorsInCategory($node, $categorySelectors, $extractionType, $options, $selectorType);
            if ($result !== null) {
                return $result;
            }
        }

        Log::debug("No {$selectorType} found with any configured selectors");
        return null;
    }

    /**
     * カテゴリ内のセレクタを試行
     *
     * @param \Symfony\Component\DomCrawler\Crawler $node
     * @param array $selectors
     * @param string $extractionType
     * @param array $options
     * @param string $selectorType
     * @return mixed
     */
    private function trySelectorsInCategory(
        \Symfony\Component\DomCrawler\Crawler $node,
        array $selectors,
        string $extractionType,
        array $options,
        string $selectorType
    ) {
        foreach ($selectors as $selector) {
            $result = $this->trySelector($node, $selector, $extractionType, $options, $selectorType);
            if ($result !== null) {
                return $result;
            }
        }
        
        return null;
    }

    /**
     * 単一セレクタを試行
     *
     * @param \Symfony\Component\DomCrawler\Crawler $node
     * @param string $selector
     * @param string $extractionType
     * @param array $options
     * @param string $selectorType
     * @return mixed
     */
    private function trySelector(
        \Symfony\Component\DomCrawler\Crawler $node,
        string $selector,
        string $extractionType,
        array $options,
        string $selectorType
    ) {
        try {
            $elements = $node->filter($selector);
            if ($elements->count() === 0) {
                return null;
            }

            Log::debug("Found {$elements->count()} elements with selector: {$selector}");

            $result = $this->extractFromElement($elements, $extractionType, $options);
            if ($result === null) {
                return null;
            }

            Log::debug("Successfully extracted {$selectorType} using selector: {$selector}", [
                'result' => is_string($result) ? substr($result, 0, 100) : $result,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::debug("Error with selector {$selector}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * 設定からセレクタ配列を取得
     *
     * @param  string  $selectorType  セレクタタイプ
     * @return array セレクタ配列（優先順位順）
     */
    protected function getSelectorsFromConfig(string $selectorType): array
    {
        $configSelectors = config("scraping.selectors.{$selectorType}", []);

        // 設定された優先順位順でセレクタを平坦化
        $flatSelectors = [];
        foreach ($configSelectors as $category => $selectors) {
            $flatSelectors[$category] = $selectors;
        }

        return $flatSelectors;
    }

    /**
     * 要素から指定された方法でデータを抽出
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $elements  対象要素
     * @param  string  $extractionType  抽出タイプ
     * @param  array  $options  追加オプション
     * @return mixed 抽出結果またはnull
     */
    protected function extractFromElement(
        \Symfony\Component\DomCrawler\Crawler $elements,
        string $extractionType,
        array $options = []
    ) {
        switch ($extractionType) {
            case 'text':
                return $this->extractTextFromElement($elements, $options);
            case 'link':
                return $this->extractLinkFromElement($elements, $options);
            case 'number':
                return $this->extractNumberFromElement($elements, $options);
            case 'attribute':
                return $this->extractAttributeFromElement($elements, $options);
            default:
                return null;
        }
    }

    /**
     * 要素からテキストを抽出
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $elements  対象要素
     * @param  array  $options  追加オプション
     * @return string|null 抽出されたテキストまたはnull
     */
    protected function extractTextFromElement(
        \Symfony\Component\DomCrawler\Crawler $elements,
        array $options = []
    ): ?string {
        try {
            $text = trim($elements->text());

            // 最大長制限
            $maxLength = $options['max_length'] ?? 500;
            if (strlen($text) > $maxLength) {
                Log::debug('Text too long, truncating', ['original_length' => strlen($text), 'max_length' => $maxLength]);

                return null;
            }

            // 最小長制限
            $minLength = $options['min_length'] ?? 1;
            if (strlen($text) < $minLength) {
                return null;
            }

            return ! empty($text) ? $text : null;
        } catch (\Exception $e) {
            Log::debug('Text extraction error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * 要素からリンク（href属性）を抽出
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $elements  対象要素
     * @param  array  $options  追加オプション（base_url, path_pattern等）
     * @return string|null 抽出されたURLまたはnull
     */
    protected function extractLinkFromElement(
        \Symfony\Component\DomCrawler\Crawler $elements,
        array $options = []
    ): ?string {
        try {
            $href = $elements->attr('href');
            if (! $href) {
                return null;
            }

            if (!$this->isValidHref($href, $options)) {
                return null;
            }

            return $this->normalizeHref($href, $options);
        } catch (\Exception $e) {
            Log::debug('Link extraction error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * hrefが有効かチェック
     *
     * @param string $href
     * @param array $options
     * @return bool
     */
    private function isValidHref(string $href, array $options): bool
    {
        // パスパターンチェック
        if (isset($options['path_pattern']) && strpos($href, $options['path_pattern']) === false) {
            return false;
        }

        // 除外パターンチェック
        if (isset($options['exclude_patterns'])) {
            foreach ($options['exclude_patterns'] as $excludePattern) {
                if (strpos($href, $excludePattern) !== false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * hrefを正規化
     *
     * @param string $href
     * @param array $options
     * @return string
     */
    private function normalizeHref(string $href, array $options): string
    {
        // 絶対URLの場合はそのまま返す
        if (strpos($href, 'http') === 0) {
            return $href;
        }

        // 相対URLの場合はベースURLと結合
        $baseUrl = $options['base_url'] ?? '';
        return $baseUrl ? $baseUrl.$href : $href;
    }

    /**
     * 要素から数値を抽出
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $elements  対象要素
     * @param  array  $options  追加オプション
     * @return int 抽出された数値（デフォルト0）
     */
    protected function extractNumberFromElement(
        \Symfony\Component\DomCrawler\Crawler $elements,
        array $options = []
    ): int {
        try {
            // aria-label属性を優先的にチェック
            $text = $elements->attr('aria-label') ?: $elements->text();

            if ($text) {
                // 数値以外の文字を除去して数値のみ抽出
                $number = (int) preg_replace('/[^0-9]/', '', $text);

                // 最小値チェック
                $minValue = $options['min_value'] ?? 0;
                if ($number < $minValue) {
                    return 0;
                }

                return $number;
            }
        } catch (\Exception $e) {
            Log::debug('Number extraction error', ['error' => $e->getMessage()]);
        }

        return 0;
    }

    /**
     * 要素から指定された属性を抽出
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $elements  対象要素
     * @param  array  $options  追加オプション（attribute_name等）
     * @return string|null 抽出された属性値またはnull
     */
    protected function extractAttributeFromElement(
        \Symfony\Component\DomCrawler\Crawler $elements,
        array $options = []
    ): ?string {
        try {
            $attributeName = $options['attribute_name'] ?? 'title';
            $value = $elements->attr($attributeName);

            return ! empty($value) ? trim($value) : null;
        } catch (\Exception $e) {
            Log::debug('Attribute extraction error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * 複数のセレクタでテキストを抽出（後方互換性のため）
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node  検索対象ノード
     * @param  array  $selectors  セレクタ配列
     * @param  string  $logType  ログ用の種別名
     * @return string|null 見つかったテキストまたはnull
     */
    protected function extractTextBySelectors(
        \Symfony\Component\DomCrawler\Crawler $node,
        array $selectors,
        string $logType = ''
    ): ?string {
        try {
            foreach ($selectors as $selector) {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $text = trim($element->text());
                    if (! empty($text)) {
                        if ($logType) {
                            Log::debug("{$logType}抽出成功", [
                                'selector' => $selector,
                                'text' => substr($text, 0, 100),
                            ]);
                        }

                        return $text;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("テキスト抽出エラー ({$logType})", ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * 複数のセレクタでリンクを抽出（後方互換性のため）
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node  検索対象ノード
     * @param  array  $selectors  セレクタ配列
     * @param  string  $pathPattern  パスパターン
     * @param  string  $baseUrl  ベースURL
     * @return string|null 見つかったリンクまたはnull
     */
    protected function extractLinkBySelectors(
        \Symfony\Component\DomCrawler\Crawler $node,
        array $selectors,
        string $pathPattern = '',
        string $baseUrl = ''
    ): ?string {
        try {
            foreach ($selectors as $selector) {
                $linkElement = $node->filter($selector);
                if ($linkElement->count() > 0) {
                    $href = $linkElement->attr('href');
                    if ($href) {
                        // パスパターンチェック
                        if ($pathPattern && strpos($href, $pathPattern) === false) {
                            continue;
                        }

                        // 絶対URLの場合はそのまま返す
                        if (strpos($href, 'http') === 0) {
                            return $href;
                        }

                        // 相対URLの場合はベースURLと結合
                        return $baseUrl ? $baseUrl.$href : $href;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('リンク抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * 複数のセレクタで数値を抽出（後方互換性のため）
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node  検索対象ノード
     * @param  array  $selectors  セレクタ配列
     * @return int 見つかった数値（デフォルト0）
     */
    protected function extractNumberBySelectors(
        \Symfony\Component\DomCrawler\Crawler $node,
        array $selectors
    ): int {
        try {
            foreach ($selectors as $selector) {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $text = $element->attr('aria-label') ?: $element->text();
                    if ($text) {
                        $number = (int) preg_replace('/[^0-9]/', '', $text);
                        if ($number > 0) {
                            return $number;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('数値抽出エラー', ['error' => $e->getMessage()]);
        }

        return 0;
    }

    /**
     * レスポンスを解析（子クラスで実装）
     */
    abstract protected function parseResponse(Response $response): array;
}
