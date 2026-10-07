import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import BulkActions from '@/Components/Dashboard/BulkActions';
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
    Search
} from 'lucide-react';
import './Index.css';

export default function MarketingIndex({ campaigns, stats, insights = [], triggers = [] }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedIds, setSelectedIds] = useState([]);

    const filteredCampaigns = useMemo(() => {
        if (!searchQuery.trim()) return campaigns;
        const q = searchQuery.toLowerCase();
        return campaigns.filter(c =>
            c.name?.toLowerCase().includes(q) ||
            c.type?.toLowerCase().includes(q) ||
            c.status?.toLowerCase().includes(q)
        );
    }, [campaigns, searchQuery]);
    const breadcrumbs = [{ label: 'Marketing' }];

    const actions = [
        { label: 'New Campaign', variant: 'primary', icon: <Plus size={16} />, onClick: () => router.get(route('host.marketing.builder')) },
    ];

    const heroStats = [
        { label: 'Total Messages', value: stats.totalSent.toLocaleString() },
        { label: 'Avg. Open Rate', value: stats.avgOpenRate },
        { label: 'Total Clicks', value: stats.clicks.toLocaleString() },
        { label: 'Reachable Guests', value: (stats.reachable ?? 0).toLocaleString() },
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
                            <div className="flex items-center gap-3">
                                <div className="flex items-center gap-2 bg-black/5 px-3 py-2 rounded-xl">
                                    <Search size={14} className="text-black/30" />
                                    <input
                                        type="text"
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        placeholder="Search campaigns..."
                                        className="bg-transparent border-none p-0 text-xs font-bold placeholder:text-black/30 focus:ring-0 w-40"
                                    />
                                </div>
                                <button className="host-marketing-index-view-all">View All</button>
                            </div>
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
                                    {filteredCampaigns.map((campaign) => (
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
                                                            onClick={() => router.post(route('host.marketing.activate', campaign.id), {}, {
                                                                preserveScroll: true,
                                                                onSuccess: () => notify.success('Campaign activated'),
                                                                onError: () => notify.error('Failed to activate campaign'),
                                                            })}
                                                            className="host-marketing-index-action-btn-green"
                                                            title="Activate & Send"
                                                        >
                                                            <Play size={14} className="host-marketing-index-action-icon-green" />
                                                        </button>
                                                    )}
                                                    {campaign.status === 'Active' && (
                                                        <button
                                                            onClick={() => router.post(route('host.marketing.pause', campaign.id), {}, {
                                                                preserveScroll: true,
                                                                onSuccess: () => notify.success('Campaign paused'),
                                                                onError: () => notify.error('Failed to pause campaign'),
                                                            })}
                                                            className="host-marketing-index-action-btn"
                                                            title="Pause Campaign"
                                                        >
                                                            <Pause size={14} className="host-marketing-index-action-icon-yellow" />
                                                        </button>
                                                    )}
                                                    <button
                                                        onClick={() => {
                                                            if (confirm('Are you sure you want to delete ' + campaign.name + '?')) {
                                                                router.delete(route('host.marketing.destroy', campaign.id), {
                                                                    preserveScroll: true,
                                                                    onSuccess: () => notify.success('Campaign deleted'),
                                                                    onError: () => notify.error('Failed to delete campaign'),
                                                                });
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
                                <h3 className="host-marketing-index-ai-title">From your data</h3>
                            </div>
                            <h3 className="host-marketing-index-ai-heading">Suggestions</h3>

                            <div className="host-marketing-index-suggestions">
                                {insights.length === 0 ? (
                                    <p className="host-marketing-index-suggestion-text">Nothing to fix right now. Keep your campaigns running.</p>
                                ) : insights.map((tip) => (
                                    <SuggestionItem key={tip.text} {...tip} />
                                ))}
                            </div>
                        </div>
                    </GlassCard>

                    <GlassCard padding="host-marketing-index-automation-card">
                        <h4 className="host-marketing-index-automation-title">
                            <Clock size={12} />
                            Automation Triggers
                        </h4>
                        <div className="host-marketing-index-automation-list">
                            {triggers.map((trigger) => <TriggerItem key={trigger.title} {...trigger} />)}
                        </div>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}

// A trigger is on when at least one active campaign uses it.
function TriggerItem({ title, active }) {
    return (
        <Link href={route('host.marketing.builder')} className="host-marketing-index-trigger">
            <span className="host-marketing-index-trigger-label">{title}</span>
            <div className={`host-marketing-index-trigger-status ${active ? 'host-marketing-index-trigger-status-enabled' : 'host-marketing-index-trigger-status-disabled'}`}>
                {active ? `${active} active` : 'Set up'}
            </div>
        </Link>
    );
}

function SuggestionItem({ text, detail, href }) {
    const body = (
        <>
            <p className="host-marketing-index-suggestion-text">{text}</p>
            <p className="text-xs text-black/50 mt-1">{detail}</p>
        </>
    );

    return href
        ? <Link href={href} className="host-marketing-index-suggestion">{body}</Link>
        : <div className="host-marketing-index-suggestion">{body}</div>;
}
