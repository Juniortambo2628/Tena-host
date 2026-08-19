import React, { useState } from 'react';
import ChartCard from '@/Components/Dashboard/ChartCard';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { Users, Building2, TrendingUp, Activity, Eye, DollarSign, UserPlus, BarChart3, PieChart as PieIcon, LineChart } from 'lucide-react';
import { motion } from 'framer-motion';
import './Dashboard.css';

const VIEWS = [
    { id: 'overview', label: 'Overview', icon: <Activity size={14} /> },
    { id: 'revenue', label: 'Revenue', icon: <DollarSign size={14} /> },
    { id: 'growth', label: 'Growth', icon: <TrendingUp size={14} /> },
    { id: 'registrations', label: 'Signups', icon: <UserPlus size={14} /> },
];

export default function AdminDashboard({ stats, hosts, recentRegistrations, analytics }) {
    const [activeView, setActiveView] = useState('overview');

    const heroStats = [
        { label: 'Active Hosts', value: stats.totalHosts, trend: stats.newHostsThisMonth },
        { label: 'Properties', value: stats.totalProperties, trend: stats.newPropertiesThisMonth },
        { label: 'Total Guests', value: stats.totalGuests },
        { label: 'Revenue', value: `KES ${(stats.totalRevenue || 0).toLocaleString()}` },
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
        <DashboardLayout title="Admin Overview">
            <Head title="Admin Dashboard" />

            <DashboardHero
                title="Platform Overview"
                breadcrumbs={breadcrumbs}
                stats={heroStats}
                rootRoute="admin.dashboard"
            />

            {/* View Switcher */}
            <div className="admin-view-switcher">
                {VIEWS.map((view) => (
                    <button
                        key={view.id}
                        onClick={() => setActiveView(view.id)}
                        className={`admin-view-btn ${activeView === view.id ? 'admin-view-btn--active' : ''}`}
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
                className="admin-dashboard-grid"
            >
                {activeView === 'overview' && (
                    <>
                        <div className="admin-main-content">
                            <motion.div variants={item}>
                                <ChartCard
                                    data={analytics.revenue}
                                    type="area"
                                    dataKeys={['revenue']}
                                    colors={['#10B981']}
                                    title="Revenue Trend"
                                    subtitle="Monthly revenue (M-Pesa)"
                                    height={280}
                                    formatTooltip={({ payload, label }) => (
                                        <div className="chart-tooltip">
                                            <p className="chart-tooltip__label">{label}</p>
                                            <div className="chart-tooltip__item">
                                                <span className="chart-tooltip__dot" style={{ background: '#10B981' }} />
                                                <span className="chart-tooltip__name">Revenue</span>
                                                <span className="chart-tooltip__value">{formatCurrency(payload[0]?.value)}</span>
                                            </div>
                                        </div>
                                    )}
                                />
                            </motion.div>

                            <motion.div variants={item}>
                                <GlassCard padding="p-0 overflow-hidden">
                                    <div className="admin-table-header">
                                        <h3>Recent Hosts</h3>
                                        <Link href={route('admin.hosts.index')} className="admin-view-all-link">View All</Link>
                                    </div>
                                    <div className="overflow-x-auto">
                                        <table className="admin-table">
                                            <thead>
                                                <tr>
                                                    <th>Host Name</th>
                                                    <th>Properties</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {hosts.map((host) => (
                                                    <tr key={host.id}>
                                                        <td>
                                                            <div className="flex flex-col">
                                                                <span className="admin-host-name">{host.name}</span>
                                                                <span className="admin-host-email">{host.email}</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div className="admin-host-properties">
                                                                <Building2 size={16} className="text-black/20" />
                                                                <span>{host.properties_count}</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span className={`admin-status-badge ${host.status === 'active' ? 'admin-status-active' : 'admin-status-pending'}`}>
                                                                {host.status}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button onClick={() => router.get(route('admin.hosts.show', host.id))} className="admin-action-btn">
                                                                <Eye size={14} />
                                                            </button>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </GlassCard>
                            </motion.div>
                        </div>

                        <div className="admin-sidebar">
                            <motion.div variants={item}>
                                <GlassCard padding="p-6">
                                    <div className="admin-sidebar-card-header">
                                        <div className="admin-sidebar-icon"><Activity size={24} className="text-black" /></div>
                                        <div>
                                            <h3 className="admin-sidebar-title">Platform Stats</h3>
                                            <p className="admin-sidebar-subtitle">Key metrics</p>
                                        </div>
                                    </div>
                                    <div className="space-y-4">
                                        <div className="admin-stat-item">
                                            <span className="admin-stat-label">Total Hosts</span>
                                            <span className="admin-stat-value">{stats.totalHosts}</span>
                                        </div>
                                        <div className="admin-stat-item">
                                            <span className="admin-stat-label">Properties</span>
                                            <span className="admin-stat-value">{stats.totalProperties}</span>
                                        </div>
                                        <div className="admin-stat-item">
                                            <span className="admin-stat-label">Guests</span>
                                            <span className="admin-stat-value">{stats.totalGuests}</span>
                                        </div>
                                        <div className="admin-stat-item">
                                            <span className="admin-stat-label">Signups</span>
                                            <span className="admin-stat-value">{stats.totalSignups}</span>
                                        </div>
                                    </div>
                                </GlassCard>
                            </motion.div>

                            <motion.div variants={item}>
                                <GlassCard padding="p-0 overflow-hidden">
                                    <div className="admin-waitlist-header"><h3>Waitlist</h3></div>
                                    <div className="admin-waitlist-items">
                                        {recentRegistrations.map((reg) => (
                                            <div key={reg.id} className="admin-waitlist-item">
                                                <div className="admin-waitlist-item-header">
                                                    <h4 className="admin-waitlist-name">{reg.first_name} {reg.last_name}</h4>
                                                    <span className="admin-waitlist-date">{new Date(reg.created_at).toLocaleDateString()}</span>
                                                </div>
                                                <p className="admin-waitlist-details">{reg.property_count} properties • {reg.property_type}</p>
                                            </div>
                                        ))}
                                    </div>
                                </GlassCard>
                            </motion.div>
                        </div>
                    </>
                )}

                {activeView === 'revenue' && (
                    <div className="admin-full-width">
                        <motion.div variants={item} className="admin-chart-row">
                            <ChartCard
                                data={analytics.revenue}
                                type="area"
                                dataKeys={['revenue']}
                                colors={['#10B981']}
                                title="Revenue Trend"
                                subtitle="Monthly M-Pesa revenue"
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
                            <ChartCard
                                data={analytics.transactionStatus}
                                type="pie"
                                dataKeys={['value']}
                                colors={['#10B981', '#F59E0B', '#EF4444']}
                                title="Transaction Status"
                                subtitle="M-Pesa breakdown"
                                height={320}
                                xAxisKey="name"
                                showLegend
                            />
                        </motion.div>
                    </div>
                )}

                {activeView === 'growth' && (
                    <div className="admin-full-width">
                        <motion.div variants={item} className="admin-chart-row">
                            <ChartCard
                                data={analytics.guests}
                                type="bar"
                                dataKeys={['guests']}
                                colors={['#FFD300']}
                                title="Guest Growth"
                                subtitle="Monthly new guests"
                                height={300}
                            />
                            <ChartCard
                                data={analytics.properties}
                                type="bar"
                                dataKeys={['properties']}
                                colors={['#3B82F6']}
                                title="Property Growth"
                                subtitle="Monthly new properties"
                                height={300}
                            />
                        </motion.div>
                        <motion.div variants={item}>
                            <ChartCard
                                data={analytics.dailyGuests}
                                type="area"
                                dataKeys={['guests']}
                                colors={['#8B5CF6']}
                                title="Daily Guest Connections"
                                subtitle="Last 14 days"
                                height={280}
                            />
                        </motion.div>
                    </div>
                )}

                {activeView === 'registrations' && (
                    <div className="admin-full-width">
                        <motion.div variants={item} className="admin-chart-row">
                            <ChartCard
                                data={analytics.signups}
                                type="bar"
                                dataKeys={['signups']}
                                colors={['#EC4899']}
                                title="Waitlist Signups"
                                subtitle="Monthly registrations"
                                height={300}
                            />
                            <ChartCard
                                data={analytics.referralSources}
                                type="pie"
                                dataKeys={['value']}
                                colors={['#FFD300', '#3B82F6', '#10B981', '#EF4444', '#8B5CF6', '#EC4899']}
                                title="Referral Sources"
                                subtitle="Where signups come from"
                                height={300}
                                xAxisKey="name"
                                showLegend
                            />
                        </motion.div>
                        <motion.div variants={item}>
                            <ChartCard
                                data={analytics.propertyTypes}
                                type="pie"
                                dataKeys={['value']}
                                colors={['#10B981', '#3B82F6', '#F59E0B', '#EF4444']}
                                title="Property Types"
                                subtitle="Registration breakdown"
                                height={280}
                                xAxisKey="name"
                                showLegend
                            />
                        </motion.div>
                    </div>
                )}
            </motion.div>
        </DashboardLayout>
    );
}
