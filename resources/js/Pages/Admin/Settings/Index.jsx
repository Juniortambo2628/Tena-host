import React, { useState, useEffect, useCallback } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import { TwoColumnLayout, MainColumn, SidebarColumn, PageGrid } from '@/Layouts/LayoutPrimitives';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import EmailPreview from '@/Components/Admin/EmailPreview';
import GlassModal from '@/Components/GlassModal';
import { FormField, TextInput, Select, CheckboxField, FormActions } from '@/Components/Forms/FormPrimitives';
import { Settings, Mail, Shield, Globe, Palette, Type, CheckCircle2, AlertCircle, Send, Eye } from 'lucide-react';
import { notify } from '@/Components/Toast';
import { safeRoute, hasRoute } from '@/lib/route';
import ContentField from '@/Components/CMS/ContentField';
import './Index.css';

function debounce(func, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

export default function Index({ settings }) {
    const [activeTab, setActiveTab] = useState('general');
    const [autoSaveStatus, setAutoSaveStatus] = useState('idle');
    const [testEmail, setTestEmail] = useState('');
    const [testTemplate, setTestTemplate] = useState('welcome');
    const [showPreview, setShowPreview] = useState(false);

    const flattened = Object.values(settings).flat().reduce((acc, curr) => {
        acc[curr.key] = curr.value;
        return acc;
    }, {});

    const { data, setData, post, processing } = useForm({
        settings: {
            site_name: flattened.site_name || 'Tena Platform',
            maintenance_mode: flattened.maintenance_mode || '0',
            support_email: flattened.support_email || 'support@tena.com',
            email_primary_color: flattened.email_primary_color || '#000000',
            email_accent_color: flattened.email_accent_color || '#FFD300',
            business_address: flattened.business_address || 'Nairobi, Kenya',
            logo_url: flattened.logo_url || '/legacy/assets/Tena-logo-square.jpg',
            welcome_email_heading: flattened.welcome_email_heading || '',
            welcome_email_body: flattened.welcome_email_body || '',
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
                onError: () => setAutoSaveStatus('error'),
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
        { id: 'branding', name: 'Email Branding', icon: <Mail size={16} /> },
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
            onError: () => setAutoSaveStatus('error'),
        });
    };

    return (
        <PageShell
            title="Global Settings"
            subtitle="Configure platform-wide variables and branding"
            headTitle="Settings"
            breadcrumbs={[{ label: 'Settings', href: route('admin.settings.index') }]}
            rootRoute="admin.dashboard"
            actions={[
                { label: processing ? 'Saving...' : 'Manual Save', onClick: handleSave, variant: 'black', icon: null },
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
                            <div className="space-y-8">
                                {/* Preview Toggle Button */}
                                <div className="settings-page__preview-toggle">
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

                                <div className="space-y-8">
                                    <GlassCard padding="p-8">
                                        <div className="space-y-6">
                                            <div className="settings-page__card-header">
                                                <div className="settings-page__card-icon"><Palette size={24} /></div>
                                                <div>
                                                    <h3 className="settings-page__card-title">Visual Theme</h3>
                                                    <p className="settings-page__card-subtitle">Colors and visual assets</p>
                                                </div>
                                            </div>

                                            <PageGrid cols={2} gap="gap-6">
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

                                            <FormField label="Logo URL">
                                                <TextInput
                                                    value={data.settings.logo_url}
                                                    onChange={(e) => updateSetting('logo_url', e.target.value)}
                                                />
                                            </FormField>
                                        </div>
                                    </GlassCard>

                                    <GlassCard padding="p-8">
                                        <div className="space-y-8">
                                            <div className="settings-page__card-header">
                                                <div className="settings-page__card-icon"><Type size={24} /></div>
                                                <div>
                                                    <h3 className="settings-page__card-title">Email Content</h3>
                                                    <p className="settings-page__card-subtitle">Customize template text</p>
                                                </div>
                                            </div>

                                            <div className="settings-page__template-section">
                                                <h4 className="settings-page__template-title">Welcome Template</h4>
                                                <FormField label="Heading">
                                                    <TextInput
                                                        value={data.settings.welcome_email_heading}
                                                        onChange={(e) => updateSetting('welcome_email_heading', e.target.value)}
                                                        placeholder="Welcome home, {name}."
                                                    />
                                                </FormField>
                                                <FormField label="Body Text">
                                                    <ContentField
                                                        type="richtext"
                                                        value={data.settings.welcome_email_body}
                                                        onChange={(val) => updateSetting('welcome_email_body', val)}
                                                        placeholder="Customize the welcome message..."
                                                    />
                                                </FormField>
                                            </div>

                                            <div className="settings-page__template-section">
                                                <h4 className="settings-page__template-title">Receipt Template</h4>
                                                <FormField label="Heading">
                                                    <TextInput
                                                        value={data.settings.receipt_email_heading}
                                                        onChange={(e) => updateSetting('receipt_email_heading', e.target.value)}
                                                        placeholder="Payment Received."
                                                    />
                                                </FormField>
                                                <FormField label="Body Text">
                                                    <ContentField
                                                        type="richtext"
                                                        value={data.settings.receipt_email_body}
                                                        onChange={(val) => updateSetting('receipt_email_body', val)}
                                                        placeholder="Customize the receipt message..."
                                                    />
                                                </FormField>
                                            </div>

                                            <div className="settings-page__template-section">
                                                <h4 className="settings-page__template-title">Password Reset Template</h4>
                                                <FormField label="Heading">
                                                    <TextInput
                                                        value={data.settings.forgot_password_email_heading}
                                                        onChange={(e) => updateSetting('forgot_password_email_heading', e.target.value)}
                                                        placeholder="Reset Request."
                                                    />
                                                </FormField>
                                                <FormField label="Body Text">
                                                    <ContentField
                                                        type="richtext"
                                                        value={data.settings.forgot_password_email_body}
                                                        onChange={(val) => updateSetting('forgot_password_email_body', val)}
                                                        placeholder="Customize the reset instructions..."
                                                    />
                                                </FormField>
                                            </div>

                                            <div className="settings-page__template-section">
                                                <h4 className="settings-page__template-title">Waitlist Confirmation Template</h4>
                                                <FormField label="Subject">
                                                    <TextInput
                                                        value={data.settings.waitlist_confirmation_subject || ''}
                                                        onChange={(e) => updateSetting('waitlist_confirmation_subject', e.target.value)}
                                                        placeholder="You're on the Tena waitlist!"
                                                    />
                                                </FormField>
                                                <FormField label="Heading">
                                                    <TextInput
                                                        value={data.settings.waitlist_confirmation_heading || ''}
                                                        onChange={(e) => updateSetting('waitlist_confirmation_heading', e.target.value)}
                                                        placeholder="You're on the list!"
                                                    />
                                                </FormField>
                                                <FormField label="Body Text">
                                                    <ContentField
                                                        type="richtext"
                                                        value={data.settings.waitlist_confirmation_body || ''}
                                                        onChange={(val) => updateSetting('waitlist_confirmation_body', val)}
                                                        placeholder="Customize the confirmation message..."
                                                    />
                                                </FormField>
                                            </div>

                                            <div className="settings-page__template-section">
                                                <h4 className="settings-page__template-title">Waitlist Welcome Template</h4>
                                                <FormField label="Subject">
                                                    <TextInput
                                                        value={data.settings.waitlist_welcome_subject || ''}
                                                        onChange={(e) => updateSetting('waitlist_welcome_subject', e.target.value)}
                                                        placeholder="Welcome to the Tena Family!"
                                                    />
                                                </FormField>
                                                <FormField label="Heading">
                                                    <TextInput
                                                        value={data.settings.waitlist_welcome_heading || ''}
                                                        onChange={(e) => updateSetting('waitlist_welcome_heading', e.target.value)}
                                                        placeholder="Welcome to the Tena Family!"
                                                    />
                                                </FormField>
                                                <FormField label="Body Text">
                                                    <ContentField
                                                        type="richtext"
                                                        value={data.settings.waitlist_welcome_body || ''}
                                                        onChange={(val) => updateSetting('waitlist_welcome_body', val)}
                                                        placeholder="Customize the welcome message..."
                                                    />
                                                </FormField>
                                            </div>

                                            <div className="settings-page__template-section">
                                                <h4 className="settings-page__template-title">Send Test Email</h4>
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
                                                            <option value="welcome">Welcome</option>
                                                            <option value="password_changed">Password Changed</option>
                                                            <option value="otp">OTP</option>
                                                            <option value="receipt">Payment Receipt</option>
                                                            <option value="waitlist_confirmation">Waitlist Confirmation</option>
                                                            <option value="waitlist_welcome">Waitlist Welcome</option>
                                                        </select>
                                                    </FormField>
                                                    <PillButton variant="black" onClick={sendTestEmail}>
                                                        <Send size={14} className="mr-1.5" /> Send Test
                                                    </PillButton>
                                                </div>
                                            </div>
                                        </div>
                                    </GlassCard>
                                </div>
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
                    <EmailPreview settings={data.settings} />
                </div>
            </GlassModal>
        </PageShell>
    );
}
