import React from 'react';
import { usePage } from '@inertiajs/react';
import { LOGO_PATH } from '@/constants';

export const useBrand = () => {
    const { brand } = usePage().props;
    return { name: brand?.name || 'TenaFi', logo: brand?.logo || LOGO_PATH };
};

/** The product logo from Admin → Settings (App\Support\Brand). */
export default function BrandLogo({ className = '', ...props }) {
    const { name, logo } = useBrand();
    return <img src={logo} alt={name} className={className} {...props} />;
}
