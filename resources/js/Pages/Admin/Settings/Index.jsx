import React, { useState, useEffect, useCallback, lazy, Suspense } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import { TwoColumnLayout, MainColumn, SidebarColumn, PageGrid } from '@/Layouts/LayoutPrimitives';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
const EmailPreview = lazy(() => import('@/Components/Admin/EmailPreview'));
import GlassModal from '@/Components/GlassModal';
import { FormField, TextInput, Select, CheckboxField, FormActions } from '@/Components/Forms/FormPrimitives';
import { Settings, Mail, Shield, Globe, Palette, Type, CheckCircle2, AlertCircle, Send, Eye, ChevronDown } from 'lucide-react';
import { notify } from '@/Components/Toast';
import { safeRoute, hasRoute } from '@/lib/route';
import EmailTemplateEditor from '@/Components/Admin/EmailTemplateEditor';
import './Index.css';
import BrandAssets from '@/Components/Admin/BrandAssets';
import { BRAND_COLORS } from '@/constants';

function debounce(func, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

const EMAIL_TEMPLATES = [
    {
        id: 'welcome',
        name: 'Welcome',
        subjectKey: 'welcome_email_subject',
        headingKey: 'welcome_email_heading',
        bodyKey: 'welcome_email_body',
        subjectPlaceholder: 'Welcome to TenaFi',
        headingPlaceholder: 'Welcome home, {name}.',
        bodyPlaceholder: 'Customize the welcome message...',
        variables: [
            { key: 'First Name', label: 'First Name', description: 'Recipient first name' },
            { key: 'Last Name', label: 'Last Name', description: 'Recipient last name' },
            { key: 'Email', label: 'Email', description: 'Recipient email' },
            { key: 'Business Name', label: 'Business Name', description: 'Your business name' },
            { key: 'Login URL', label: 'Login URL', description: 'Link to login page' },
        ],
    },
    {
        id: 'receipt',
        name: 'Payment Receipt',
        headingKey: 'receipt_email_heading',
        bodyKey: 'receipt_email_body',
        headingPlaceholder: 'Payment Received.',
        bodyPlaceholder: 'Customize the receipt message...',
        variables: [
            { key: 'Name', label: 'Name', description: 'Recipient name' },
            { key: 'Amount', label: 'Amount', description: 'Payment amount' },
            { key: 'Transaction ID', label: 'Transaction ID', description: 'Transaction reference' },
            { key: 'Date', label: 'Date', description: 'Payment date' },
            { key: 'Plan Name', label: 'Plan Name', description: 'Subscription plan' },
            { key: 'Business Name', label: 'Business Name', description: 'Your business name' },
        ],
    },
    {
        id: 'reset',
        name: 'Password Reset',
        headingKey: 'forgot_password_email_heading',
        bodyKey: 'forgot_password_email_body',
        headingPlaceholder: 'Reset Request.',
        bodyPlaceholder: 'Customize the reset instructions...',
        variables: [
            { key: 'Name', label: 'Name', description: 'Recipient name' },
            { key: 'Reset URL', label: 'Reset URL', description: 'Password reset link' },
            { key: 'Business Name', label: 'Business Name', description: 'Your business name' },
        ],
    },
    {
        id: 'waitlist_confirmation',
        name: 'Waitlist Confirmation',
        subjectKey: 'waitlist_confirmation_subject',
        headingKey: 'waitlist_confirmation_heading',
        bodyKey: 'waitlist_confirmation_body',
        subjectPlaceholder: "You're on the TenaFi waitlist!",
        headingPlaceholder: "You're on the list!",
        bodyPlaceholder: 'Customize the confirmation message...',
        variables: [
            { key: 'First Name', label: 'First Name', description: 'Recipient first name' },
            { key: 'Last Name', label: 'Last Name', description: 'Recipient last name' },
            { key: 'Email', label: 'Email', description: 'Recipient email' },
            { key: 'Property Type', label: 'Property Type', description: 'Their property type' },
            { key: 'Units', label: 'Units', description: 'Number of units' },
            { key: 'Primary Platform', label: 'Primary Platform', description: 'Their booking platform' },
            { key: 'Biggest Challenge', label: 'Biggest Challenge', description: 'Their biggest challenge' },
            { key: 'Business Name', label: 'Business Name', description: 'Your business name' },
        ],
    },
    {
        id: 'waitlist_welcome',
        name: 'Waitlist Welcome',
        subjectKey: 'waitlist_welcome_subject',
        headingKey: 'waitlist_welcome_heading',
        bodyKey: 'waitlist_welcome_body',
        subjectPlaceholder: 'Welcome to the TenaFi family!',
        headingPlaceholder: 'Welcome to the TenaFi family!',
        bodyPlaceholder: 'Customize the welcome message...',
        variables: [
            { key: 'First Name', label: 'First Name', description: 'Recipient first name' },
            { key: 'Last Name', label: 'Last Name', description: 'Recipient last name' },
            { key: 'Email', label: 'Email', description: 'Recipient email' },
            { key: 'Business Name', label: 'Business Name', description: 'Your business name' },
            { key: 'Business Address', label: 'Business Address', description: 'Your business address' },
        ],
    },
    {
        id: 'contact_enquiry',
        name: 'Contact Enquiry',
        headingKey: 'contact_enquiry_heading',
        bodyKey: 'contact_enquiry_body',
        headingPlaceholder: 'New contact enquiry',
        bodyPlaceholder: 'Leave blank to use the default layout, or write a custom body...',
        variables: [
            { key: 'Name', label: 'Sender Name', description: 'Name from the form' },
            { key: 'Email', label: 'Sender Email', description: 'Reply-to address' },
            { key: 'Subject', label: 'Subject', description: 'Subject line the sender chose' },
            { key: 'Message', label: 'Message', description: 'The visitor message' },
            { key: 'Business Name', label: 'Business Name', description: 'Your business name' },
        ],
    },
];

export default function Index({ settings, logos = [] }) {
    const [activeTab, setActiveTab] = useState('general');
    const [autoSaveStatus, setAutoSaveStatus] = useState('idle');
    const [testEmail, setTestEmail] = useState('');
    const [testTemplate, setTestTemplate] = useState('welcome');
    const [showPreview, setShowPreview] = useState(false);
    const [selectedTemplate, setSelectedTemplate] = useState('welcome');

    const flattened = Object.values(settings).flat().reduce((acc, curr) => {
        acc[curr.key] = curr.value;
        return acc;
    }, {});

    const { data, setData, post, processing } = useForm({
        settings: {
            site_name: flattened.site_name || 'TenaFi',
            maintenance_mode: flattened.maintenance_mode || '0',
            support_email: flattened.support_email || 'support@tena.com',
            signup_alert_emails: flattened.signup_alert_emails || '',
            signup_alert_webhook_url: flattened.signup_alert_webhook_url || '',
            trial_slots_total: flattened.trial_slots_total ?? '50',
            trial_slots_since: flattened.trial_slots_since || '',
            callbacks_enabled: flattened.callbacks_enabled || '0',
            demo_enabled: flattened.demo_enabled || '0',
            v2_pages_enabled: flattened.v2_pages_enabled || '0',
            v2_live: flattened.v2_live ?? '1',
            pricing_page_enabled: flattened.pricing_page_enabled || '0',
            event_accounts_enabled: flattened.event_accounts_enabled || '0',
            teams_enabled: flattened.teams_enabled || '0',
            staff_wifi_enabled: flattened.staff_wifi_enabled || '0',
            wifi_qr_enabled: flattened.wifi_qr_enabled || '0',
            visitor_log_enabled: flattened.visitor_log_enabled || '0',
            review_emails_enabled: flattened.review_emails_enabled || '0',
            event_billing_enabled: flattened.event_billing_enabled || '0',
            device_financing_enabled: flattened.device_financing_enabled || '0',
            property_transfers_enabled: flattened.property_transfers_enabled || '0',
            email_primary_color: flattened.email_primary_color || '#000000',
            email_accent_color: flattened.email_accent_color || BRAND_COLORS.primary,
            business_address: flattened.business_address || 'Nairobi, Kenya',
            welcome_email_heading: flattened.welcome_email_heading || '',
            welcome_email_body: flattened.welcome_email_body || '',
            welcome_email_subject: flattened.welcome_email_subject || '',
            receipt_email_heading: flattened.receipt_email_heading || '',
            receipt_email_body: flattened.receipt_email_body || '',
            forgot_password_email_heading: flattened.forgot_password_email_heading || '',
            forgot_password_email_body: flattened.forgot_password_email_body || '',
            waitlist_confirmation_subject: flattened.waitlist_confirmation_subject || '',
            waitlist_confirmation_heading: flattened.waitlist_confirmation_heading || '',
            waitlist_confirmation_body: flattened.waitlist_confirmation_body || '',
            waitlist_welcome_subject: flattened.waitlist_welcome_subject || '',
            waitlist_welcome_heading: flattened.waitlist_welcome_heading || '',
            waitlist_welcome_body: flattened.waitlist_welcome_body || '',
            contact_enquiry_heading: flattened.contact_enquiry_heading || '',
            contact_enquiry_body: flattened.contact_enquiry_body || '',
            billing_enabled: flattened.billing_enabled || 'auto',
        }
    });

    const updateSetting = (key, value) => {
        setData('settings', { ...data.settings, [key]: value });
    };

    const debouncedSave = useCallback(
        debounce(() => {
            setAutoSaveStatus('saving');
            post(route('admin.settings.update'), {
                preserveScroll: true,
                onSuccess: () => setAutoSaveStatus('saved'),
                onError: (errors) => {
                    setAutoSaveStatus('error');
                    notify.error(Object.values(errors)[0] || 'Settings could not be saved.');
                },
            });
        }, 2000),
        []
    );

    useEffect(() => {
        if (activeTab === 'branding') {
            debouncedSave();
        }
    }, [data.settings, debouncedSave, activeTab]);

    const tabs = [
        { id: 'general', name: 'General', icon: <Settings size={16} /> },
        { id: 'branding', name: 'Branding', icon: <Mail size={16} /> },
        { id: 'billing', name: 'Billing', icon: <Shield size={16} /> },
        { id: 'policies', name: 'Policies & Terms', icon: <Globe size={16} /> },
    ];

    const renderStatus = () => {
        if (autoSaveStatus === 'idle') return null;
        const icons = {
            saving: <div className="settings-page__status-dot" />,
            saved: <CheckCircle2 size={14} className="settings-page__status-icon--saved" />,
            error: <AlertCircle size={14} className="settings-page__status-icon--error" />,
        };
        const labels = {
            saving: 'Syncing changes...',
            saved: 'Synced to cloud',
            error: 'Sync failed',
        };
        return (
            <div className="settings-page__status-row">
                {icons[autoSaveStatus]}
                {labels[autoSaveStatus]}
            </div>
        );
    };

    const sendTestEmail = () => {
        if (!testEmail) {
            notify.error('Enter an email address to test.');
            return;
        }
        router.post(safeRoute('admin.notifications.test'), {
            email: testEmail,
            template: testTemplate,
        }, {
            onSuccess: () => notify.success('Test email sent successfully.'),
            onError: () => notify.error('Failed to send test email.'),
        });
    };

    const handleSave = () => {
        setAutoSaveStatus('saving');
        post(route('admin.settings.update'), {
            preserveScroll: true,
            onSuccess: () => setAutoSaveStatus('saved'),
            onError: (errors) => {
                setAutoSaveStatus('error');
                notify.error(Object.values(errors)[0] || 'Settings could not be saved.');
            },
        });
    };

    const activeTemplateConfig = EMAIL_TEMPLATES.find(t => t.id === selectedTemplate);

    return (
        <PageShell
            title="Global Settings"
            subtitle="Configure platform-wide variables and branding"
            headTitle="Settings"
            breadcrumbs={[{ label: 'Settings', href: route('admin.settings.index') }]}
            rootRoute="admin.dashboard"
            actions={[
                { label: processing ? 'Saving...' : 'Manual Save', onClick: handleSave, variant: 'primary', icon: null },
            ]}
        >
            <Head title="Settings" />
            <TwoColumnLayout>
                <SidebarColumn span={3}>
                    <div className="settings-page__tabs">
                        {tabs.map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`settings-page__tab ${activeTab === tab.id ? 'settings-page__tab--active' : 'settings-page__tab--inactive'}`}
                            >
                                {tab.icon}
                                {tab.name}
                            </button>
                        ))}
                    </div>
                    {activeTab === 'branding' && (
                        <div className="settings-page__status">
                            {renderStatus()}
                        </div>
                    )}
                </SidebarColumn>

                <MainColumn span={9}>
                    <form onSubmit={(e) => e.preventDefault()} className="space-y-8">
                        {activeTab === 'general' && (
                            <GlassCard padding="p-8">
                                <div className="settings-page__card-inner">
                                    <div className="settings-page__card-header">
                                        <div className="settings-page__card-icon"><Globe size={24} /></div>
                                        <div>
                                            <h3 className="settings-page__card-title">Platform Identity</h3>
                                            <p className="settings-page__card-subtitle">General information and status</p>
                                        </div>
                                    </div>

                                    <FormField label="Site Name">
                                        <TextInput
                                            value={data.settings.site_name}
                                            onChange={(e) => updateSetting('site_name', e.target.value)}
                                        />
                                    </FormField>

                                    <FormField label="Support Email">
                                        <TextInput
                                            type="email"
                                            value={data.settings.support_email}
                                            onChange={(e) => updateSetting('support_email', e.target.value)}
                                        />
                                    </FormField>

                                    <FormField label="Sign-up alert emails" hint="Comma-separated. Every new host/business sign-up is emailed here. Empty = support email.">
                                        <TextInput
                                            value={data.settings.signup_alert_emails}
                                            placeholder="glen@tena-fi.com, ops@tena-fi.com"
                                            onChange={(e) => updateSetting('signup_alert_emails', e.target.value)}
                                        />
                                    </FormField>

                                    <FormField label="Sign-up alert webhook (optional)" hint="Receives a JSON POST per sign-up. Connect it to Zapier / Make / Twilio to forward alerts to WhatsApp.">
                                        <TextInput
                                            type="url"
                                            value={data.settings.signup_alert_webhook_url}
                                            placeholder="https://hooks.zapier.com/..."
                                            onChange={(e) => updateSetting('signup_alert_webhook_url', e.target.value)}
                                        />
                                    </FormField>

                                    <FormField label="Free trial slots" hint="The total behind “X of 50 free trial slots taken” on the public pages. Taken is counted from real sign-ups. 0 hides the counter.">
                                        <TextInput
                                            type="number"
                                            min="0"
                                            value={data.settings.trial_slots_total}
                                            onChange={(e) => updateSetting('trial_slots_total', e.target.value)}
                                        />
                                    </FormField>

                                    <FormField label="Count trial sign-ups from" hint="Sign-ups from this date count as taken slots (marking one inactive frees its slot). Leave empty to count every sign-up, including the old waitlist.">
                                        <TextInput
                                            type="date"
                                            value={data.settings.trial_slots_since}
                                            onChange={(e) => updateSetting('trial_slots_since', e.target.value)}
                                        />
                                    </FormField>

                                    <CheckboxField
                                        label="“Get a call back” form on the homepage (leads go to Sign-ups, with the usual alerts)"
                                        checked={data.settings.callbacks_enabled === '1'}
                                        onChange={() => updateSetting('callbacks_enabled', data.settings.callbacks_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Live demo: /demo and the homepage “Try it yourself” section (nothing visitors type is saved)"
                                        checked={data.settings.demo_enabled === '1'}
                                        onChange={() => updateSetting('demo_enabled', data.settings.demo_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="V2 design on the live site: /, /hosts, /business and /events show the v2 pages (/v2 links redirect there). Untick to bring back the previous design."
                                        checked={data.settings.v2_live === '1'}
                                        onChange={() => updateSetting('v2_live', data.settings.v2_live === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Pricing page: show /pricing (TenaFi Lite, Pro and Outdoor, and the two ways to pay) to everyone, and link to it from each page's pricing. Admins can always preview it. Prices are edited in Public Pages → Devices & pricing."
                                        checked={data.settings.pricing_page_enabled === '1'}
                                        onChange={() => updateSetting('pricing_page_enabled', data.settings.pricing_page_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="V2 public pages: open the new design at /v2 to everyone (admins can always preview it). Never shown to search engines."
                                        checked={data.settings.v2_pages_enabled === '1'}
                                        onChange={() => updateSetting('v2_pages_enabled', data.settings.v2_pages_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Event accounts: event enquiries become event accounts (attendees and venues) instead of business accounts. Also removes the site's “Coming soon” badges on the event features (sponsors, extra question, attendee lists)"
                                        checked={data.settings.event_accounts_enabled === '1'}
                                        onChange={() => updateSetting('event_accounts_enabled', data.settings.event_accounts_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Teams: account owners can invite managers and staff, each with their own login and sites"
                                        checked={data.settings.teams_enabled === '1'}
                                        onChange={() => updateSetting('teams_enabled', data.settings.teams_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Staff WiFi: a site's staff network gets a staff sign-in (name and staff ID or work email), kept out of customer lists and campaigns"
                                        checked={data.settings.staff_wifi_enabled === '1'}
                                        onChange={() => updateSetting('staff_wifi_enabled', data.settings.staff_wifi_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="WiFi QR code: each site's page gets a printable “scan to join the WiFi” QR code for rooms, tables and reception"
                                        checked={data.settings.wifi_qr_enabled === '1'}
                                        onChange={() => updateSetting('wifi_qr_enabled', data.settings.wifi_qr_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Visitor log: a site's WiFi sign-in asks who the visitor is here to see, and reception gets a dated visitor log with sign-out"
                                        checked={data.settings.visitor_log_enabled === '1'}
                                        onChange={() => updateSetting('visitor_log_enabled', data.settings.visitor_log_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Review emails: a site can send its thank-you and Google review request as an email in its own name, logo and colour, and ask everyone captured so far in one go"
                                        checked={data.settings.review_emails_enabled === '1'}
                                        onChange={() => updateSetting('review_emails_enabled', data.settings.review_emails_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Pay per event: event organisers without a plan ask for a quote (Admin → Event quotes), then pay it by M-Pesa or card to switch the event's WiFi page on"
                                        checked={data.settings.event_billing_enabled === '1'}
                                        onChange={() => updateSetting('event_billing_enabled', data.settings.event_billing_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Device register: Admin → Devices lists every TenaFi device paid for monthly, with its instalments, payments and status (recorded by hand for now)"
                                        checked={data.settings.device_financing_enabled === '1'}
                                        onChange={() => updateSetting('device_financing_enabled', data.settings.device_financing_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Property transfers: owners can hand a property, with its WiFi page, devices and guest list, to another TenaFi account (Properties → Transfers). The recipient accepts. Guests aren't told yet, so use it for moves between your own accounts until that's built."
                                        checked={data.settings.property_transfers_enabled === '1'}
                                        onChange={() => updateSetting('property_transfers_enabled', data.settings.property_transfers_enabled === '1' ? '0' : '1')}
                                    />

                                    <CheckboxField
                                        label="Maintenance Mode"
                                        checked={data.settings.maintenance_mode === '1'}
                                        onChange={() => updateSetting('maintenance_mode', data.settings.maintenance_mode === '1' ? '0' : '1')}
                                    />
                                </div>
                            </GlassCard>
                        )}

                        {activeTab === 'billing' && (
                            <GlassCard padding="p-8">
                                <div className="settings-page__card-inner">
                                    <div className="settings-page__card-header">
                                        <div className="settings-page__card-icon"><Shield size={24} /></div>
                                        <div>
                                            <h3 className="settings-page__card-title">Billing Mode</h3>
                                            <p className="settings-page__card-subtitle">Control subscription enforcement</p>
                                        </div>
                                    </div>

                                    <FormField label="Mode">
                                        <Select
                                            value={data.settings.billing_enabled}
                                            onChange={(e) => updateSetting('billing_enabled', e.target.value)}
                                        >
                                            <option value="auto">Auto (enabled when Paystack keys are present)</option>
                                            <option value="enabled">Enabled (force subscription checks)</option>
                                            <option value="disabled">Disabled (allow simulation / dev mode)</option>
                                        </Select>
                                    </FormField>

                                    <p className="settings-page__hint">
                                        When set to <strong>Disabled</strong>, hosts can access the M-Pesa simulation endpoint from the billing page without needing a real payment provider configured.
                                    </p>
                                </div>
                            </GlassCard>
                        )}

                        {activeTab === 'branding' && (
                            <div className="space-y-6">
                                <BrandAssets logos={logos} />

                                {/* Visual Theme */}
                                <GlassCard padding="p-6">
                                    <div className="space-y-4">
                                        <div className="settings-page__card-header">
                                            <div className="settings-page__card-icon"><Palette size={20} /></div>
                                            <div>
                                                <h3 className="settings-page__card-title">Visual Theme</h3>
                                                <p className="settings-page__card-subtitle">Email colors</p>
                                            </div>
                                        </div>

                                        <PageGrid cols={2} gap="gap-4">
                                            <FormField label="Primary Color">
                                                <div className="settings-page__color-field">
                                                    <input
                                                        type="color"
                                                        value={data.settings.email_primary_color}
                                                        onChange={(e) => updateSetting('email_primary_color', e.target.value)}
                                                        className="settings-page__color-picker"
                                                    />
                                                    <TextInput
                                                        value={data.settings.email_primary_color}
                                                        onChange={(e) => updateSetting('email_primary_color', e.target.value)}
                                                        className="settings-page__color-input"
                                                    />
                                                </div>
                                            </FormField>

                                            <FormField label="Accent Color">
                                                <div className="settings-page__color-field">
                                                    <input
                                                        type="color"
                                                        value={data.settings.email_accent_color}
                                                        onChange={(e) => updateSetting('email_accent_color', e.target.value)}
                                                        className="settings-page__color-picker"
                                                    />
                                                    <TextInput
                                                        value={data.settings.email_accent_color}
                                                        onChange={(e) => updateSetting('email_accent_color', e.target.value)}
                                                        className="settings-page__color-input"
                                                    />
                                                </div>
                                            </FormField>
                                        </PageGrid>
                                    </div>
                                </GlassCard>

                                {/* Template Selector */}
                                <GlassCard padding="p-6">
                                    <div className="space-y-4">
                                        <div className="settings-page__card-header">
                                            <div className="settings-page__card-icon"><Type size={20} /></div>
                                            <div>
                                                <h3 className="settings-page__card-title">Email Templates</h3>
                                                <p className="settings-page__card-subtitle">Customize each email template</p>
                                            </div>
                                        </div>

                                        <div className="settings-page__template-selector">
                                            <label className="settings-page__template-selector-label">Select Template</label>
                                            <div className="settings-page__template-selector-wrap">
                                                <select
                                                    value={selectedTemplate}
                                                    onChange={(e) => setSelectedTemplate(e.target.value)}
                                                    className="settings-page__template-select"
                                                >
                                                    {EMAIL_TEMPLATES.map((t) => (
                                                        <option key={t.id} value={t.id}>{t.name}</option>
                                                    ))}
                                                </select>
                                                <ChevronDown size={16} className="settings-page__template-selector-icon" />
                                            </div>
                                        </div>

                                        {/* Active Template Fields */}
                                        {activeTemplateConfig && (
                                            <div className="settings-page__template-fields" key={activeTemplateConfig.id}>
                                                {activeTemplateConfig.subjectKey && (
                                                    <FormField label="Subject">
                                                        <TextInput
                                                            value={data.settings[activeTemplateConfig.subjectKey] || ''}
                                                            onChange={(e) => updateSetting(activeTemplateConfig.subjectKey, e.target.value)}
                                                            placeholder={activeTemplateConfig.subjectPlaceholder}
                                                        />
                                                    </FormField>
                                                )}

                                                <FormField label="Heading">
                                                    <TextInput
                                                        value={data.settings[activeTemplateConfig.headingKey] || ''}
                                                        onChange={(e) => updateSetting(activeTemplateConfig.headingKey, e.target.value)}
                                                        placeholder={activeTemplateConfig.headingPlaceholder}
                                                    />
                                                </FormField>

                                                <EmailTemplateEditor
                                                    label="Body Text"
                                                    value={data.settings[activeTemplateConfig.bodyKey] || ''}
                                                    onChange={(val) => updateSetting(activeTemplateConfig.bodyKey, val)}
                                                    placeholder={activeTemplateConfig.bodyPlaceholder}
                                                    variables={activeTemplateConfig.variables}
                                                />
                                            </div>
                                        )}
                                    </div>
                                </GlassCard>

                                {/* Preview & Test */}
                                <GlassCard padding="p-6">
                                    <div className="space-y-4">
                                        <div className="settings-page__card-header">
                                            <div className="settings-page__card-icon"><Send size={20} /></div>
                                            <div>
                                                <h3 className="settings-page__card-title">Preview & Test</h3>
                                                <p className="settings-page__card-subtitle">Send test emails and preview templates</p>
                                            </div>
                                        </div>

                                        <div className="flex gap-3 items-end">
                                            <FormField label="Recipient Email" className="flex-1">
                                                <TextInput
                                                    value={testEmail}
                                                    onChange={(e) => setTestEmail(e.target.value)}
                                                    placeholder="test@example.com"
                                                    type="email"
                                                />
                                            </FormField>
                                            <FormField label="Template">
                                                <select
                                                    value={testTemplate}
                                                    onChange={(e) => setTestTemplate(e.target.value)}
                                                    className="form-select"
                                                >
                                                    {EMAIL_TEMPLATES.map((t) => (
                                                        <option key={t.id} value={t.id}>{t.name}</option>
                                                    ))}
                                                </select>
                                            </FormField>
                                            <PillButton variant="black" onClick={sendTestEmail}>
                                                <Send size={14} className="mr-1.5" /> Send Test
                                            </PillButton>
                                        </div>

                                        <div className="flex gap-3 items-center pt-2">
                                            <PillButton
                                                variant="secondary"
                                                onClick={() => setShowPreview(true)}
                                                icon={<Eye size={16} />}
                                            >
                                                Preview Emails
                                            </PillButton>
                                            <span className="settings-page__preview-hint">
                                                See how your emails will look to recipients
                                            </span>
                                        </div>
                                    </div>
                                </GlassCard>
                            </div>
                        )}

                        {activeTab === 'policies' && (
                            <GlassCard padding="p-8">
                                <div className="settings-page__card-inner">
                                    <div className="settings-page__card-header">
                                        <div className="settings-page__card-icon"><Globe size={24} /></div>
                                        <div>
                                            <h3 className="settings-page__card-title">Policies & Terms</h3>
                                            <p className="settings-page__card-subtitle">Manage legal documents and policies</p>
                                        </div>
                                    </div>
                                    <div className="space-y-4">
                                        <p className="settings-page__hint">
                                            Manage your platform's legal documents including Privacy Policy, Terms of Service, Cookie Policy, and more.
                                        </p>
                                        <PillButton
                                            variant="black"
                                            onClick={() => router.visit(safeRoute('admin.policies.index'))}
                                        >
                                            Manage Policies
                                        </PillButton>
                                    </div>
                                </div>
                            </GlassCard>
                        )}

                        {activeTab !== 'branding' && (
                            <FormActions>
                                <PillButton variant="primary" processing={processing} onClick={handleSave}>
                                    Save Settings
                                </PillButton>
                            </FormActions>
                        )}
                    </form>
                </MainColumn>
            </TwoColumnLayout>

            {/* Email Preview Modal */}
            <GlassModal
                isOpen={showPreview}
                onClose={() => setShowPreview(false)}
                title="Email Preview"
                maxWidth="6xl"
            >
                <div className="settings-page__preview-modal">
                    <Suspense fallback={<div className="p-8 text-center text-black/40">Loading preview...</div>}>
                        <EmailPreview settings={data.settings} />
                    </Suspense>
                </div>
            </GlassModal>
        </PageShell>
    );
}
