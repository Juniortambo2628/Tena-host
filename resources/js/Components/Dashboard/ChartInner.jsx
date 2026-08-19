import React from 'react';
import {
    AreaChart, Area,
    BarChart, Bar,
    LineChart, Line,
    PieChart, Pie, Cell,
    XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';

const DEFAULT_COLORS = ['#10B981', '#FFD300', '#3B82F6', '#EF4444', '#8B5CF6', '#EC4899'];

function CustomTooltip({ active, payload, label, formatTooltip }) {
    if (!active || !payload?.length) return null;

    if (formatTooltip) {
        return formatTooltip({ active, payload, label });
    }

    return (
        <div className="chart-tooltip">
            <p className="chart-tooltip__label">{label}</p>
            {payload.map((entry, i) => (
                <div key={i} className="chart-tooltip__item">
                    <span className="chart-tooltip__dot" style={{ background: entry.color }} />
                    <span className="chart-tooltip__name">{entry.name}</span>
                    <span className="chart-tooltip__value">{typeof entry.value === 'number' ? entry.value.toLocaleString() : entry.value}</span>
                </div>
            ))}
        </div>
    );
}

export default function ChartInner({
    data,
    type,
    dataKeys,
    colors,
    gradientId,
    xAxisKey,
    stacked,
    showLegend,
    showGrid,
    showDots,
    formatTooltip,
}) {
    const colorPalette = colors.length > 0 ? colors : DEFAULT_COLORS;

    const commonProps = {
        data,
        margin: { top: 5, right: 10, left: -10, bottom: 0 },
    };

    const axisProps = {
        xAxis: <XAxis dataKey={xAxisKey} axisLine={false} tickLine={false} tick={{ fontSize: 10, fontWeight: 700, fill: '#999' }} />,
        yAxis: <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fontWeight: 600, fill: '#ccc' }} width={40} />,
        grid: showGrid ? <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.04)" vertical={false} /> : null,
        tooltip: <Tooltip content={<CustomTooltip formatTooltip={formatTooltip} />} />,
        legend: showLegend ? <Legend wrapperStyle={{ fontSize: 11, fontWeight: 600 }} /> : null,
    };

    if (type === 'bar') {
        return (
            <ResponsiveContainer width="100%" height="100%">
                <BarChart {...commonProps}>
                    {axisProps.grid}
                    {axisProps.xAxis}
                    {axisProps.yAxis}
                    {axisProps.tooltip}
                    {axisProps.legend}
                    {dataKeys.map((key, i) => (
                        <Bar
                            key={key}
                            dataKey={key}
                            fill={colorPalette[i % colorPalette.length]}
                            radius={[4, 4, 0, 0]}
                            stackId={stacked ? 'stack' : undefined}
                        />
                    ))}
                </BarChart>
            </ResponsiveContainer>
        );
    }

    if (type === 'line') {
        return (
            <ResponsiveContainer width="100%" height="100%">
                <LineChart {...commonProps}>
                    {axisProps.grid}
                    {axisProps.xAxis}
                    {axisProps.yAxis}
                    {axisProps.tooltip}
                    {axisProps.legend}
                    {dataKeys.map((key, i) => (
                        <Line
                            key={key}
                            type="monotone"
                            dataKey={key}
                            stroke={colorPalette[i % colorPalette.length]}
                            strokeWidth={2}
                            dot={showDots ? { r: 3, fill: colorPalette[i % colorPalette.length] } : false}
                            activeDot={showDots ? { r: 5 } : undefined}
                        />
                    ))}
                </LineChart>
            </ResponsiveContainer>
        );
    }

    if (type === 'pie') {
        return (
            <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                    <Pie
                        data={data}
                        cx="50%"
                        cy="50%"
                        innerRadius={50}
                        outerRadius={80}
                        paddingAngle={3}
                        dataKey={dataKeys[0]}
                        nameKey={xAxisKey}
                    >
                        {data.map((entry, i) => (
                            <Cell key={i} fill={colorPalette[i % colorPalette.length]} />
                        ))}
                    </Pie>
                    {axisProps.tooltip}
                    {axisProps.legend}
                </PieChart>
            </ResponsiveContainer>
        );
    }

    // Default: area chart
    return (
        <ResponsiveContainer width="100%" height="100%">
            <AreaChart {...commonProps}>
                <defs>
                    {dataKeys.map((key, i) => (
                        <linearGradient key={key} id={`${gradientId}-${key}`} x1="0" y1="0" x2="0" y2="1">
                            <stop offset="5%" stopColor={colorPalette[i % colorPalette.length]} stopOpacity={0.3} />
                            <stop offset="95%" stopColor={colorPalette[i % colorPalette.length]} stopOpacity={0} />
                        </linearGradient>
                    ))}
                </defs>
                {axisProps.grid}
                {axisProps.xAxis}
                {axisProps.yAxis}
                {axisProps.tooltip}
                {axisProps.legend}
                {dataKeys.map((key, i) => (
                    <Area
                        key={key}
                        type="monotone"
                        dataKey={key}
                        stroke={colorPalette[i % colorPalette.length]}
                        fill={`url(#${gradientId}-${key})`}
                        strokeWidth={2}
                        stackId={stacked ? 'stack' : undefined}
                    />
                ))}
            </AreaChart>
        </ResponsiveContainer>
    );
}
