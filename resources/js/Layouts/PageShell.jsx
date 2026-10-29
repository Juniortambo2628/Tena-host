import React from 'react';
import { Head } from '@inertiajs/react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';

// The page heading for dashboard pages. DashboardLayout itself is the persistent
// layout set in app.jsx, so it is not rendered here.
const PageShell = React.memo(function PageShell({
    children,
    title,
    headTitle,
    breadcrumbs = [],
    actions = [],
    stats = [],
    rootRoute = 'host.dashboard',
    hideHero = false,
}) {
    return (
        <>
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
        </>
    );
});

export default PageShell;
