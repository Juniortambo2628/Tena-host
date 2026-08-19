import React, { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import ChartCard from '@/Components/Dashboard/ChartCard';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { notify } from '@/Components/Toast';
import { Wifi, ShieldCheck, Plus, Activity, Users, DollarSign, BarChart3, TrendingUp } from 'lucide-react';
import { motion } from 'framer-motion';
import './Dashboard.css';

const VIEWS = [
    { id: 'overview', label: 'Overview', icon: <Activity size={14} /> },
    { id: 'guests', label: 'Guests', icon: <Users size={14} /> },
    { id: 'revenue', label: 'Revenue', icon: <DollarSign size={14} /> },
    { id: 'properties', label: 'Properties', icon: <BarChart3 size={14} /> },
];

export default function Dashboard({ properties, stats, analytics }) {
    const [activeView, setActiveView] = useState('overview');
    const guestTrend = stats.guestTrend || 0;

    const heroStats = [
        { label: 'Total Guests', value: stats.totalGuests || 0, trend: guestTrend !== 0 ? guestTrend : undefined },
        { label: 'Revenue', value: `KES ${(stats.totalRevenue || 0).toLocaleString()}` },
        { label: 'Avg Occupancy', value: stats.avgOccupancy || '0%' },
        { label: 'Properties', value: stats.totalProperties || 0 },
    ];

    const actions = [
        { label: 'Add Property', variant: 'primary', icon: <Plus size={16} />, onClick: () => router.get(route('host.properties.index')) },
    ];

    const breadcrumbs = [{ label: 'Overview' }];

    const container = {
        hidden: { opacity: 0 },
        show: { opacity: 1, transition: { staggerChildren: 0.08 } }
    };

    const item = {
        hidden: { opacity: 0, y: 16 },
        show: { opacity: 1, y: 0 }
    };

    const formatCurrency = (value) => `KES ${Number(value).toLocaleString()}`;

    return (
        <DashboardLayout title="Overview">
            <Head title="Host Dashboard" />

            <DashboardHero
                title={`Welcome back, ${stats.userName || 'Superhost'}`}
                breadcrumbs={breadcrumbs}
                actions={actions}
                stats={heroStats}
            />

            {/* View Switcher */}
            <div className="host-view-switcher">
                {VIEWS.map((view) => (
                    <button
                        key={view.id}
                        onClick={() => setActiveView(view.id)}
                        className={`host-view-btn ${activeView === view.id ? 'host-view-btn--active' : ''}`}
                    >
                        {view.icon}
                        {view.label}
                    </button>
                ))}
            </div>

            <motion.div
                variants={container}
                initial="hidden"
                animate="show"
                key={activeView}
                className="host-dashboard-grid"
            >
                {activeView === 'overview' && (
                    <>
                        <div className="host-dashboard-main">
                            <motion.div variants={item}>
                                <ChartCard
                                    data={analytics.guestGrowth}
                                    type="area"
                                    dataKeys={['guests']}
                                    colors={['#FFD300']}
                                    title="Guest Growth"
                                    subtitle={`Trend: ${guestTrend >= 0 ? '+' : ''}${guestTrend}% this week`}
                                    height={280}
                                />
                            </motion.div>

                            <motion.div variants={item}>
                                <GlassCard padding="p-0 overflow-hidden">
                                    <div className="host-dashboard-table-header">
                                        <h3 className="host-dashboard-table-title">Active Properties</h3>
                                        <PillButton variant="ghost" className="text-[10px] py-2 px-4" onClick={() => router.get(route('host.properties.index'))}>See all</PillButton>
                                    </div>
                                    <div className="host-dashboard-table-wrapper">
                                        <table className="host-dashboard-table">
                                            <thead>
                                                <tr className="host-dashboard-table-head-row">
                                                    <th className="host-dashboard-table-th">Property</th>
                                                    <th className="host-dashboard-table-th">Network</th>
                                                    <th className="host-dashboard-table-th">Occupancy</th>
                                                    <th className="host-dashboard-table-th">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody className="host-dashboard-table-body">
                                                {properties.map((property) => (
                                                    <tr key={property.id} className="host-dashboard-table-row">
                                                        <td className="host-dashboard-table-cell">
                                                            <div className="host-dashboard-property-info">
                                                                <span className="host-dashboard-property-name">{property.name}</span>
                                                                <span className="host-dashboard-property-address">{property.address || 'No address'}</span>
                                                            </div>
                                                        </td>
                                                        <td className="host-dashboard-table-cell">
                                                            <div className="host-dashboard-network-status">
                                                                <div className={`host-dashboard-network-dot ${property.network_status === 'Online' ? 'host-dashboard-network-dot--online' : 'host-dashboard-network-dot--offline'}`}></div>
                                                                <span className="host-dashboard-network-label">{property.network_status}</span>
                                                            </div>
                                                        </td>
                                                        <td className="host-dashboard-table-cell">
                                                            <div className="host-dashboard-occupancy">
                                                                <div className="host-dashboard-occupancy-bar-bg">
                                                                    <div
                                                                        className={`host-dashboard-occupancy-bar ${property.occupancy_rate > 90 ? 'bg-red-500' : 'bg-[#FFD300]'}`}
                                                                        style={{ width: `${property.occupancy_rate}%` }}
                                                                    />
                                                                </div>
                                                                <span className="host-dashboard-occupancy-label">{property.occupancy_rate}%</span>
                                                            </div>
                                                        </td>
                                                        <td className="host-dashboard-table-cell">
                                                            <Link href={route('host.properties.show', property.id)} className="host-dashboard-manage-link">Manage</Link>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </GlassCard>
                            </motion.div>
                        </div>

                        <div className="host-dashboard-sidebar">
                            <motion.div variants={item}>
                                <GlassCard padding="p-6">
                                    <div className="host-sidebar-stat-header">
                                        <Activity size={20} className="text-black/30" />
                                        <h4>Quick Stats</h4>
                                    </div>
                                    <div className="host-sidebar-stats">
                                        <div className="host-sidebar-stat">
                                            <span className="host-sidebar-stat-label">Orders</span>
                                            <span className="host-sidebar-stat-value">{stats.totalOrders || 0}</span>
                                        </div>
                                        <div className="host-sidebar-stat">
                                            <span className="host-sidebar-stat-label">Revenue</span>
                                            <span className="host-sidebar-stat-value">KES {(stats.totalRevenue || 0).toLocaleString()}</span>
                                        </div>
                                        <div className="host-sidebar-stat">
                                            <span className="host-sidebar-stat-label">Campaigns</span>
                                            <span className="host-sidebar-stat-value">{stats.activeCampaigns || 0}</span>
                                        </div>
                                        <div className="host-sidebar-stat">
                                            <span className="host-sidebar-stat-label">Online APs</span>
                                            <span className="host-dashboard-guard-device-status">{stats.onlineAPs || 0}</span>
                                        </div>
                                    </div>
                                </GlassCard>
                            </motion.div>

                            <motion.div variants={item}>
                                <GlassCard padding="p-0 overflow-hidden">
                                    <div className="host-dashboard-table-header">
                                        <h3 className="host-dashboard-table-title">Recent Guests</h3>
                                    </div>
                                    <div className="host-dashboard-guard-devices">
                                        {properties.slice(0, 3).map(p => (
                                            <div key={p.id} className="host-dashboard-guard-device">
                                                <p className="host-dashboard-guard-device-name">{p.name}</p>
                                                <p className="host-dashboard-guard-device-status">{p.guests_count} guests</p>
                                            </div>
                                        ))}
                                    </div>
                                </GlassCard>
                            </motion.div>
                        </div>
                    </>
                )}

                {activeView === 'guests' && (
                    <div className="host-full-width">
                        <motion.div variants={item} className="host-chart-row">
                            <ChartCard
                                data={analytics.guestMonthly}
                                type="bar"
                                dataKeys={['guests']}
                                colors={['#FFD300']}
                                title="Monthly Guests"
                                subtitle="Guest growth over 6 months"
                                height={320}
                            />
                            {analytics.guestSources.length > 0 && (
                                <ChartCard
                                    data={analytics.guestSources}
                                    type="pie"
                                    dataKeys={['value']}
                                    colors={['#FFD300', '#3B82F6', '#10B981', '#EF4444', '#8B5CF6']}
                                    title="Guest Sources"
                                    subtitle="Where guests come from"
                                    height={320}
                                    xAxisKey="name"
                                    showLegend
                                />
                            )}
                        </motion.div>
                        <motion.div variants={item}>
                            <ChartCard
                                data={analytics.guestGrowth}
                                type="area"
                                dataKeys={['guests']}
                                colors={['#FFD300']}
                                title="Daily Guest Connections"
                                subtitle="Last 30 days"
                                height={280}
                            />
                        </motion.div>
                    </div>
                )}

                {activeView === 'revenue' && (
                    <div className="host-full-width">
                        <motion.div variants={item}>
                            <ChartCard
                                data={analytics.orderRevenue}
                                type="area"
                                dataKeys={['revenue']}
                                colors={['#10B981']}
                                title="Order Revenue"
                                subtitle="Last 30 days"
                                height={320}
                                formatTooltip={({ payload, label }) => (
                                    <div className="chart-tooltip">
                                        <p className="chart-tooltip__label">{label}</p>
                                        <div className="chart-tooltip__item">
                                            <span className="chart-tooltip__dot" style={{ background: '#10B981' }} />
                                            <span className="chart-tooltip__value">{formatCurrency(payload[0]?.value)}</span>
                                        </div>
                                    </div>
                                )}
                            />
                        </motion.div>
                        {analytics.campaignStats.length > 0 && (
                            <motion.div variants={item}>
                                <GlassCard padding="p-6">
                                    <h3 className="font-semibold text-sm mb-4">Campaign Performance</h3>
                                    <div className="space-y-3">
                                        {analytics.campaignStats.map((c, i) => (
                                            <div key={i} className="flex items-center justify-between p-3 bg-black/[0.02] rounded-xl">
                                                <div>
                                                    <span className="text-sm font-bold">{c.type}</span>
                                                    <span className="text-xs text-black/40 ml-2">{c.count} campaigns</span>
                                                </div>
                                                <div className="text-right">
                                                    <span className="text-xs font-bold">{c.sent} sent</span>
                                                    <span className="text-xs text-black/40 ml-2">{c.opened} opened</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </GlassCard>
                            </motion.div>
                        )}
                    </div>
                )}

                {activeView === 'properties' && (
                    <div className="host-full-width">
                        <motion.div variants={item} className="host-chart-row">
                            <ChartCard
                                data={analytics.guestsPerProperty}
                                type="bar"
                                dataKeys={['guests']}
                                colors={['#FFD300', '#3B82F6', '#10B981', '#8B5CF6']}
                                title="Guests per Property"
                                subtitle="Distribution across properties"
                                height={320}
                                xAxisKey="name"
                            />
                            {analytics.occupancyData.length > 0 && (
                                <ChartCard
                                    data={analytics.occupancyData}
                                    type="bar"
                                    dataKeys={['occupancy']}
                                    colors={['#FFD300']}
                                    title="Occupancy Rates"
                                    subtitle="Current occupancy by property"
                                    height={320}
                                    xAxisKey="name"
                                />
                            )}
                        </motion.div>
                    </div>
                )}
            </motion.div>
        </DashboardLayout>
    );
}
