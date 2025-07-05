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
    protected int $timeout = 30;

    protected int $maxRetries = 3;

    protected int $delaySeconds = 1;

    protected int $requestsPerMinute = 30;

    protected array $headers = [];

    protected ?Response $lastResponse = null;

    protected array $errorLog = [];

    protected string $userAgent = 'DevCorpTrends/1.0';

    public function __construct()
    {
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
            $waitTime = 60 - (time() % 60);
            Log::info("Rate limit exceeded. Waiting {$waitTime} seconds.", [
                'scraper' => class_basename($this),
                'requests' => $requests,
                'limit' => $this->requestsPerMinute,
            ]);
            sleep($waitTime);
            Cache::forget($cacheKey);
        }

        Cache::put($cacheKey, $requests + 1, 60);
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
     * レスポンスを解析（子クラスで実装）
     */
    abstract protected function parseResponse(Response $response): array;
}
