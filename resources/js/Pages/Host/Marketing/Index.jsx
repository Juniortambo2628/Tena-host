import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import { useConfirm } from '@/hooks/useConfirm';
import {
    Mail,
    MessageSquare,
    Play,
    Plus,
    BarChart3,
    Clock,
    Edit2,
    Trash2,
    Pause,
    Zap,
    Megaphone,
} from 'lucide-react';
import './Index.css';

export default function MarketingIndex({ campaigns, stats, insights = [], triggers = [] }) {
    const { confirm, ConfirmDialogEl } = useConfirm();

    const handleDelete = async (campaign) => {
        const ok = await confirm({
            title: 'Delete Campaign',
            message: `Delete campaign "${campaign.name}"?`,
            confirmLabel: 'Delete',
            variant: 'danger',
        });
        if (!ok) return;
        router.delete(route('host.marketing.destroy', campaign.id), {
            preserveScroll: true,
            onSuccess: () => notify.success('Campaign deleted'),
            onError: () => notify.error('Failed to delete campaign'),
        });
    };

    const columns = [
        {
            header: 'Campaign',
            accessorKey: 'name',
            cell: ({ row: { original: campaign } }) => (
                <div className="host-marketing-index-campaign-info">
                    <span className="host-marketing-index-campaign-name">{campaign.name}</span>
                    <span className="host-marketing-index-campaign-date">Last sent {campaign.last_sent}</span>
                </div>
            ),
        },
        {
            header: 'Type',
            accessorKey: 'type',
            cell: ({ getValue }) => (
                <div className="host-marketing-index-campaign-type">
                    {getValue() === 'Email' ? <Mail size={14} className="text-[#FFD300]" /> : <MessageSquare size={14} className="text-green-500" />}
                    <span className="host-marketing-index-campaign-type-label">{getValue()}</span>
                </div>
            ),
        },
        {
            header: 'Status',
            accessorKey: 'status',
            cell: ({ getValue }) => (
                <div className="host-marketing-index-campaign-type">
                    <div className={`host-marketing-index-status-dot ${getValue() === 'Active' ? 'host-marketing-index-status-active' : 'host-marketing-index-status-inactive'}`}></div>
                    <span className="host-marketing-index-status-label">{getValue()}</span>
                </div>
            ),
        },
        {
            header: 'Performance',
            accessorKey: 'performance',
            cell: ({ getValue }) => <span className="host-marketing-index-performance">{getValue()}</span>,
        },
        {
            header: 'Actions',
            id: 'actions',
            enableSorting: false,
            cell: ({ row: { original: campaign } }) => (
                <div className="host-marketing-index-actions">
                    <Link
                        href={route('host.marketing.analytics', campaign.id)}
                        className="host-marketing-index-action-btn"
                        title="View Analytics"
                        aria-label="View analytics"
                    >
                        <BarChart3 size={14} className="host-marketing-index-action-icon" />
                    </Link>
                    <Link
                        href={route('host.marketing.edit', campaign.id)}
                        className="host-marketing-index-action-btn"
                        title="Edit Campaign"
                        aria-label="Edit campaign"
                    >
                        <Edit2 size={14} className="host-marketing-index-action-icon" />
                    </Link>
                    {campaign.status !== 'Active' && (
                        <button
                            onClick={() => router.post(route('host.marketing.activate', campaign.id), {}, {
                                preserveScroll: true,
                                onSuccess: () => notify.success('Campaign activated'),
                                onError: () => notify.error('Failed to activate campaign'),
                            })}
                            className="host-marketing-index-action-btn-green"
                            title="Activate & Send"
                            aria-label="Activate and send"
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
                            aria-label="Pause campaign"
                        >
                            <Pause size={14} className="host-marketing-index-action-icon-yellow" />
                        </button>
                    )}
                    <button
                        onClick={() => handleDelete(campaign)}
                        className="host-marketing-index-action-btn-red"
                        title="Delete"
                        aria-label="Delete campaign"
                    >
                        <Trash2 size={14} className="host-marketing-index-action-icon-red" />
                    </button>
                </div>
            ),
        },
    ];
    const breadcrumbs = [{ label: 'Marketing' }];

    const actions = [
        { label: 'Open Campaign Studio', variant: 'primary', icon: <Plus size={16} />, href: route('host.marketing.builder') },
    ];

    const heroStats = [
        { label: 'Total Messages', value: stats.totalSent.toLocaleString() },
        { label: 'Avg. Open Rate', value: stats.avgOpenRate },
        { label: 'Total Clicks', value: stats.clicks.toLocaleString() },
        { label: 'Reachable Guests', value: (stats.reachable ?? 0).toLocaleString() },
    ];

    return (
        <>
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
                        <DataTable
                            title="Campaigns"
                            data={campaigns}
                            columns={columns}
                            searchPlaceholder="Search campaigns..."
                            emptyIcon={Megaphone}
                            emptyMessage="No campaigns yet"
                            emptyAction={{ label: 'Open Campaign Studio', icon: <Plus size={14} />, href: route('host.marketing.builder') }}
                        />
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
            {ConfirmDialogEl}
        </>
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
