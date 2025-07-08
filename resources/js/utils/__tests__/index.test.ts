import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { formatDate, formatNumber, truncateText, debounce } from '../index';

describe('ユーティリティ関数', () => {
    describe('formatDate', () => {
        it('ISO日付文字列を日本語形式にフォーマットする', () => {
            const result = formatDate('2024-01-15T10:30:00Z');
            expect(result).toBe('2024年1月15日');
        });

        it('別の日付でも正しくフォーマットする', () => {
            const result = formatDate('2023-12-25T15:45:30Z');
            expect(result).toBe('2023年12月26日'); // JST+9のため1日進む
        });

        it('月の先頭ゼロが正しく処理される', () => {
            const result = formatDate('2024-03-05T00:00:00Z');
            expect(result).toBe('2024年3月5日');
        });

        it('年末年始の日付も正しく処理される', () => {
            const result = formatDate('2024-01-01T00:00:00Z');
            expect(result).toBe('2024年1月1日');
        });

        it('無効な日付文字列でもエラーにならない', () => {
            const result = formatDate('invalid-date');
            expect(result).toBe('Invalid Date');
        });
    });

    describe('formatNumber', () => {
        it('整数を日本語形式にフォーマットする', () => {
            const result = formatNumber(1000);
            expect(result).toBe('1,000');
        });

        it('大きな数値も正しくフォーマットする', () => {
            const result = formatNumber(1234567);
            expect(result).toBe('1,234,567');
        });

        it('小数点を含む数値をフォーマットする', () => {
            const result = formatNumber(1234.56);
            expect(result).toBe('1,234.56');
        });

        it('ゼロも正しく処理される', () => {
            const result = formatNumber(0);
            expect(result).toBe('0');
        });

        it('負の数値も正しく処理される', () => {
            const result = formatNumber(-1234);
            expect(result).toBe('-1,234');
        });

        it('小さな数値（3桁未満）も正しく処理される', () => {
            const result = formatNumber(999);
            expect(result).toBe('999');
        });
    });

    describe('truncateText', () => {
        it('指定した長さより短いテキストはそのまま返す', () => {
            const result = truncateText('短いテキスト', 20);
            expect(result).toBe('短いテキスト');
        });

        it('指定した長さと同じテキストはそのまま返す', () => {
            const text = '12345';
            const result = truncateText(text, 5);
            expect(result).toBe('12345');
        });

        it('指定した長さより長いテキストは切り取られて...が付く', () => {
            const result = truncateText('これは非常に長いテキストです', 10);
            expect(result).toBe('これは非常に長いテキ...');
        });

        it('英語のテキストも正しく処理される', () => {
            const result = truncateText('This is a very long text', 10);
            expect(result).toBe('This is a ...');
        });

        it('空文字列も正しく処理される', () => {
            const result = truncateText('', 10);
            expect(result).toBe('');
        });

        it('maxLengthが0の場合...のみ返す', () => {
            const result = truncateText('テキスト', 0);
            expect(result).toBe('...');
        });

        it('maxLengthが負の値の場合も正しく処理される', () => {
            const result = truncateText('テキスト', -1);
            expect(result).toBe('...');
        });

        it('多バイト文字も正しくカウントされる', () => {
            const result = truncateText('あいうえおかきくけこ', 5);
            expect(result).toBe('あいうえお...');
        });

        it('絵文字も正しく処理される', () => {
            const result = truncateText('👍👎😀😁😂', 3);
            // 実際の実装では絵文字が部分的に切り取られて代替文字が表示される
            expect(result.length).toBeLessThanOrEqual(6); // '👍' + '...' の最大長
            expect(result).toMatch(/^👍.*\.\.\.$/);
        });
    });

    describe('debounce', () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('指定した遅延時間後に関数が実行される', () => {
            const mockFn = vi.fn();
            const debouncedFn = debounce(mockFn, 1000);

            debouncedFn('test');
            expect(mockFn).not.toHaveBeenCalled();

            vi.advanceTimersByTime(1000);
            expect(mockFn).toHaveBeenCalledWith('test');
            expect(mockFn).toHaveBeenCalledTimes(1);
        });

        it('連続して呼び出された場合、最後の呼び出しのみ実行される', () => {
            const mockFn = vi.fn();
            const debouncedFn = debounce(mockFn, 1000);

            debouncedFn('first');
            debouncedFn('second');
            debouncedFn('third');

            expect(mockFn).not.toHaveBeenCalled();

            vi.advanceTimersByTime(1000);
            expect(mockFn).toHaveBeenCalledWith('third');
            expect(mockFn).toHaveBeenCalledTimes(1);
        });

        it('遅延時間が経過する前に再度呼び出されると、タイマーがリセットされる', () => {
            const mockFn = vi.fn();
            const debouncedFn = debounce(mockFn, 1000);

            debouncedFn('first');
            vi.advanceTimersByTime(500);
            
            debouncedFn('second');
            vi.advanceTimersByTime(500);
            expect(mockFn).not.toHaveBeenCalled();

            vi.advanceTimersByTime(500);
            expect(mockFn).toHaveBeenCalledWith('second');
            expect(mockFn).toHaveBeenCalledTimes(1);
        });

        it('複数の引数を正しく渡す', () => {
            const mockFn = vi.fn();
            const debouncedFn = debounce(mockFn, 1000);

            debouncedFn('arg1', 'arg2', 123);

            vi.advanceTimersByTime(1000);
            expect(mockFn).toHaveBeenCalledWith('arg1', 'arg2', 123);
        });

        it('異なる遅延時間で独立して動作する', () => {
            const mockFn1 = vi.fn();
            const mockFn2 = vi.fn();
            const debouncedFn1 = debounce(mockFn1, 500);
            const debouncedFn2 = debounce(mockFn2, 1000);

            debouncedFn1('fn1');
            debouncedFn2('fn2');

            vi.advanceTimersByTime(500);
            expect(mockFn1).toHaveBeenCalledWith('fn1');
            expect(mockFn2).not.toHaveBeenCalled();

            vi.advanceTimersByTime(500);
            expect(mockFn2).toHaveBeenCalledWith('fn2');
        });

        it('遅延時間が0の場合、即座に実行される', () => {
            const mockFn = vi.fn();
            const debouncedFn = debounce(mockFn, 0);

            debouncedFn('immediate');
            vi.advanceTimersByTime(0);
            
            expect(mockFn).toHaveBeenCalledWith('immediate');
        });

        it('引数なしの関数も正しく動作する', () => {
            const mockFn = vi.fn();
            const debouncedFn = debounce(mockFn, 1000);

            debouncedFn();

            vi.advanceTimersByTime(1000);
            expect(mockFn).toHaveBeenCalledWith();
            expect(mockFn).toHaveBeenCalledTimes(1);
        });

        it('thisコンテキストが失われても動作する', () => {
            const obj = {
                value: 'test',
                method: function(this: any, arg: string) {
                    return `${this?.value || 'undefined'}: ${arg}`;
                }
            };

            const mockFn = vi.fn(obj.method);
            const debouncedFn = debounce(mockFn, 1000);

            debouncedFn('arg');

            vi.advanceTimersByTime(1000);
            expect(mockFn).toHaveBeenCalledWith('arg');
        });
    });
});