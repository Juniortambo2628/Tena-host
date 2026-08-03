import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import {
    Mail,
    MessageSquare,
    Play,
    Plus,
    BarChart3,
    Clock,
    TrendingUp,
    CheckCircle2,
    AlertCircle,
    Edit2,
    Trash2,
    Pause,
    Zap,
    ArrowUpRight
} from 'lucide-react';
import './Index.css';

export default function MarketingIndex({ campaigns, stats }) {
    const breadcrumbs = [{ label: 'Marketing' }];

    const actions = [
        { label: 'New Campaign', variant: 'primary', icon: <Plus size={16} />, onClick: () => router.get(route('host.marketing.builder')) },
    ];

    const heroStats = [
        { label: 'Total Messages', value: stats.totalSent.toLocaleString() },
        { label: 'Avg. Open Rate', value: stats.avgOpenRate, trend: 12 },
        { label: 'Total Clicks', value: stats.clicks.toLocaleString() },
        { label: 'ROI Generated', value: stats.revenue, trend: 8.5 },
    ];

    return (
        <DashboardLayout title="Marketing">
            <Head title="Marketing Builder" />

            <DashboardHero
                title="Marketing Builder"
                breadcrumbs={breadcrumbs}
                actions={actions}
                stats={heroStats}
            />

            <div className="host-marketing-index-grid">
                {/* Campaigns List */}
                <div className="host-marketing-index-main">
                    <GlassCard padding="p-0 overflow-hidden">
                        <div className="host-marketing-index-table-header">
                            <h3 className="host-marketing-index-table-title">Active Campaigns</h3>
                            <button className="host-marketing-index-view-all">View All</button>
                        </div>

                        <div className="host-marketing-index-table-wrapper">
                            <table className="host-marketing-index-table">
                                <thead>
                                    <tr className="host-marketing-index-table-head-row">
                                        <th className="host-marketing-index-table-th">Campaign</th>
                                        <th className="host-marketing-index-table-th">Type</th>
                                        <th className="host-marketing-index-table-th">Status</th>
                                        <th className="host-marketing-index-table-th">Performance</th>
                                        <th className="host-marketing-index-table-th">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="host-marketing-index-table-body">
                                    {campaigns.map((campaign) => (
                                        <tr key={campaign.id} className="host-marketing-index-table-row">
                                            <td className="host-marketing-index-table-cell">
                                                <div className="host-marketing-index-campaign-info">
                                                    <span className="host-marketing-index-campaign-name">{campaign.name}</span>
                                                    <span className="host-marketing-index-campaign-date">Last sent {campaign.last_sent}</span>
                                                </div>
                                            </td>
                                            <td className="host-marketing-index-table-cell">
                                                <div className="host-marketing-index-campaign-type">
                                                    {campaign.type === 'Email' ? <Mail size={14} className="text-[#FFD300]" /> : <MessageSquare size={14} className="text-green-500" />}
                                                    <span className="host-marketing-index-campaign-type-label">{campaign.type}</span>
                                                </div>
                                            </td>
                                            <td className="host-marketing-index-table-cell">
                                                <div className="host-marketing-index-campaign-type">
                                                    <div className={`host-marketing-index-status-dot ${campaign.status === 'Active' ? 'host-marketing-index-status-active' : 'host-marketing-index-status-inactive'}`}></div>
                                                    <span className="host-marketing-index-status-label">{campaign.status}</span>
                                                </div>
                                            </td>
                                            <td className="host-marketing-index-performance">
                                                {campaign.performance}
                                            </td>
                                            <td className="host-marketing-index-table-cell">
                                                <div className="host-marketing-index-actions">
                                                    <button
                                                        onClick={() => router.get(route('host.marketing.analytics', campaign.id))}
                                                        className="host-marketing-index-action-btn"
                                                        title="View Analytics"
                                                    >
                                                        <BarChart3 size={14} className="host-marketing-index-action-icon" />
                                                    </button>
                                                    <button
                                                        onClick={() => router.get(route('host.marketing.edit', campaign.id))}
                                                        className="host-marketing-index-action-btn"
                                                        title="Edit Campaign"
                                                    >
                                                        <Edit2 size={14} className="host-marketing-index-action-icon" />
                                                    </button>
                                                    {campaign.status !== 'Active' && (
                                                        <button
                                                            onClick={() => router.post(route('host.marketing.activate', campaign.id), {}, { preserveScroll: true })}
                                                            className="host-marketing-index-action-btn-green"
                                                            title="Activate & Send"
                                                        >
                                                            <Play size={14} className="host-marketing-index-action-icon-green" />
                                                        </button>
                                                    )}
                                                    {campaign.status === 'Active' && (
                                                        <button
                                                            onClick={() => router.post(route('host.marketing.pause', campaign.id), {}, { preserveScroll: true })}
                                                            className="host-marketing-index-action-btn"
                                                            title="Pause Campaign"
                                                        >
                                                            <Pause size={14} className="host-marketing-index-action-icon-yellow" />
                                                        </button>
                                                    )}
                                                    <button
                                                        onClick={() => {
                                                            if (confirm('Are you sure you want to delete ' + campaign.name + '?')) {
                                                                router.delete(route('host.marketing.destroy', campaign.id), { preserveScroll: true });
                                                            }
                                                        }}
                                                        className="host-marketing-index-action-btn-red"
                                                        title="Delete"
                                                    >
                                                        <Trash2 size={14} className="host-marketing-index-action-icon-red" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </GlassCard>
                </div>

                {/* Right Column: Tips & Automation */}
                <div className="host-marketing-index-sidebar">
                    <GlassCard padding="p-8" className="host-marketing-index-ai-card">
                        <div className="host-marketing-index-ai-bg">
                            <Zap size={120} className="text-black" />
                        </div>
                        <div className="host-marketing-index-ai-content">
                            <div className="host-marketing-index-ai-header">
                                <Zap size={18} className="text-[#FFD300]" />
                                <h3 className="host-marketing-index-ai-title">AI Optimizer</h3>
                            </div>
                            <h3 className="host-marketing-index-ai-heading">Growth Suggestions</h3>

                            <div className="host-marketing-index-suggestions">
                                <SuggestionItem
                                    text='Switch "Review Request" to SMS'
                                    lift="+24%"
                                />
                                <SuggestionItem
                                    text="Adjust welcome email to 10 AM"
                                    lift="+12%"
                                />
                                <SuggestionItem
                                    text="Target 'Business' segment for midweek"
                                    lift="+18%"
                                />
                            </div>

                            <PillButton
                                variant="primary"
                                className="w-full py-4 text-xs"
                                onClick={() => notify.info('Applying all campaign optimizations...')}
                            >
                                <div className="flex items-center justify-center gap-2">
                                    Apply All Optimizations
                                    <ArrowUpRight size={14} />
                                </div>
                            </PillButton>
                        </div>
                    </GlassCard>

                    <GlassCard padding="host-marketing-index-automation-card">
                        <h4 className="host-marketing-index-automation-title">
                            <Clock size={12} />
                            Automation Triggers
                        </h4>
                        <div className="host-marketing-index-automation-list">
                            <TriggerItem title="Guest Connects to WiFi" status="Enabled" />
                            <TriggerItem title="1 Hour Before Checkout" status="Enabled" />
                            <TriggerItem title="24 Hours Post Stay" status="Disabled" />
                        </div>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}

function TriggerItem({ title, status }) {
    return (
        <div className="host-marketing-index-trigger">
            <span className="host-marketing-index-trigger-label">{title}</span>
            <div className="host-marketing-index-trigger-toggle-wrap">
                <div className={`host-marketing-index-trigger-status ${status === 'Enabled' ? 'host-marketing-index-trigger-status-enabled' : 'host-marketing-index-trigger-status-disabled'}`}>{status}</div>
                <button className={`host-marketing-index-trigger-toggle ${status === 'Enabled' ? 'host-marketing-index-trigger-toggle-on' : 'host-marketing-index-trigger-toggle-off'}`}>
                    <div className={`host-marketing-index-trigger-knob ${status === 'Enabled' ? 'host-marketing-index-trigger-knob-on' : 'host-marketing-index-trigger-knob-off'}`} />
                </button>
            </div>
        </div>
    );
}

function SuggestionItem({ text, lift }) {
    return (
        <div className="host-marketing-index-suggestion">
            <p className="host-marketing-index-suggestion-text">{text}</p>
            <span className="host-marketing-index-suggestion-lift">{lift}</span>
        </div>
    );
}
