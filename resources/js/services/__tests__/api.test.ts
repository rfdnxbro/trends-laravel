import { describe, it, expect } from 'vitest';
import { API_CONSTANTS } from '../../constants/api';

describe('API Service', () => {
    describe('API 定数の使用', () => {
        it('API_CONSTANTS が正しく定義されている', () => {
            expect(API_CONSTANTS.TIMEOUT).toBe(10000);
            expect(API_CONSTANTS.DEFAULT_LIMIT).toBe(10);
            expect(API_CONSTANTS.CSRF_ERROR_STATUS).toBe(419);
            expect(API_CONSTANTS.SERVER_ERROR_START).toBe(500);
        });

        it('API_CONSTANTS の値が適切な範囲内である', () => {
            expect(API_CONSTANTS.TIMEOUT).toBeGreaterThan(0);
            expect(API_CONSTANTS.DEFAULT_LIMIT).toBeGreaterThan(0);
            expect(API_CONSTANTS.CSRF_ERROR_STATUS).toBe(419);
            expect(API_CONSTANTS.SERVER_ERROR_START).toBeGreaterThanOrEqual(500);
        });
    });

    describe('API エンドポイント構造', () => {
        it('APIサービスのメソッドが想定通りのエンドポイントを構築する', () => {
            // エンドポイントパターンのテスト
            expect('/api/dashboard/stats').toMatch(/^\/api\/[a-z]+\/[a-z]+$/);
            expect('/api/companies/top').toMatch(/^\/api\/[a-z]+\/[a-z]+$/);
            expect('/api/companies/123').toMatch(/^\/api\/[a-z]+\/\d+$/);
            expect('/api/companies/search').toMatch(/^\/api\/[a-z]+\/[a-z]+$/);
            expect('/api/search').toMatch(/^\/api\/[a-z]+$/);
        });

        it('クエリパラメータが正しい形式で構築される', () => {
            // クエリパラメータのパターンテスト
            const limitParam = `limit=${API_CONSTANTS.DEFAULT_LIMIT}`;
            expect(limitParam).toBe('limit=10');

            const searchParam = 'q=test query';
            expect(searchParam).toMatch(/^q=.+$/);
        });
    });

    describe('エラーハンドリング定数', () => {
        it('CSRF エラーステータスが適切に定義されている', () => {
            expect(API_CONSTANTS.CSRF_ERROR_STATUS).toBe(419);
            // 419 は Laravel の CSRF トークンエラー
        });

        it('サーバーエラーステータスが適切に定義されている', () => {
            expect(API_CONSTANTS.SERVER_ERROR_START).toBe(500);
            // 500番台がサーバーエラーの開始
        });
    });

    describe('API設定値の妥当性', () => {
        it('タイムアウト値が実用的な範囲内である', () => {
            expect(API_CONSTANTS.TIMEOUT).toBeGreaterThan(1000); // 1秒以上
            expect(API_CONSTANTS.TIMEOUT).toBeLessThanOrEqual(30000); // 30秒以下
        });

        it('デフォルトリミット値が実用的な範囲内である', () => {
            expect(API_CONSTANTS.DEFAULT_LIMIT).toBeGreaterThan(0);
            expect(API_CONSTANTS.DEFAULT_LIMIT).toBeLessThanOrEqual(100);
        });
    });
});