import React from 'react';
import { usePage } from '@inertiajs/react';
import { BRAND_DEFAULTS } from '@/constants';

/** Logos from Admin → Settings → Branding (App\Support\Brand). */
export const useBrand = () => {
    const { brand = {} } = usePage().props;
    return { ...BRAND_DEFAULTS, ...Object.fromEntries(Object.entries(brand).filter(([, v]) => v)) };
};

/**
 * variant: header (light backgrounds, the default), footer (dark
 * backgrounds), logo (the official yellow logo) or favicon (square icon).
 */
export default function BrandLogo({ variant = 'header', className = '', ...props }) {
    const brand = useBrand();
    return <img src={brand[variant]} alt={brand.name} className={className} {...props} />;
}
