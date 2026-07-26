import React, { useEffect, useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Users, Building2, TrendingUp, Activity, Eye } from 'lucide-react';
import { motion } from 'framer-motion';
import './Dashboard.css';

export default function AdminDashboard({ stats, hosts, revenueChartData, recentRegistrations }) {
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    const heroStats = [
        { label: 'Active Hosts', value: stats.totalHosts, trend: stats.newHostsThisMonth },
        { label: 'Properties', value: stats.totalProperties, trend: stats.newPropertiesThisMonth },
        { label: 'Total Guests', value: stats.totalGuests },
        { label: 'Pending', value: stats.pendingApprovals },
    ];

    const breadcrumbs = [{ label: 'Overview' }];

    const container = {
        hidden: { opacity: 0 },
        show: { opacity: 1, transition: { staggerChildren: 0.1 } }
    };

    const item = {
        hidden: { opacity: 0, y: 20 },
        show: { opacity: 1, y: 0 }
    };

    return (
        <DashboardLayout title="Admin Overview">
            <Head title="Admin Dashboard" />

            <DashboardHero
                title="Platform Overview"
                breadcrumbs={breadcrumbs}
                stats={heroStats}
                rootRoute="admin.dashboard"
            />

            <motion.div
                variants={container}
                initial="hidden"
                animate="show"
                className="admin-dashboard-grid"
            >
                <div className="admin-main-content">

                    <motion.div variants={item}>
                        <GlassCard padding="p-8">
                            <div className="admin-chart-header">
                                <div>
                                    <h3>Financials</h3>
                                    <p>Monthly Revenue</p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="admin-chart-icon">
                                        <TrendingUp size={16} />
                                    </div>
                                </div>
                            </div>

                            <div className="admin-chart-container">
                                {mounted && (
                                    <ResponsiveContainer width="100%" height="100%" minWidth={300} minHeight={200}>
                                        <AreaChart data={revenueChartData}>
                                            <defs>
                                                <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="5%" stopColor="#10B981" stopOpacity={0.1} />
                                                    <stop offset="95%" stopColor="#10B981" stopOpacity={0} />
                                                </linearGradient>
                                            </defs>
                                            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="rgba(0,0,0,0.05)" />
                                            <XAxis
                                                dataKey="month"
                                                axisLine={false}
                                                tickLine={false}
                                                tick={{ fontSize: 10, fontWeight: 900, fill: 'rgba(0,0,0,0.3)', textAnchor: 'middle' }}
                                                dy={10}
                                            />
                                            <YAxis hide domain={['dataMin - 1000', 'dataMax + 1000']} />
                                            <Tooltip
                                                contentStyle={{
                                                    backgroundColor: 'rgba(255,255,255,0.9)',
                                                    borderRadius: '12px',
                                                    border: 'none',
                                                    boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1)',
                                                    fontWeight: 900,
                                                    fontSize: '10px',
                                                    textTransform: 'uppercase',
                                                    letterSpacing: '0.05em'
                                                }}
                                                formatter={(value) => [`$${value}`, 'Revenue']}
                                            />
                                            <Area
                                                type="monotone"
                                                dataKey="revenue"
                                                stroke="#10B981"
                                                strokeWidth={4}
                                                fillOpacity={1}
                                                fill="url(#colorRevenue)"
                                            />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                )}
                            </div>
                        </GlassCard>
                    </motion.div>

                    <motion.div variants={item}>
                        <GlassCard padding="p-0 overflow-hidden">
                            <div className="admin-table-header">
                                <h3>Recent Hosts</h3>
                                <Link
                                    href={route('admin.hosts.index')}
                                    className="admin-view-all-link"
                                >
                                    View All
                                </Link>
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
                                                    <button
                                                        onClick={() => router.get(route('admin.hosts.show', host.id))}
                                                        className="admin-action-btn"
                                                    >
                                                        <Eye size={14} />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                        {hosts.length === 0 && (
                                            <tr>
                                                <td colSpan="4" className="admin-table-empty">
                                                    No hosts found
                                                </td>
                                            </tr>
                                        )}
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
                                <div className="admin-sidebar-icon">
                                    <Activity size={24} className="text-black" />
                                </div>
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
                                    <span className="admin-stat-label">New This Month</span>
                                    <span className="admin-stat-value">{stats.newHostsThisMonth} hosts</span>
                                </div>
                            </div>
                        </GlassCard>
                    </motion.div>

                    <motion.div variants={item}>
                        <GlassCard padding="p-0 overflow-hidden">
                            <div className="admin-waitlist-header">
                                <h3>Waitlist</h3>
                            </div>
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
                                {recentRegistrations.length === 0 && (
                                    <div className="admin-waitlist-empty">
                                        No pending requests
                                    </div>
                                )}
                            </div>
                        </GlassCard>
                    </motion.div>
                </div>
            </motion.div>
        </DashboardLayout>
    );
}
