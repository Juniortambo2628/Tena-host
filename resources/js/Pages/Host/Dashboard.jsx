import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Wifi, ShieldCheck } from 'lucide-react';
import './Dashboard.css';

export default function Dashboard({ properties, stats, guestChartData, notifications }) {
    // Use dynamic chart data from backend, or fallback to empty array
    const chartData = guestChartData || [];

    const heroStats = [
        { label: 'Total Guests', value: stats.totalGuests || '0' },
        { label: 'Online APs', value: stats.onlineAPs || '0' },
        { label: 'Avg Occupancy', value: stats.avgOccupancy || '0%' },
        { label: 'Properties', value: stats.totalProperties || '0' },
    ];

    const actions = [
        { label: 'Add Property', variant: 'primary', icon: <i className="fas fa-plus" />, onClick: () => router.get(route('host.properties.index')) },
    ];

    const breadcrumbs = [{ label: 'Overview' }];

    return (
        <DashboardLayout title="Overview">
            <Head title="Host Dashboard" />

            <DashboardHero
                title={`Welcome back, ${stats.userName || 'Superhost'}`}
                breadcrumbs={breadcrumbs}
                actions={actions}
                stats={heroStats}
            />

            <div className="host-dashboard-grid">
                {/* Main Stats/Table Area */}
                <div className="host-dashboard-main">
                    {/* Guest Growth Chart */}
                    <GlassCard padding="p-8">
                        <div className="host-dashboard-chart-header">
                            <div>
                                <h3 className="host-dashboard-chart-label">Analytics</h3>
                                <p className="host-dashboard-chart-title">Guest Growth</p>
                            </div>
                            <div className="host-dashboard-chart-legend">
                                <div className="host-dashboard-chart-dot-outer">
                                    <div className="host-dashboard-chart-dot-inner"></div>
                                </div>
                                <span className="host-dashboard-chart-trend">+32% this week</span>
                            </div>
                        </div>

                        <div className="host-dashboard-chart-container">
                            <ResponsiveContainer width="100%" height="100%" minWidth={100} minHeight={100}>
                                <AreaChart data={chartData}>
                                    <defs>
                                        <linearGradient id="colorGuests" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#FFD300" stopOpacity={0.1} />
                                            <stop offset="95%" stopColor="#FFD300" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="rgba(0,0,0,0.05)" />
                                    <XAxis
                                        dataKey="name"
                                        axisLine={false}
                                        tickLine={false}
                                        tick={{ fontSize: 10, fontWeight: 900, fill: 'rgba(0,0,0,0.3)', textAnchor: 'middle' }}
                                        dy={10}
                                    />
                                    <YAxis hide domain={['dataMin - 2', 'dataMax + 2']} />
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
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey="guests"
                                        stroke="#FFD300"
                                        strokeWidth={4}
                                        fillOpacity={1}
                                        fill="url(#colorGuests)"
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </GlassCard>

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
                                        <th className="host-dashboard-table-th">Uptime</th>
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
                                                    <div className="host-dashboard-network-dot"></div>
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
                                                    <span className="host-dashboard-occupancy-label">{property.occupancy_rate}% Load</span>
                                                </div>
                                            </td>
                                            <td className="host-dashboard-table-cell">
                                                <span className="host-dashboard-uptime">{property.uptime}</span>
                                            </td>
                                            <td className="host-dashboard-table-cell">
                                                <Link
                                                    href={route('host.properties.index')}
                                                    className="host-dashboard-manage-link"
                                                >
                                                    Manage
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                    {properties.length === 0 && (
                                        <tr>
                                            <td colSpan="4" className="host-dashboard-empty">
                                                No properties found
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </GlassCard>
                </div>

                {/* Sidebar Cards Area */}
                <div className="host-dashboard-sidebar">
                    <GlassCard
                        bgImage="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80"
                        className="group"
                    >
                        <i className="host-dashboard-marketing-icon"></i>
                        <h3 className="host-dashboard-marketing-title">Marketing Builder</h3>
                        <p className="host-dashboard-marketing-desc">
                            Create automated email & SMS campaigns for your guests to drive direct bookings.
                        </p>
                        <PillButton variant="secondary" className="w-full" onClick={() => router.get(route('host.marketing.index'))}>Open Builder</PillButton>
                    </GlassCard>

                    <GlassCard padding="p-8" className="host-dashboard-guard-card">
                        <div className="host-dashboard-guard-bg">
                            <Wifi size={80} className="rotate-12" />
                        </div>
                        <div className="host-dashboard-guard-content">
                            <div className="host-dashboard-guard-icon">
                                <ShieldCheck size={20} className="text-black" />
                            </div>
                            <h3 className="host-dashboard-guard-title">Network Guard</h3>
                                <p className="host-dashboard-guard-desc">
                                    Real-time monitoring active across {properties.length} properties.
                                </p>
                                <div className="host-dashboard-guard-devices">
                                    {properties.slice(0, 3).map(p => (
                                        <div key={p.id} className="host-dashboard-guard-device">
                                            <p className="host-dashboard-guard-device-name">{p.name.split(' ')[0]}</p>
                                            <p className="host-dashboard-guard-device-status">{p.network_status}</p>
                                        </div>
                                    ))}
                            </div>
                            <PillButton variant="white" className="w-full py-3">Security Audit</PillButton>
                        </div>
                    </GlassCard>

                    <GlassCard padding="host-dashboard-support-card">
                        <div className="host-dashboard-support-header">
                            <h4 className="host-dashboard-support-title">Quick Support</h4>
                            <div className="host-dashboard-support-dot"></div>
                        </div>
                        <p className="host-dashboard-support-desc">Need help with your configuration? Our team is online.</p>
                        <PillButton variant="white" className="w-full py-2.5" onClick={() => alert('Support Chat is currently in development. Please contact support@tena.io for immediate assistance.')}>Chat with us</PillButton>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
