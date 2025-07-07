import React, { useMemo } from 'react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ChartOptions,
} from 'chart.js';
import { Bar } from 'react-chartjs-2';
import { TrendChartProps } from '../types/index';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend
);

const TrendChart: React.FC<TrendChartProps> = ({
    data,
    config = {},
    className = '',
    period,
    onPeriodChange
}) => {
    const defaultConfig = {
        responsive: true,
        maintainAspectRatio: false,
        showLegend: true,
        showTooltips: true,
        height: 400,
        ...config
    };

    const periodOptions = [
        { value: '7d', label: '7日間' },
        { value: '30d', label: '30日間' },
        { value: '90d', label: '90日間' },
        { value: '1y', label: '1年間' }
    ];

    const chartData = useMemo(() => {
        // 記事数とブックマーク数のデータから日付を抽出
        const allDates = Array.from(new Set([
            ...data.articleCount.map(item => item.date),
            ...data.bookmarkCount.map(item => item.date)
        ])).sort();

        return {
            labels: allDates,
            datasets: [
                {
                    label: '記事数',
                    data: data.articleCount.map(item => item.value),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    yAxisID: 'y',
                },
                {
                    label: 'ブックマーク数',
                    data: data.bookmarkCount.map(item => item.value),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1,
                    yAxisID: 'y1',
                }
            ]
        };
    }, [data]);

    const options: ChartOptions<'bar'> = {
        responsive: defaultConfig.responsive,
        maintainAspectRatio: defaultConfig.maintainAspectRatio,
        plugins: {
            title: {
                display: true,
                text: `記事数・ブックマーク数推移 (${period})`,
                font: {
                    size: 16,
                    weight: 'bold'
                }
            },
            legend: {
                display: defaultConfig.showLegend,
                position: 'top' as const,
            },
            tooltip: {
                enabled: defaultConfig.showTooltips,
                mode: 'index' as const,
                intersect: false,
                callbacks: {
                    title: (context) => {
                        return `日付: ${context[0].label}`;
                    },
                    label: (context) => {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        return `${label}: ${value.toLocaleString()}`;
                    }
                }
            },
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: '日付'
                },
                grid: {
                    display: true,
                    color: 'rgba(156, 163, 175, 0.2)'
                }
            },
            y: {
                type: 'linear' as const,
                display: true,
                position: 'left' as const,
                title: {
                    display: true,
                    text: '記事数'
                },
                grid: {
                    display: true,
                    color: 'rgba(156, 163, 175, 0.2)'
                },
                min: 0
            },
            y1: {
                type: 'linear' as const,
                display: true,
                position: 'right' as const,
                title: {
                    display: true,
                    text: 'ブックマーク数'
                },
                grid: {
                    drawOnChartArea: false,
                },
                min: 0
            },
        },
        interaction: {
            mode: 'index' as const,
            intersect: false,
        },
    };

    if (!data || (!data.articleCount.length && !data.bookmarkCount.length)) {
        return (
            <div className={`flex items-center justify-center h-64 bg-gray-50 rounded-lg ${className}`}>
                <div className="text-center">
                    <p className="text-gray-500">データがありません</p>
                    <p className="text-sm text-gray-400 mt-1">期間を変更して再度お試しください</p>
                </div>
            </div>
        );
    }

    return (
        <div className={`bg-white rounded-lg shadow-md p-6 ${className}`}>
            {/* 期間選択 */}
            <div className="mb-4 flex justify-between items-center">
                <h3 className="text-lg font-semibold text-gray-900">
                    トレンドチャート
                </h3>
                {onPeriodChange && (
                    <div className="flex space-x-2">
                        {periodOptions.map(option => (
                            <button
                                key={option.value}
                                onClick={() => onPeriodChange(option.value)}
                                className={`px-3 py-1 rounded-md text-sm font-medium transition-colors ${
                                    period === option.value
                                        ? 'bg-blue-500 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                )}
            </div>

            <div style={{ height: defaultConfig.height }}>
                <Bar data={chartData} options={options} />
            </div>

            {/* 統計情報 */}
            <div className="mt-4 grid grid-cols-2 gap-4">
                <div className="text-center p-3 bg-blue-50 rounded-lg">
                    <div className="text-2xl font-bold text-blue-600">
                        {data.articleCount.reduce((sum, item) => sum + item.value, 0).toLocaleString()}
                    </div>
                    <div className="text-sm text-gray-600">総記事数</div>
                </div>
                <div className="text-center p-3 bg-green-50 rounded-lg">
                    <div className="text-2xl font-bold text-green-600">
                        {data.bookmarkCount.reduce((sum, item) => sum + item.value, 0).toLocaleString()}
                    </div>
                    <div className="text-sm text-gray-600">総ブックマーク数</div>
                </div>
            </div>
        </div>
    );
};

export default TrendChart;