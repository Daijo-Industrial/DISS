@props([
    'type' => 'line', // 'line', 'bar', 'doughnut'
    'labels' => [],
    'datasets' => [],
    'height' => 300,
    'options' => null,
])

@php
    $chartId = 'chart-' . Str::random(8);
@endphp

<div
    x-data="{
        chart: null,
        initChart() {
            if (!window.Chart) return;
            if (this.chart) {
                this.chart.destroy();
            }
            const ctx = this.$refs.canvas.getContext('2d');
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: {{ $type === 'doughnut' ? 'true' : 'false' }},
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 16,
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: '700' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                        padding: 12,
                        cornerRadius: 12,
                        displayColors: true
                    }
                },
                scales: {{ $type === 'doughnut' ? '{}' : '{
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: "Plus Jakarta Sans", size: 11 } }
                    },
                    y: {
                        grid: { color: "rgba(226, 232, 240, 0.6)" },
                        ticks: { font: { family: "Plus Jakarta Sans", size: 11 } },
                        beginAtZero: true,
                        max: 100
                    }
                }' }}
            };

            const customOptions = @json($options ?? []);
            const mergedOptions = Object.assign({}, defaultOptions, customOptions);

            this.chart = new window.Chart(ctx, {
                type: '{{ $type }}',
                data: {
                    labels: @json($labels),
                    datasets: @json($datasets)
                },
                options: mergedOptions
            });
        },
        updateChart(newLabels, newDatasets) {
            if (!this.chart) return;
            this.chart.data.labels = newLabels;
            this.chart.data.datasets = newDatasets;
            this.chart.update('active');
        }
    }"
    x-init="$nextTick(() => initChart())"
    x-effect="if (chart) updateChart(@json($labels), @json($datasets))"
    {{ $attributes->merge(['class' => 'relative w-full']) }}
    style="height: {{ $height }}px;"
>
    <canvas x-ref="canvas"></canvas>
</div>
