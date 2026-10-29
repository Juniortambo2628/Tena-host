import { useState, useEffect, lazy, Suspense } from 'react';
import { BRAND_COLORS } from '@/constants';

const Chart = lazy(() => import('./AreaChartInner'));

export default function AreaChartCard({ data, dataKey = 'guests', color = BRAND_COLORS.primary, title, subtitle, gradientId = 'colorValue', className = '' }) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => { setMounted(true); }, []);

    if (!mounted) {
        return <div className={`h-[240px] w-full ${className}`} />;
    }

    return (
        <div className={`h-[240px] w-full ${className}`}>
            <Suspense fallback={<div className="h-full w-full animate-pulse bg-black/5 rounded-2xl" />}>
                <Chart data={data} dataKey={dataKey} color={color} gradientId={gradientId} />
            </Suspense>
        </div>
    );
}
