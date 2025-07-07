import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import RankingHistoryChart from '../RankingHistoryChart';
import { RankingHistoryData } from '../../types/index';

// Chart.jsのモック
vi.mock('react-chartjs-2', () => ({
    Line: ({ data, options, ...props }: any) => (
        <div data-testid="line-chart" {...props}>
            <div data-testid="chart-data">{JSON.stringify(data)}</div>
            <div data-testid="chart-options">{JSON.stringify(options)}</div>
        </div>
    ),
}));

// Chart.jsのモック
vi.mock('chart.js', () => ({
    Chart: {
        register: vi.fn(),
    },
    CategoryScale: {},
    LinearScale: {},
    PointElement: {},
    LineElement: {},
    Title: {},
    Tooltip: {},
    Legend: {},
}));

describe('RankingHistoryChart', () => {
    const mockData: RankingHistoryData[] = [
        {
            date: '2024-01-01',
            rank: 5,
            score: 85.5,
            companyName: 'テスト会社A',
        },
        {
            date: '2024-01-02',
            rank: 3,
            score: 92.1,
            companyName: 'テスト会社A',
        },
        {
            date: '2024-01-03',
            rank: 7,
            score: 78.9,
            companyName: 'テスト会社A',
        },
    ];

    it('データがある場合、チャートが表示される', () => {
        render(<RankingHistoryChart data={mockData} companyId={1} />);
        
        expect(screen.getByTestId('line-chart')).toBeInTheDocument();
    });

    it('データがない場合、メッセージが表示される', () => {
        render(<RankingHistoryChart data={[]} companyId={1} />);
        
        expect(screen.getByText('データがありません')).toBeInTheDocument();
        expect(screen.getByText('順位履歴データがありません')).toBeInTheDocument();
    });

    it('チャートデータが正しく構築される', () => {
        render(<RankingHistoryChart data={mockData} companyId={1} />);
        
        const chartDataElement = screen.getByTestId('chart-data');
        const chartData = JSON.parse(chartDataElement.textContent || '{}');
        
        expect(chartData.labels).toEqual(['2024-01-01', '2024-01-02', '2024-01-03']);
        expect(chartData.datasets).toHaveLength(2);
        expect(chartData.datasets[0].label).toBe('順位');
        expect(chartData.datasets[1].label).toBe('影響力スコア');
    });

    it('統計情報が正しく表示される', () => {
        render(<RankingHistoryChart data={mockData} companyId={1} />);
        
        // 現在の順位 (最後のデータ)
        expect(screen.getByText('現在の順位')).toBeInTheDocument();
        
        // 最高順位 (最小値)
        expect(screen.getByText('3位')).toBeInTheDocument();
        expect(screen.getByText('最高順位')).toBeInTheDocument();
        
        // 最低順位 (最大値)
        expect(screen.getByText('最低順位')).toBeInTheDocument();
        
        // 平均スコア
        const avgScore = ((85.5 + 92.1 + 78.9) / 3).toFixed(1);
        expect(screen.getByText(avgScore)).toBeInTheDocument();
        expect(screen.getByText('平均スコア')).toBeInTheDocument();
    });

    it('順位変動が正しく計算される', () => {
        render(<RankingHistoryChart data={mockData} companyId={1} />);
        
        // 前回3位 → 現在7位なので、4位下がった（↓4）
        expect(screen.getByText((content, element) => {
            return element?.textContent === '↓ 4' || content.includes('↓');
        })).toBeInTheDocument();
    });

    it('カスタムクラス名が適用される', () => {
        render(<RankingHistoryChart data={mockData} companyId={1} className="custom-class" />);
        
        const container = screen.getByTestId('line-chart').parentElement?.parentElement;
        expect(container).toHaveClass('custom-class');
    });

    it('カスタム設定が適用される', () => {
        const customConfig = {
            height: 300,
            showLegend: false,
        };
        
        render(<RankingHistoryChart data={mockData} companyId={1} config={customConfig} />);
        
        const chartOptionsElement = screen.getByTestId('chart-options');
        const chartOptions = JSON.parse(chartOptionsElement.textContent || '{}');
        
        expect(chartOptions.plugins.legend.display).toBe(false);
    });

    it('チャートタイトルに会社名が含まれる', () => {
        render(<RankingHistoryChart data={mockData} companyId={1} />);
        
        const chartOptionsElement = screen.getByTestId('chart-options');
        const chartOptions = JSON.parse(chartOptionsElement.textContent || '{}');
        
        expect(chartOptions.plugins.title.text).toBe('テスト会社A の順位推移');
    });

    it('Y軸が正しく設定される', () => {
        render(<RankingHistoryChart data={mockData} companyId={1} maxRank={50} />);
        
        const chartOptionsElement = screen.getByTestId('chart-options');
        const chartOptions = JSON.parse(chartOptionsElement.textContent || '{}');
        
        expect(chartOptions.scales.y.title.text).toBe('順位');
        expect(chartOptions.scales.y1.title.text).toBe('影響力スコア');
        expect(chartOptions.scales.y.reverse).toBe(true);
        expect(chartOptions.scales.y.max).toBe(50);
    });

    it('データが日付順にソートされる', () => {
        const unsortedData = [
            {
                date: '2024-01-03',
                rank: 7,
                score: 78.9,
                companyName: 'テスト会社A',
            },
            {
                date: '2024-01-01',
                rank: 5,
                score: 85.5,
                companyName: 'テスト会社A',
            },
            {
                date: '2024-01-02',
                rank: 3,
                score: 92.1,
                companyName: 'テスト会社A',
            },
        ];
        
        render(<RankingHistoryChart data={unsortedData} companyId={1} />);
        
        const chartDataElement = screen.getByTestId('chart-data');
        const chartData = JSON.parse(chartDataElement.textContent || '{}');
        
        // 日付順にソートされているか確認
        expect(chartData.labels).toEqual(['2024-01-01', '2024-01-02', '2024-01-03']);
    });

    it('順位変動がない場合、変動表示がされない', () => {
        const sameRankData = [
            {
                date: '2024-01-01',
                rank: 5,
                score: 85.5,
                companyName: 'テスト会社A',
            },
            {
                date: '2024-01-02',
                rank: 5,
                score: 87.1,
                companyName: 'テスト会社A',
            },
        ];
        
        render(<RankingHistoryChart data={sameRankData} companyId={1} />);
        
        // 変動の矢印が表示されないことを確認
        expect(screen.queryByText('↓')).not.toBeInTheDocument();
        expect(screen.queryByText('↑')).not.toBeInTheDocument();
    });

    it('単一データポイントでも正しく表示される', () => {
        const singleData = [mockData[0]];
        
        render(<RankingHistoryChart data={singleData} companyId={1} />);
        
        expect(screen.getByTestId('line-chart')).toBeInTheDocument();
        expect(screen.getByText('85.5')).toBeInTheDocument();
    });
});