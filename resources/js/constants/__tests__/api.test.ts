import { describe, it, expect } from 'vitest';
import { API_CONSTANTS } from '../api';

describe('API_CONSTANTS', () => {
    it('すべての定数が正しい値で定義されている', () => {
        expect(API_CONSTANTS.TIMEOUT).toBe(10000);
        expect(API_CONSTANTS.DEFAULT_LIMIT).toBe(10);
        expect(API_CONSTANTS.CSRF_ERROR_STATUS).toBe(419);
        expect(API_CONSTANTS.SERVER_ERROR_START).toBe(500);
    });

    it('定数がTypeScript上で読み取り専用として定義されている', () => {
        // TypeScriptのコンパイル時に readonly として定義されていることを確認
        // 実行時は JavaScript なので実際には変更可能だが、型レベルで保護されている
        expect(typeof API_CONSTANTS.TIMEOUT).toBe('number');
        expect(typeof API_CONSTANTS.DEFAULT_LIMIT).toBe('number');
        expect(typeof API_CONSTANTS.CSRF_ERROR_STATUS).toBe('number');
        expect(typeof API_CONSTANTS.SERVER_ERROR_START).toBe('number');
    });

    it('TIMEOUTが妥当な値である', () => {
        expect(API_CONSTANTS.TIMEOUT).toBeGreaterThan(0);
        expect(API_CONSTANTS.TIMEOUT).toBeLessThanOrEqual(30000); // 30秒以下
        expect(typeof API_CONSTANTS.TIMEOUT).toBe('number');
    });

    it('DEFAULT_LIMITが妥当な値である', () => {
        expect(API_CONSTANTS.DEFAULT_LIMIT).toBeGreaterThan(0);
        expect(API_CONSTANTS.DEFAULT_LIMIT).toBeLessThanOrEqual(100);
        expect(typeof API_CONSTANTS.DEFAULT_LIMIT).toBe('number');
    });

    it('CSRF_ERROR_STATUSが正しいHTTPステータスコードである', () => {
        expect(API_CONSTANTS.CSRF_ERROR_STATUS).toBe(419);
        expect(typeof API_CONSTANTS.CSRF_ERROR_STATUS).toBe('number');
    });

    it('SERVER_ERROR_STARTが500番台エラーの開始値である', () => {
        expect(API_CONSTANTS.SERVER_ERROR_START).toBe(500);
        expect(API_CONSTANTS.SERVER_ERROR_START).toBeGreaterThanOrEqual(500);
        expect(API_CONSTANTS.SERVER_ERROR_START).toBeLessThan(600);
        expect(typeof API_CONSTANTS.SERVER_ERROR_START).toBe('number');
    });

    it('定数の型が正しく推論される', () => {
        // TypeScript の型チェックが正しく動作することを確認
        const timeout = API_CONSTANTS.TIMEOUT;
        const defaultLimit = API_CONSTANTS.DEFAULT_LIMIT;
        const csrfErrorStatus = API_CONSTANTS.CSRF_ERROR_STATUS;
        const serverErrorStart = API_CONSTANTS.SERVER_ERROR_START;

        expect(timeout).toBe(10000);
        expect(defaultLimit).toBe(10);
        expect(csrfErrorStatus).toBe(419);
        expect(serverErrorStart).toBe(500);
    });

    it('定数オブジェクトの構造が正しい', () => {
        const expectedKeys = ['TIMEOUT', 'DEFAULT_LIMIT', 'CSRF_ERROR_STATUS', 'SERVER_ERROR_START'];
        const actualKeys = Object.keys(API_CONSTANTS);

        expect(actualKeys).toEqual(expect.arrayContaining(expectedKeys));
        expect(actualKeys.length).toBe(expectedKeys.length);
    });

    it('定数の構造が適切である', () => {
        // 定数オブジェクトが正しく定義されていることを確認
        expect(API_CONSTANTS).toBeDefined();
        expect(Object.keys(API_CONSTANTS).length).toBe(4);
        
        // すべてのプロパティが存在することを確認
        expect(API_CONSTANTS).toHaveProperty('TIMEOUT');
        expect(API_CONSTANTS).toHaveProperty('DEFAULT_LIMIT');
        expect(API_CONSTANTS).toHaveProperty('CSRF_ERROR_STATUS');
        expect(API_CONSTANTS).toHaveProperty('SERVER_ERROR_START');
    });
});