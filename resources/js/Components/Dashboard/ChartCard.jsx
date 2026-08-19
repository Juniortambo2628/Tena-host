import React, { Suspense, lazy } from 'react';
import './ChartCard.css';

const LazyChart = lazy(() => import('./ChartInner'));

function ChartFallback() {
    return (
        <div className="chart-card__skeleton">
            <div className="chart-card__skeleton-bar" />
            <div className="chart-card__skeleton-area" />
        </div>
    );
}

export default function ChartCard({
    data = [],
    type = 'area',
    dataKeys = ['value'],
    colors = ['#10B981'],
    title,
    subtitle,
    gradientId,
    className = '',
    height = 280,
    xAxisKey = 'name',
    stacked = false,
    showLegend = false,
    showGrid = true,
    showDots = false,
    formatTooltip,
}) {
    return (
        <div className={`chart-card ${className}`}>
            {(title || subtitle) && (
                <div className="chart-card__header">
                    {title && <h3 className="chart-card__title">{title}</h3>}
                    {subtitle && <p className="chart-card__subtitle">{subtitle}</p>}
                </div>
            )}
            <div className="chart-card__body" style={{ height }}>
                <Suspense fallback={<ChartFallback />}>
                    <LazyChart
                        data={data}
                        type={type}
                        dataKeys={dataKeys}
                        colors={colors}
                        gradientId={gradientId || `grad-${title?.replace(/\s/g, '-') || 'default'}`}
                        xAxisKey={xAxisKey}
                        stacked={stacked}
                        showLegend={showLegend}
                        showGrid={showGrid}
                        showDots={showDots}
                        formatTooltip={formatTooltip}
                    />
                </Suspense>
            </div>
        </div>
    );
}
