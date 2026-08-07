@props([
    'type' => 'line', // 'line', 'bar', 'doughnut'
    'labels' => [],
    'datasets' => [],
    'height' => 300,
    'options' => null,
])

<div
    wire:ignore
    x-data="{
        chart: null,
        renderChart() {
            const ChartObj = window.Chart || (typeof Chart !== 'undefined' ? Chart : null);
            if (!ChartObj) {
                setTimeout(() => this.renderChart(), 100);
                return;
            }
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            if (this.chart) {
                try {
                    this.chart.stop();
                    this.chart.destroy();
                } catch (e) {}
                this.chart = null;
            }

            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 150 },
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

            const customOptions = @js($options ?? []);
            const mergedOptions = Object.assign({}, defaultOptions, customOptions);

            try {
                this.chart = new ChartObj(ctx, {
                    type: '{{ $type }}',
                    data: {
                        labels: @js($labels),
                        datasets: @js($datasets)
                    },
                    options: mergedOptions
                });
            } catch (err) {
                console.warn('Chart render deferred:', err);
            }
        }
    }"
    x-init="
        $nextTick(() => renderChart());
    "
    {{ $attributes->merge(['class' => 'relative w-full']) }}
    style="height: {{ $height }}px;"
>
    <canvas x-ref="canvas"></canvas>
</div>
