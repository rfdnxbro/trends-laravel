import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import TrendChart from '../TrendChart';
import { TrendChartData, MockChartProps } from '../../types/index';

// Chart.jsのモック
vi.mock('react-chartjs-2', () => ({
    Bar: ({ data, options, ...props }: MockChartProps) => (
        <div data-testid="bar-chart" {...props}>
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
    BarElement: {},
    Title: {},
    Tooltip: {},
    Legend: {},
}));

describe('TrendChart', () => {
    const mockData: TrendChartData = {
        articleCount: [
            { date: '2024-01-01', value: 15 },
            { date: '2024-01-02', value: 22 },
            { date: '2024-01-03', value: 18 },
        ],
        bookmarkCount: [
            { date: '2024-01-01', value: 150 },
            { date: '2024-01-02', value: 280 },
            { date: '2024-01-03', value: 195 },
        ],
        period: '7d',
    };

    it('データがある場合、チャートが表示される', () => {
        render(<TrendChart data={mockData} period="7d" />);
        
        expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
        expect(screen.getByText('トレンドチャート')).toBeInTheDocument();
    });

    it('データがない場合、メッセージが表示される', () => {
        const emptyData = {
            articleCount: [],
            bookmarkCount: [],
            period: '7d',
        };
        
        render(<TrendChart data={emptyData} period="7d" />);
        
        expect(screen.getByText('データがありません')).toBeInTheDocument();
        expect(screen.getByText('期間を変更して再度お試しください')).toBeInTheDocument();
    });

    it('チャートデータが正しく構築される', () => {
        render(<TrendChart data={mockData} period="7d" />);
        
        const chartDataElement = screen.getByTestId('chart-data');
        const chartData = JSON.parse(chartDataElement.textContent || '{}');
        
        expect(chartData.labels).toEqual(['2024-01-01', '2024-01-02', '2024-01-03']);
        expect(chartData.datasets).toHaveLength(2);
        expect(chartData.datasets[0].label).toBe('記事数');
        expect(chartData.datasets[1].label).toBe('ブックマーク数');
    });

    it('期間選択ボタンが表示される', () => {
        const mockOnPeriodChange = vi.fn();
        
        render(<TrendChart data={mockData} period="7d" onPeriodChange={mockOnPeriodChange} />);
        
        expect(screen.getByText('7日間')).toBeInTheDocument();
        expect(screen.getByText('30日間')).toBeInTheDocument();
        expect(screen.getByText('90日間')).toBeInTheDocument();
        expect(screen.getByText('1年間')).toBeInTheDocument();
    });

    it('期間選択ボタンのクリックでコールバックが呼ばれる', () => {
        const mockOnPeriodChange = vi.fn();
        
        render(<TrendChart data={mockData} period="7d" onPeriodChange={mockOnPeriodChange} />);
        
        const button30d = screen.getByText('30日間');
        fireEvent.click(button30d);
        
        expect(mockOnPeriodChange).toHaveBeenCalledWith('30d');
    });

    it('現在の期間が正しくハイライトされる', () => {
        const mockOnPeriodChange = vi.fn();
        render(<TrendChart data={mockData} period="7d" onPeriodChange={mockOnPeriodChange} />);
        
        const activeButton = screen.getByText('7日間');
        const inactiveButton = screen.getByText('30日間');
        
        expect(activeButton).toHaveClass('bg-blue-500', 'text-white');
        expect(inactiveButton).toHaveClass('bg-gray-100', 'text-gray-700');
    });

    it('統計情報が正しく計算される', () => {
        render(<TrendChart data={mockData} period="7d" />);
        
        // 総記事数: 15 + 22 + 18 = 55
        expect(screen.getByText('55')).toBeInTheDocument();
        expect(screen.getByText('総記事数')).toBeInTheDocument();
        
        // 総ブックマーク数: 150 + 280 + 195 = 625
        expect(screen.getByText('625')).toBeInTheDocument();
        expect(screen.getByText('総ブックマーク数')).toBeInTheDocument();
    });

    it('カスタムクラス名が適用される', () => {
        render(<TrendChart data={mockData} period="7d" className="custom-class" />);
        
        const container = screen.getByTestId('bar-chart').parentElement?.parentElement;
        expect(container).toHaveClass('custom-class');
    });

    it('カスタム設定が適用される', () => {
        const customConfig = {
            height: 300,
            showLegend: false,
        };
        
        render(<TrendChart data={mockData} period="7d" config={customConfig} />);
        
        const chartOptionsElement = screen.getByTestId('chart-options');
        const chartOptions = JSON.parse(chartOptionsElement.textContent || '{}');
        
        expect(chartOptions.plugins.legend.display).toBe(false);
    });

    it('チャートタイトルに期間が含まれる', () => {
        render(<TrendChart data={mockData} period="30d" />);
        
        const chartOptionsElement = screen.getByTestId('chart-options');
        const chartOptions = JSON.parse(chartOptionsElement.textContent || '{}');
        
        expect(chartOptions.plugins.title.text).toBe('記事数・ブックマーク数推移 (30d)');
    });

    it('Y軸が正しく設定される', () => {
        render(<TrendChart data={mockData} period="7d" />);
        
        const chartOptionsElement = screen.getByTestId('chart-options');
        const chartOptions = JSON.parse(chartOptionsElement.textContent || '{}');
        
        expect(chartOptions.scales.y.title.text).toBe('記事数');
        expect(chartOptions.scales.y1.title.text).toBe('ブックマーク数');
        expect(chartOptions.scales.y.position).toBe('left');
        expect(chartOptions.scales.y1.position).toBe('right');
    });

    it('期間が選択されていない場合もチャートが表示される', () => {
        const mockOnPeriodChange = vi.fn();
        
        render(<TrendChart data={mockData} period="7d" />);
        
        expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
    });
});