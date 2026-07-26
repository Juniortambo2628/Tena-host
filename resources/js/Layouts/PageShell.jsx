import React from 'react';
import { Head } from '@inertiajs/react';
import DashboardLayout from './DashboardLayout';
import DashboardHero from '@/Components/Dashboard/DashboardHero';

const PageShell = React.memo(function PageShell({
    children,
    title,
    headTitle,
    breadcrumbs = [],
    actions = [],
    stats = [],
    rootRoute = 'host.dashboard',
    layoutTitle,
    hideHero = false,
}) {
    return (
        <DashboardLayout title={layoutTitle ?? title}>
            <Head title={headTitle ?? title} />
            {! hideHero && (
                <DashboardHero
                    title={title}
                    breadcrumbs={breadcrumbs}
                    actions={actions}
                    stats={stats}
                    rootRoute={rootRoute}
                />
            )}
            {children}
        </DashboardLayout>
    );
});

export default PageShell;
