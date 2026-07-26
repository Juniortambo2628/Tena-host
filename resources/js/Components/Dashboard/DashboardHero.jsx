import React from 'react';
import { Link } from '@inertiajs/react';
import PillButton from './PillButton';
import './DashboardHero.css';

export default function DashboardHero({
    title,
    breadcrumbs = [],
    actions = [],
    stats = [],
    rootRoute = 'host.dashboard',
}) {
    return (
        <div className="dashboard-hero">
            {/* Top Row: Breadcrumbs & Title */}
            <div className="dashboard-hero__header">
                <div className="dashboard-hero__title-block">
                    <nav className="dashboard-hero__breadcrumbs">
                        <Link href={route(rootRoute)} className="dashboard-hero__breadcrumb-link">Dashboard</Link>
                        {breadcrumbs.map((crumb, idx) => (
                            <React.Fragment key={idx}>
                                <span className="dashboard-hero__breadcrumb-separator">/</span>
                                {crumb.href ? (
                                    <Link href={crumb.href} className="dashboard-hero__breadcrumb-link">{crumb.label}</Link>
                                ) : (
                                    <span className="dashboard-hero__breadcrumb-current">{crumb.label}</span>
                                )}
                            </React.Fragment>
                        ))}
                    </nav>
                    <h1 className="dashboard-hero__title">{title}</h1>
                </div>

                {/* Page Actions */}
                <div className="dashboard-hero__actions">
                    {actions.map((action, idx) => (
                        <PillButton
                            key={idx}
                            variant={action.variant || 'white'}
                            onClick={action.onClick}
                            icon={action.icon}
                            className={action.className}
                        >
                            {action.label}
                        </PillButton>
                    ))}
                </div>
            </div>

            {/* Optional Stats Row */}
            {stats.length > 0 && (
                <div className="dashboard-hero__stats">
                    {stats.map((stat, idx) => (
                        <div key={idx} className="dashboard-hero__stat">
                            <span className="dashboard-hero__stat-label">{stat.label}</span>
                            <div className="dashboard-hero__stat-row">
                                <span className="dashboard-hero__stat-value">{stat.value}</span>
                                {stat.trend && (
                                    <span className={`dashboard-hero__stat-trend ${stat.trend > 0 ? 'dashboard-hero__stat-trend--positive' : 'dashboard-hero__stat-trend--negative'}`}>
                                        {stat.trend > 0 ? '+' : ''}{stat.trend}%
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <div className="dashboard-hero__divider" />
        </div>
    );
}
