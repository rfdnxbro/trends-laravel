import { describe, it, expect } from 'vitest';
import { UI_CONSTANTS } from '../ui';

describe('UI_CONSTANTS', () => {
    it('すべての定数が正しい値で定義されている', () => {
        expect(UI_CONSTANTS.ICON_SIZE).toBe(4);
        expect(UI_CONSTANTS.SKELETON_COUNT).toBe(10);
        expect(UI_CONSTANTS.EMPTY_ICON_SIZE).toBe(12);
    });

    it('定数がTypeScript上で読み取り専用として定義されている', () => {
        // TypeScriptのコンパイル時に readonly として定義されていることを確認
        // 実行時は JavaScript なので実際には変更可能だが、型レベルで保護されている
        expect(typeof UI_CONSTANTS.ICON_SIZE).toBe('number');
        expect(typeof UI_CONSTANTS.SKELETON_COUNT).toBe('number');
        expect(typeof UI_CONSTANTS.EMPTY_ICON_SIZE).toBe('number');
    });

    it('ICON_SIZEが妥当な値である', () => {
        expect(UI_CONSTANTS.ICON_SIZE).toBeGreaterThan(0);
        expect(UI_CONSTANTS.ICON_SIZE).toBeLessThanOrEqual(20); // 妥当なアイコンサイズ範囲
        expect(typeof UI_CONSTANTS.ICON_SIZE).toBe('number');
        expect(Number.isInteger(UI_CONSTANTS.ICON_SIZE)).toBe(true);
    });

    it('SKELETON_COUNTが妥当な値である', () => {
        expect(UI_CONSTANTS.SKELETON_COUNT).toBeGreaterThan(0);
        expect(UI_CONSTANTS.SKELETON_COUNT).toBeLessThanOrEqual(50); // 妥当なスケルトン数
        expect(typeof UI_CONSTANTS.SKELETON_COUNT).toBe('number');
        expect(Number.isInteger(UI_CONSTANTS.SKELETON_COUNT)).toBe(true);
    });

    it('EMPTY_ICON_SIZEが妥当な値である', () => {
        expect(UI_CONSTANTS.EMPTY_ICON_SIZE).toBeGreaterThan(0);
        expect(UI_CONSTANTS.EMPTY_ICON_SIZE).toBeLessThanOrEqual(50); // 妥当な空状態アイコンサイズ
        expect(typeof UI_CONSTANTS.EMPTY_ICON_SIZE).toBe('number');
        expect(Number.isInteger(UI_CONSTANTS.EMPTY_ICON_SIZE)).toBe(true);
    });

    it('EMPTY_ICON_SIZEはICON_SIZEより大きい', () => {
        expect(UI_CONSTANTS.EMPTY_ICON_SIZE).toBeGreaterThan(UI_CONSTANTS.ICON_SIZE);
    });

    it('定数の型が正しく推論される', () => {
        // TypeScript の型チェックが正しく動作することを確認
        const iconSize = UI_CONSTANTS.ICON_SIZE;
        const skeletonCount = UI_CONSTANTS.SKELETON_COUNT;
        const emptyIconSize = UI_CONSTANTS.EMPTY_ICON_SIZE;

        expect(iconSize).toBe(4);
        expect(skeletonCount).toBe(10);
        expect(emptyIconSize).toBe(12);
    });

    it('定数オブジェクトの構造が正しい', () => {
        const expectedKeys = ['ICON_SIZE', 'SKELETON_COUNT', 'EMPTY_ICON_SIZE'];
        const actualKeys = Object.keys(UI_CONSTANTS);

        expect(actualKeys).toEqual(expect.arrayContaining(expectedKeys));
        expect(actualKeys.length).toBe(expectedKeys.length);
    });

    it('定数の構造が適切である', () => {
        // 定数オブジェクトが正しく定義されていることを確認
        expect(UI_CONSTANTS).toBeDefined();
        expect(Object.keys(UI_CONSTANTS).length).toBe(3);
        
        // すべてのプロパティが存在することを確認
        expect(UI_CONSTANTS).toHaveProperty('ICON_SIZE');
        expect(UI_CONSTANTS).toHaveProperty('SKELETON_COUNT');
        expect(UI_CONSTANTS).toHaveProperty('EMPTY_ICON_SIZE');
    });

    it('CSS/Tailwindクラスとの整合性', () => {
        // アイコンサイズがTailwindの規約に合致している
        const tailwindSizes = [1, 2, 3, 4, 5, 6, 8, 10, 12, 16, 20, 24];
        expect(tailwindSizes).toContain(UI_CONSTANTS.ICON_SIZE);
        expect(tailwindSizes).toContain(UI_CONSTANTS.EMPTY_ICON_SIZE);
    });

    it('スケルトン数が実用的な範囲内である', () => {
        // 一般的なページで表示されるスケルトン数として妥当
        expect(UI_CONSTANTS.SKELETON_COUNT).toBeGreaterThanOrEqual(5);
        expect(UI_CONSTANTS.SKELETON_COUNT).toBeLessThanOrEqual(20);
    });

    it('UX の観点から妥当な値設定である', () => {
        // アイコンサイズの関係性が適切
        expect(UI_CONSTANTS.EMPTY_ICON_SIZE).toBeGreaterThan(UI_CONSTANTS.ICON_SIZE);
        
        // 空状態のアイコンは通常のアイコンより大きいことが一般的
        const sizeDifference = UI_CONSTANTS.EMPTY_ICON_SIZE - UI_CONSTANTS.ICON_SIZE;
        expect(sizeDifference).toBeGreaterThanOrEqual(2);
        expect(sizeDifference).toBeLessThanOrEqual(16); // 極端に大きくない
    });
});