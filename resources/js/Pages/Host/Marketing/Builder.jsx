import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import {
    Mail,
    MessageSquare,
    Zap,
    Users,
    Clock,
    Palette,
    Send,
    Type,
    Plus,
    Loader2,
    ArrowLeft
} from 'lucide-react';
import './Builder.css';

export default function MarketingBuilder({ campaign, properties }) {
    const isEditing = !!campaign;

    const { data, setData, post, put, processing } = useForm({
        name: campaign?.name || '',
        type: campaign?.type || 'email',
        subject: campaign?.subject || '',
        content: campaign?.content || '',
        trigger_event: campaign?.trigger_event || 'Guest Connects to WiFi',
        trigger_delay: campaign?.trigger_delay || 'Instant',
        target_audience: campaign?.target_audience || 'all_guests',
        property_id: campaign?.property_id || null,
        audience_property_id: campaign?.audience_property_id || null,
        audience_from: campaign?.audience_from || '',
        audience_to: campaign?.audience_to || '',
        scheduled_at: campaign?.scheduled_at || '',
        status: campaign?.status || 'draft',
    });

    const [activeTab, setActiveTab] = useState('design');

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEditing) {
            put(route('host.marketing.update', campaign.id), { preserveScroll: true });
        } else {
            post(route('host.marketing.store'));
        }
    };

    const handleSaveDraft = (e) => {
        e.preventDefault();
        setData('status', 'draft');
        handleSubmit(e);
    };

    const handleLaunch = (e) => {
        e.preventDefault();
        setData('status', 'active');
        handleSubmit(e);
    };

    const breadcrumbs = [
        { label: 'Marketing', href: route('host.marketing.index') },
        { label: isEditing ? 'Edit Campaign' : 'New Campaign' }
    ];

    const actions = [
        { label: 'Back to Campaigns', icon: <ArrowLeft size={16} />, onClick: () => router.get(route('host.marketing.index')) },
    ];

    return (
        <DashboardLayout title={isEditing ? "Edit Campaign" : "Campaign Builder"}>
            <Head title={isEditing ? "Edit Campaign" : "Create Campaign"} />

            <DashboardHero
                title={isEditing ? `Editing: ${campaign.name}` : "New Campaign"}
                breadcrumbs={breadcrumbs}
                actions={actions}
            />

            <form onSubmit={handleSubmit}>
                <div className="host-builder-grid">
                    {/* Sidebar: Components/Settings */}
                    <div className="host-builder-sidebar">
                        <GlassCard padding="host-builder-card">
                            <h4 className="host-builder-card-title">Campaign Name</h4>
                            <input
                                type="text"
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                                className="host-builder-input"
                                placeholder="e.g. Pre-Arrival Welcome"
                                required
                            />
                        </GlassCard>

                        <GlassCard padding="host-builder-card">
                            <h4 className="host-builder-card-title-mb6">Campaign Type</h4>
                            <div className="host-builder-type-grid">
                                <button
                                    type="button"
                                    onClick={() => setData('type', 'email')}
                                    className={`host-builder-type-btn ${data.type === 'email' ? 'host-builder-type-btn-active' : 'host-builder-type-btn-inactive'}`}
                                >
                                    <Mail size={20} />
                                    <span className="host-builder-type-label">Email</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setData('type', 'sms')}
                                    className={`host-builder-type-btn ${data.type === 'sms' ? 'host-builder-type-btn-active' : 'host-builder-type-btn-inactive'}`}
                                >
                                    <MessageSquare size={20} />
                                    <span className="host-builder-type-label">SMS</span>
                                </button>
                            </div>
                        </GlassCard>

                        <GlassCard padding="host-builder-card">
                            <h4 className="host-builder-card-title-mb6">Audience Logic</h4>
                            <div className="host-builder-audience-fields">
                                <div>
                                    <label className="host-builder-field-label">Trigger Event</label>
                                    <select
                                        value={data.trigger_event}
                                        onChange={e => setData('trigger_event', e.target.value)}
                                        className="host-builder-select"
                                    >
                                        <option>Guest Connects to WiFi</option>
                                        <option>24 Hours Before Arrival</option>
                                        <option>Day of Checkout</option>
                                        <option>Custom Date</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="host-builder-field-label">Wait Duration</label>
                                    <select
                                        value={data.trigger_delay}
                                        onChange={e => setData('trigger_delay', e.target.value)}
                                        className="host-builder-select"
                                    >
                                        <option>Instant</option>
                                        <option>1 Hour</option>
                                        <option>24 Hours</option>
                                        <option>3 Days</option>
                                    </select>
                                </div>
                            </div>
                        </GlassCard>

                        <div className="host-builder-actions">
                            <PillButton
                                variant="secondary"
                                onClick={handleSaveDraft}
                                disabled={processing}
                                className="host-builder-action-btn"
                            >
                                Save Draft
                            </PillButton>
                            <PillButton
                                variant="primary"
                                onClick={handleLaunch}
                                disabled={processing}
                                className="host-builder-action-btn"
                            >
                                {processing ? <Loader2 className="animate-spin" size={14} /> : <Send size={14} />}
                                {isEditing ? 'Update' : 'Launch'}
                            </PillButton>
                        </div>
                    </div>

                    {/* Main: Preview Canvas */}
                    <div className="host-builder-canvas-area">
                        {/* Toolbar */}
                        <div className="host-builder-toolbar">
                            <div className="host-builder-toolbar-btns">
                                <ToolbarBtn active={activeTab === 'design'} onClick={() => setActiveTab('design')} icon={<Palette size={16} />} label="Design" />
                                <ToolbarBtn active={activeTab === 'audience'} onClick={() => setActiveTab('audience')} icon={<Users size={16} />} label="Audience" />
                                <ToolbarBtn active={activeTab === 'schedule'} onClick={() => setActiveTab('schedule')} icon={<Clock size={16} />} label="Schedule" />
                            </div>
                        </div>

                        {/* Canvas Area */}
                        <div className="host-builder-canvas">
                            <div className="host-builder-canvas-bg" style={{ backgroundImage: 'radial-gradient(circle, #000 1px, transparent 1px)', backgroundSize: '30px 30px' }} />

                            {activeTab === 'design' && (
                                <div className="host-builder-canvas-inner">
                                    <div className="host-builder-email-preview">
                                        {data.type === 'email' ? (
                                            <>
                                                <div className="host-builder-email-header">
                                                    <img src="/legacy/assets/Tena-logo-square.jpg" className="w-16 h-16 rounded-2xl" />
                                                </div>
                                                <div className="host-builder-email-body">
                                                    <h2 className="host-builder-email-heading">
                                                        {data.name || 'Welcome to Paradise.'}
                                                    </h2>
                                                    <p className="host-builder-email-desc">
                                                        {data.content || "We're so glad you're here. Connect to our high-speed WiFi and start your vacation right now."}
                                                    </p>
                                                    <button className="host-builder-email-cta">
                                                        Open Guest Portal
                                                    </button>
                                                </div>
                                            </>
                                        ) : (
                                            <div className="host-builder-sms-preview">
                                                <div className="host-builder-sms-bubble">
                                                    <p className="host-builder-sms-text">
                                                        {data.content || "Hey {guest_name}! Welcome to {property_name}. Use code TENA15 for 15% off!"}
                                                    </p>
                                                    <span className="host-builder-sms-footer">Sent via Tena Automation</span>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {activeTab === 'audience' && (
                                <div className="host-builder-audience-panel">
                                    <div className="host-builder-audience-container">
                                        <div className="host-builder-audience-heading-wrap">
                                            <h3 className="host-builder-audience-heading">Who receives this?</h3>
                                            <p className="host-builder-audience-desc">Define your target audience for this campaign.</p>
                                        </div>

                                        <div className="host-builder-audience-form">
                                            <div>
                                                <label className="host-builder-audience-label">Target Segment</label>
                                                <select
                                                    value={data.target_audience}
                                                    onChange={e => setData('target_audience', e.target.value)}
                                                    className="host-builder-audience-select"
                                                >
                                                    <option value="all_guests">All Guests</option>
                                                    <option value="new_guests">New Guests (First Visit)</option>
                                                    <option value="returning_guests">Returning Guests</option>
                                                    <option value="vip_guests">VIP Guests</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label className="host-builder-audience-label">Property</label>
                                                <select
                                                    value={data.audience_property_id || 'all'}
                                                    onChange={e => setData('audience_property_id', e.target.value === 'all' ? null : e.target.value)}
                                                    className="host-builder-audience-select"
                                                >
                                                    <option value="all">All Properties</option>
                                                    {properties?.map(p => (
                                                        <option key={p.id} value={p.id}>{p.name}</option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div>
                                                <label className="host-builder-audience-label">Filter by Visit Date</label>
                                                <div className="host-builder-audience-date-grid">
                                                    <input
                                                        type="date"
                                                        value={data.audience_from || ''}
                                                        onChange={e => setData('audience_from', e.target.value)}
                                                        className="host-builder-audience-date-input"
                                                        placeholder="From"
                                                    />
                                                    <input
                                                        type="date"
                                                        value={data.audience_to || ''}
                                                        onChange={e => setData('audience_to', e.target.value)}
                                                        className="host-builder-audience-date-input"
                                                        placeholder="To"
                                                    />
                                                </div>
                                            </div>

                                            <div className="host-builder-audience-estimate">
                                                <p className="host-builder-audience-estimate-label">Estimated Reach</p>
                                                <p className="host-builder-audience-estimate-value">~150 guests</p>
                                                <p className="host-builder-audience-estimate-note">Based on current filters</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'schedule' && (
                                <div className="host-builder-schedule-panel">
                                    <div className="host-builder-schedule-container">
                                        <div className="host-builder-schedule-heading-wrap">
                                            <h3 className="host-builder-schedule-heading">When to send?</h3>
                                            <p className="host-builder-schedule-desc">Schedule your campaign delivery.</p>
                                        </div>

                                        <div className="host-builder-schedule-form">
                                            <div>
                                                <label className="host-builder-schedule-label">Send Timing</label>
                                                <div className="host-builder-schedule-timing-grid">
                                                    <button
                                                        type="button"
                                                        onClick={() => setData('trigger_event', 'Guest Connects to WiFi')}
                                                        className={`host-builder-schedule-timing-btn ${data.trigger_event === 'Guest Connects to WiFi' ? 'host-builder-schedule-timing-btn-active' : 'host-builder-schedule-timing-btn-inactive'}`}
                                                    >
                                                        <p className="host-builder-schedule-timing-title">On WiFi Connect</p>
                                                        <p className="host-builder-schedule-timing-sub">Instant trigger</p>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setData('trigger_event', '24 Hours Before Arrival')}
                                                        className={`host-builder-schedule-timing-btn ${data.trigger_event === '24 Hours Before Arrival' ? 'host-builder-schedule-timing-btn-active' : 'host-builder-schedule-timing-btn-inactive'}`}
                                                    >
                                                        <p className="host-builder-schedule-timing-title">Pre-Arrival</p>
                                                        <p className="host-builder-schedule-timing-sub">24h before check-in</p>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setData('trigger_event', 'Day of Checkout')}
                                                        className={`host-builder-schedule-timing-btn ${data.trigger_event === 'Day of Checkout' ? 'host-builder-schedule-timing-btn-active' : 'host-builder-schedule-timing-btn-inactive'}`}
                                                    >
                                                        <p className="host-builder-schedule-timing-title">Post-Stay</p>
                                                        <p className="host-builder-schedule-timing-sub">Day of checkout</p>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setData('trigger_event', 'Custom Date')}
                                                        className={`host-builder-schedule-timing-btn ${data.trigger_event === 'Custom Date' ? 'host-builder-schedule-timing-btn-active' : 'host-builder-schedule-timing-btn-inactive'}`}
                                                    >
                                                        <p className="host-builder-schedule-timing-title">Specific Date</p>
                                                        <p className="host-builder-schedule-timing-sub">Choose a date</p>
                                                    </button>
                                                </div>
                                            </div>

                                            {data.trigger_event === 'Custom Date' && (
                                                <div>
                                                    <label className="host-builder-schedule-label">Send Date & Time</label>
                                                    <input
                                                        type="datetime-local"
                                                        value={data.scheduled_at || ''}
                                                        onChange={e => setData('scheduled_at', e.target.value)}
                                                        className="host-builder-schedule-datetime"
                                                    />
                                                </div>
                                            )}

                                            <div>
                                                <label className="host-builder-schedule-label">Delay After Trigger</label>
                                                <select
                                                    value={data.trigger_delay}
                                                    onChange={e => setData('trigger_delay', e.target.value)}
                                                    className="host-builder-schedule-delay-select"
                                                >
                                                    <option>Instant</option>
                                                    <option>1 Hour</option>
                                                    <option>6 Hours</option>
                                                    <option>24 Hours</option>
                                                    <option>3 Days</option>
                                                    <option>7 Days</option>
                                                </select>
                                            </div>

                                            <div className="host-builder-schedule-summary">
                                                <p className="host-builder-schedule-summary-label">Schedule Summary</p>
                                                <p className="host-builder-schedule-summary-text">
                                                    {data.trigger_event === 'Custom Date' ? 'Sends on selected date' : `Sends ${data.trigger_delay.toLowerCase()} after "${data.trigger_event.toLowerCase()}"`}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </form>
        </DashboardLayout>
    );
}

function ToolbarBtn({ active, onClick, icon, label }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`host-builder-toolbar-btn ${active ? 'host-builder-toolbar-btn-active' : 'host-builder-toolbar-btn-inactive'}`}
        >
            {icon}
            {label}
        </button>
    );
}
