import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer
} from 'recharts';
import {
    Mail,
    MessageSquare,
    BarChart3,
    Clock,
    TrendingUp,
    Users,
    MousePointer2,
    Eye,
    ChevronLeft
} from 'lucide-react';

export default function MarketingAnalytics({ campaign, performance, summary }) {
    const breadcrumbs = [
        { label: 'Marketing', href: route('host.marketing.index') },
        { label: 'Analytics' }
    ];

    const actions = [
        { label: 'Edit Campaign', variant: 'white', icon: <BarChart3 size={16} />, onClick: () => router.get(route('host.marketing.edit', campaign.id)) },
    ];

    return (
        <DashboardLayout title="Campaign Analytics">
            <Head title={`Analytics: ${campaign.name}`} />

            <div className="mb-6">
                <Link
                    href={route('host.marketing.index')}
                    className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-black/30 hover:text-black transition-colors"
                >
                    <ChevronLeft size={14} />
                    Back to Marketing
                </Link>
            </div>

            <DashboardHero
                title={campaign.name}
                breadcrumbs={breadcrumbs}
                actions={actions}
            />

            {/* Quick Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <StatCard
                    icon={<Eye size={20} className="text-[#FFD300]" />}
                    label="Total Delivered"
                    value={summary.totalDelivered}
                    sub="100% Delivery rate"
                />
                <StatCard
                    icon={<Users size={20} className="text-[#FFD300]" />}
                    label="Open Rate"
                    value={summary.openRate}
                    sub="+12% vs last week"
                />
                <StatCard
                    icon={<MousePointer2 size={20} className="text-green-500" />}
                    label="Click Rate"
                    value={summary.clickRate}
                    sub="18% Click-to-open"
                />
                <StatCard
                    icon={<TrendingUp size={20} className="text-purple-500" />}
                    label="Unsubscribes"
                    value={summary.unsubscribes}
                    sub="Below industry avg"
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Main Engagement Chart */}
                <div className="lg:col-span-2">
                    <GlassCard padding="p-8">
                        <div className="flex justify-between items-center mb-8">
                            <div>
                                <h3 className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-1">Engagement Trends</h3>
                                <p className="text-2xl font-black">Performance Over Time</p>
                            </div>
                            <div className="flex items-center gap-4">
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 rounded-full bg-[#FFD300]"></div>
                                    <span className="text-[10px] font-black uppercase tracking-widest text-black/40">Opened</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 rounded-full bg-black"></div>
                                    <span className="text-[10px] font-black uppercase tracking-widest text-black/40">Delivered</span>
                                </div>
                            </div>
                        </div>

                        <div className="h-[400px] w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={performance}>
                                    <defs>
                                        <linearGradient id="colorDelivered" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#000" stopOpacity={0.05} />
                                            <stop offset="95%" stopColor="#000" stopOpacity={0} />
                                        </linearGradient>
                                        <linearGradient id="colorOpened" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#FFD300" stopOpacity={0.1} />
                                            <stop offset="95%" stopColor="#FFD300" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="rgba(0,0,0,0.05)" />
                                    <XAxis
                                        dataKey="name"
                                        axisLine={false}
                                        tickLine={false}
                                        tick={{ fontSize: 10, fontWeight: 900, fill: 'rgba(0,0,0,0.3)' }}
                                        dy={10}
                                    />
                                    <YAxis hide />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'rgba(255,255,255,0.9)',
                                            borderRadius: '12px',
                                            border: 'none',
                                            boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1)',
                                            fontWeight: 900,
                                            fontSize: '10px'
                                        }}
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey="delivered"
                                        stroke="#000"
                                        strokeWidth={2}
                                        fillOpacity={1}
                                        fill="url(#colorDelivered)"
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey="opened"
                                        stroke="#FFD300"
                                        strokeWidth={4}
                                        fillOpacity={1}
                                        fill="url(#colorOpened)"
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </GlassCard>
                </div>

                {/* Secondary: Device/Channel Breakdown */}
                <div className="space-y-6">
                    <GlassCard padding="p-8">
                        <h3 className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-6">Audience Interaction</h3>
                        <div className="space-y-6">
                            <DeviceStat label="Mobile" value="84%" trend={3} />
                            <DeviceStat label="Desktop" value="12%" trend={-2} />
                            <DeviceStat label="Tablet" value="4%" trend={0} />
                        </div>

                        <div className="h-px bg-black/5 w-full my-8" />

                        <h4 className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-4">Top Domains</h4>
                        <div className="space-y-3">
                            <DomainStat name="gmail.com" percent="62%" />
                            <DomainStat name="icloud.com" percent="18%" />
                            <DomainStat name="outlook.com" percent="15%" />
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-8" className="bg-black text-white">
                        <TrendingUp size={24} className="text-[#FFD300] mb-6" />
                        <h3 className="text-xl font-black mb-2 tracking-tight">AI Insights</h3>
                        <p className="text-sm text-white/50 font-medium leading-relaxed">
                            Your midweek open rates are <span className="text-[#FFD300]">15% higher</span> when sent at 10:00 AM. We recommend scheduling future midweek emails for this time.
                        </p>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}

function StatCard({ icon, label, value, sub }) {
    return (
        <GlassCard padding="p-6" className="group">
            <div className="flex items-center gap-3 mb-4">
                <div className="w-10 h-10 rounded-xl bg-black/5 flex items-center justify-center transition-colors group-hover:bg-black group-hover:text-white">
                    {icon}
                </div>
                <span className="text-[10px] font-black uppercase tracking-widest text-black/40">{label}</span>
            </div>
            <p className="text-3xl font-black tracking-tight mb-1">{value}</p>
            <p className="text-[10px] font-bold text-black/30 group-hover:text-black/60 transition-colors uppercase tracking-widest">{sub}</p>
        </GlassCard>
    );
}

function DeviceStat({ label, value, trend }) {
    return (
        <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-black/60 uppercase tracking-widest">{label}</span>
            <div className="flex items-center gap-3">
                <span className="text-sm font-black text-black">{value}</span>
                {trend !== 0 && (
                    <span className={`text-[10px] font-black ${trend > 0 ? 'text-green-500' : 'text-red-500'}`}>
                        {trend > 0 ? '↑' : '↓'}{Math.abs(trend)}%
                    </span>
                )}
            </div>
        </div>
    );
}

function DomainStat({ name, percent }) {
    return (
        <div className="flex items-center justify-between">
            <span className="text-xs font-black text-black/40">{name}</span>
            <div className="flex items-center gap-4 flex-1 ml-6">
                <div className="h-1 bg-black/5 flex-1 rounded-full overflow-hidden">
                    <div className="h-full bg-black rounded-full" style={{ width: percent }} />
                </div>
                <span className="text-[10px] font-black text-black w-8">{percent}</span>
            </div>
        </div>
    );
}
