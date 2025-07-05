<?php

namespace App\Contracts;

interface ScrapingService
{
    /**
     * スクレイピングを実行する
     */
    public function scrape(string $url, array $options = []): array;

    /**
     * レート制限を設定する
     */
    public function setRateLimit(int $requestsPerMinute): void;

    /**
     * リトライ設定を行う
     */
    public function setRetryOptions(int $maxRetries, int $delaySeconds): void;

    /**
     * HTTPヘッダーを設定する
     */
    public function setHeaders(array $headers): void;

    /**
     * タイムアウト設定を行う
     */
    public function setTimeout(int $seconds): void;

    /**
     * 最後のリクエストの情報を取得する
     */
    public function getLastResponse(): ?array;

    /**
     * エラーログを取得する
     */
    public function getErrorLog(): array;
}
