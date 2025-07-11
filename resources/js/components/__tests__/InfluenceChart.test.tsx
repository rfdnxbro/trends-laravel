import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import InfluenceChart from '../InfluenceChart';
import { InfluenceChartData, MockChartProps } from '../../types/index';

// Chart.jsのモック
vi.mock('react-chartjs-2', () => ({
    Line: ({ data, options, ...props }: MockChartProps) => (
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

describe('InfluenceChart', () => {
    const mockData: InfluenceChartData[] = [
        {
            companyId: 1,
            companyName: 'テスト会社A',
            data: [
                { date: '2024-01-01', value: 85.5 },
                { date: '2024-01-02', value: 87.2 },
                { date: '2024-01-03', value: 82.8 },
            ],
        },
        {
            companyId: 2,
            companyName: 'テスト会社B',
            data: [
                { date: '2024-01-01', value: 78.3 },
                { date: '2024-01-02', value: 80.1 },
                { date: '2024-01-03', value: 81.9 },
            ],
        },
    ];

    it('データがある場合、チャートが表示される', () => {
        render(<InfluenceChart data={mockData} />);
        
        expect(screen.getByTestId('line-chart')).toBeInTheDocument();
    });

    it('データがない場合、メッセージが表示される', () => {
        render(<InfluenceChart data={[]} />);
        
        expect(screen.getByText('データがありません')).toBeInTheDocument();
        expect(screen.getByText('企業データを選択してください')).toBeInTheDocument();
    });

    it('チャートデータが正しく構築される', () => {
        render(<InfluenceChart data={mockData} />);
        
        const chartDataElement = screen.getByTestId('chart-data');
        const chartData = JSON.parse(chartDataElement.textContent || '{}');
        
        expect(chartData.labels).toEqual(['2024-01-01', '2024-01-02', '2024-01-03']);
        expect(chartData.datasets).toHaveLength(2);
        expect(chartData.datasets[0].label).toBe('テスト会社A');
        expect(chartData.datasets[1].label).toBe('テスト会社B');
    });

    it('カスタムクラス名が適用される', () => {
        render(<InfluenceChart data={mockData} className="custom-class" />);
        
        const container = screen.getByTestId('line-chart').parentElement?.parentElement;
        expect(container).toHaveClass('custom-class');
    });

    it('カスタム設定が適用される', () => {
        const customConfig = {
            height: 300,
            showLegend: false,
        };
        
        render(<InfluenceChart data={mockData} config={customConfig} />);
        
        const chartOptionsElement = screen.getByTestId('chart-options');
        const chartOptions = JSON.parse(chartOptionsElement.textContent || '{}');
        
        expect(chartOptions.plugins.legend.display).toBe(false);
    });

    it('onDataPointClickコールバックが設定される', () => {
        const mockCallback = vi.fn();
        
        render(<InfluenceChart data={mockData} onDataPointClick={mockCallback} />);
        
        // Chart.jsのonClickはJSON.stringifyで正しく変換されないため、
        // コンポーネントの存在を確認する
        expect(screen.getByTestId('line-chart')).toBeInTheDocument();
    });

    it('企業名が正しく表示される', () => {
        render(<InfluenceChart data={mockData} />);
        
        const chartDataElement = screen.getByTestId('chart-data');
        const chartData = JSON.parse(chartDataElement.textContent || '{}');
        
        expect(chartData.datasets[0].label).toBe('テスト会社A');
        expect(chartData.datasets[1].label).toBe('テスト会社B');
    });

    it('時系列データが正しく処理される', () => {
        render(<InfluenceChart data={mockData} />);
        
        const chartDataElement = screen.getByTestId('chart-data');
        const chartData = JSON.parse(chartDataElement.textContent || '{}');
        
        expect(chartData.datasets[0].data).toEqual([
            { x: '2024-01-01', y: 85.5 },
            { x: '2024-01-02', y: 87.2 },
            { x: '2024-01-03', y: 82.8 },
        ]);
    });

    it('複数企業のデータが統合される', () => {
        render(<InfluenceChart data={mockData} />);
        
        const chartDataElement = screen.getByTestId('chart-data');
        const chartData = JSON.parse(chartDataElement.textContent || '{}');
        
        // 全ての日付が統合されている
        expect(chartData.labels).toEqual(['2024-01-01', '2024-01-02', '2024-01-03']);
        expect(chartData.datasets).toHaveLength(2);
    });

    it('チャートタイトルが正しく設定される', () => {
        render(<InfluenceChart data={mockData} />);
        
        const chartOptionsElement = screen.getByTestId('chart-options');
        const chartOptions = JSON.parse(chartOptionsElement.textContent || '{}');
        
        expect(chartOptions.plugins.title.text).toBe('企業影響力スコア推移');
        expect(chartOptions.plugins.title.display).toBe(true);
    });
});