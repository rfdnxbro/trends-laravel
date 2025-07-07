import React, { useMemo } from 'react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    ChartOptions,
} from 'chart.js';
import { Line } from 'react-chartjs-2';
import { InfluenceChartProps, ChartDataPoint } from '../types/index';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

const InfluenceChart: React.FC<InfluenceChartProps> = ({
    data,
    config = {},
    className = '',
    onDataPointClick
}) => {
    const defaultConfig = {
        responsive: true,
        maintainAspectRatio: false,
        showLegend: true,
        showTooltips: true,
        height: 400,
        ...config
    };

    const chartData = useMemo(() => {
        const colors = [
            'rgb(59, 130, 246)',   // blue-500
            'rgb(16, 185, 129)',   // emerald-500
            'rgb(245, 158, 11)',   // amber-500
            'rgb(239, 68, 68)',    // red-500
            'rgb(168, 85, 247)',   // purple-500
            'rgb(236, 72, 153)',   // pink-500
            'rgb(6, 182, 212)',    // cyan-500
            'rgb(34, 197, 94)',    // green-500
        ];

        const datasets = data.map((companyData, index) => ({
            label: companyData.companyName,
            data: companyData.data.map(point => ({
                x: point.date,
                y: point.value
            })),
            borderColor: colors[index % colors.length],
            backgroundColor: colors[index % colors.length] + '20',
            fill: false,
            tension: 0.1,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: colors[index % colors.length],
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
        }));

        // すべての日付を収集してユニークな日付のリストを作成
        const allDates = Array.from(new Set(
            data.flatMap(companyData => companyData.data.map(point => point.date))
        )).sort();

        return {
            labels: allDates,
            datasets
        };
    }, [data]);

    const options: ChartOptions<'line'> = {
        responsive: defaultConfig.responsive,
        maintainAspectRatio: defaultConfig.maintainAspectRatio,
        plugins: {
            title: {
                display: true,
                text: '企業影響力スコア推移',
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
                        return `${context.dataset.label}: ${context.parsed.y.toFixed(1)}`;
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
                display: true,
                title: {
                    display: true,
                    text: '影響力スコア'
                },
                grid: {
                    display: true,
                    color: 'rgba(156, 163, 175, 0.2)'
                },
                min: 0
            },
        },
        interaction: {
            mode: 'nearest' as const,
            axis: 'x' as const,
            intersect: false,
        },
        onClick: (event, elements) => {
            if (elements.length > 0 && onDataPointClick) {
                const element = elements[0];
                const datasetIndex = element.datasetIndex;
                const index = element.index;
                const dataset = chartData.datasets[datasetIndex];
                const dataPoint = dataset.data[index];
                const companyId = data[datasetIndex].companyId;
                
                onDataPointClick(
                    {
                        x: dataPoint.x,
                        y: dataPoint.y,
                        label: dataset.label
                    },
                    companyId
                );
            }
        }
    };

    if (!data || data.length === 0) {
        return (
            <div className={`flex items-center justify-center h-64 bg-gray-50 rounded-lg ${className}`}>
                <div className="text-center">
                    <p className="text-gray-500">データがありません</p>
                    <p className="text-sm text-gray-400 mt-1">企業データを選択してください</p>
                </div>
            </div>
        );
    }

    return (
        <div className={`bg-white rounded-lg shadow-md p-6 ${className}`}>
            <div style={{ height: defaultConfig.height }}>
                <Line data={chartData} options={options} />
            </div>
        </div>
    );
};

export default InfluenceChart;