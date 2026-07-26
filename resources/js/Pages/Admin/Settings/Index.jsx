import React, { useState, useEffect, useCallback } from 'react';
import { Head, useForm } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import { TwoColumnLayout, MainColumn, SidebarColumn, PageGrid } from '@/Layouts/LayoutPrimitives';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import EmailPreview from '@/Components/Admin/EmailPreview';
import { FormField, TextInput, TextArea, Select, CheckboxField, FormActions } from '@/Components/Forms/FormPrimitives';
import { Settings, Mail, Shield, Globe, Palette, Type, CheckCircle2, AlertCircle } from 'lucide-react';
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
                                            <option value="auto">Auto (enabled when Stripe keys are present)</option>
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
                            <TwoColumnLayout gap="gap-8" className="items-start">
                                <MainColumn span={5} className="space-y-8">
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
                                                    <TextArea
                                                        value={data.settings.welcome_email_body}
                                                        onChange={(e) => updateSetting('welcome_email_body', e.target.value)}
                                                        placeholder="Customize the welcome message..."
                                                        rows={4}
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
                                                    <TextArea
                                                        value={data.settings.receipt_email_body}
                                                        onChange={(e) => updateSetting('receipt_email_body', e.target.value)}
                                                        placeholder="Customize the receipt message..."
                                                        rows={4}
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
                                                    <TextArea
                                                        value={data.settings.forgot_password_email_body}
                                                        onChange={(e) => updateSetting('forgot_password_email_body', e.target.value)}
                                                        placeholder="Customize the reset instructions..."
                                                        rows={4}
                                                    />
                                                </FormField>
                                            </div>
                                        </div>
                                    </GlassCard>
                                </MainColumn>

                                <SidebarColumn span={7}>
                                    <div className="settings-page__preview-wrapper">
                                        <EmailPreview settings={data.settings} />
                                    </div>
                                </SidebarColumn>
                            </TwoColumnLayout>
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
        </PageShell>
    );
}
