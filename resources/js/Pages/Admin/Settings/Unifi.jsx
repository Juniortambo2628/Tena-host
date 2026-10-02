import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import { TwoColumnLayout, MainColumn } from '@/Layouts/LayoutPrimitives';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import { FormField, TextInput, Select, CheckboxField, FormActions } from '@/Components/Forms/FormPrimitives';
import { Wifi, Plug, CheckCircle2, AlertCircle } from 'lucide-react';
import { notify } from '@/Components/Toast';

export default function Unifi({ settings, portal_url }) {
    const { data, setData, post, processing } = useForm({
        base_url: settings.base_url || '',
        username: settings.username || '',
        password: '',
        site: settings.site || 'default',
        is_os: settings.is_os ?? true,
        verify_ssl: settings.verify_ssl ?? false,
        auth_minutes: settings.auth_minutes ?? 1440,
        timeout: settings.timeout ?? 10,
    });

    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState(null);

    const save = () => {
        post(route('admin.wifi.update'), {
            preserveScroll: true,
            onSuccess: () => notify.success('UniFi settings saved.'),
            onError: () => notify.error('Could not save settings. Check the fields.'),
        });
    };

    const testConnection = async () => {
        setTesting(true);
        setTestResult(null);
        try {
            const { data: result } = await window.axios.post(route('admin.wifi.test'), {
                base_url: data.base_url,
                username: data.username,
                password: data.password,
                site: data.site,
                is_os: data.is_os,
                verify_ssl: data.verify_ssl,
                timeout: data.timeout,
            });
            setTestResult(result);
            result.ok ? notify.success(result.message) : notify.error(result.message);
        } catch (e) {
            const message = e?.response?.data?.message || 'Connection test failed.';
            setTestResult({ ok: false, message });
            notify.error(message);
        } finally {
            setTesting(false);
        }
    };

    return (
        <PageShell
            title="WiFi Portal"
            subtitle="Connect the guest splash page to your UniFi controller"
            headTitle="WiFi Portal"
            breadcrumbs={[{ label: 'WiFi Portal', href: route('admin.wifi.edit') }]}
            rootRoute="admin.dashboard"
        >
            <Head title="WiFi Portal" />
            <TwoColumnLayout>
                <MainColumn span={12}>
                    <form onSubmit={(e) => e.preventDefault()} className="space-y-6">
                        <GlassCard padding="p-8">
                            <div className="space-y-6">
                                <div className="flex items-center gap-3">
                                    <div className="settings-page__card-icon"><Wifi size={24} /></div>
                                    <div>
                                        <h3 className="settings-page__card-title">UniFi Controller</h3>
                                        <p className="settings-page__card-subtitle">
                                            Guest devices tap “Connect” on the splash page and are authorized here.
                                        </p>
                                    </div>
                                </div>

                                <FormField label="Controller URL">
                                    <TextInput
                                        value={data.base_url}
                                        onChange={(e) => setData('base_url', e.target.value)}
                                        placeholder="https://192.168.1.1  or  https://controller.example.com:8443"
                                    />
                                </FormField>

                                <FormField label="Controller type">
                                    <Select
                                        value={data.is_os ? 'os' : 'classic'}
                                        onChange={(e) => setData('is_os', e.target.value === 'os')}
                                    >
                                        <option value="os">UniFi OS — UDM / UDM-Pro / Cloud Key Gen2+</option>
                                        <option value="classic">Classic self-hosted controller (port 8443)</option>
                                    </Select>
                                </FormField>

                                <FormField label="Admin username">
                                    <TextInput
                                        value={data.username}
                                        onChange={(e) => setData('username', e.target.value)}
                                        autoComplete="off"
                                        placeholder="A local controller admin"
                                    />
                                </FormField>

                                <FormField label="Admin password">
                                    <TextInput
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        autoComplete="new-password"
                                        placeholder={settings.has_password ? '•••••••• (leave blank to keep current)' : 'Controller admin password'}
                                    />
                                </FormField>

                                <FormField label="Site">
                                    <TextInput
                                        value={data.site}
                                        onChange={(e) => setData('site', e.target.value)}
                                        placeholder="default"
                                    />
                                </FormField>

                                <FormField label="Authorization window (minutes)">
                                    <TextInput
                                        type="number"
                                        value={data.auth_minutes}
                                        onChange={(e) => setData('auth_minutes', e.target.value)}
                                        placeholder="1440"
                                    />
                                </FormField>

                                <FormField label="Request timeout (seconds)">
                                    <TextInput
                                        type="number"
                                        value={data.timeout}
                                        onChange={(e) => setData('timeout', e.target.value)}
                                        placeholder="10"
                                    />
                                </FormField>

                                <CheckboxField
                                    label="Verify the controller's SSL certificate (leave off for self-signed controllers)"
                                    checked={data.verify_ssl}
                                    onChange={() => setData('verify_ssl', !data.verify_ssl)}
                                />

                                {testResult && (
                                    <div
                                        className="flex items-start gap-2 rounded-xl px-4 py-3 text-sm"
                                        style={{
                                            background: testResult.ok ? '#dcfce7' : '#fef2f2',
                                            color: testResult.ok ? '#166534' : '#b91c1c',
                                        }}
                                    >
                                        {testResult.ok ? <CheckCircle2 size={18} /> : <AlertCircle size={18} />}
                                        <span>{testResult.message}</span>
                                    </div>
                                )}

                                <FormActions>
                                    <PillButton
                                        variant="secondary"
                                        onClick={testConnection}
                                        disabled={testing}
                                        icon={<Plug size={16} />}
                                    >
                                        {testing ? 'Testing…' : 'Test connection'}
                                    </PillButton>
                                    <PillButton variant="primary" onClick={save} disabled={processing}>
                                        {processing ? 'Saving…' : 'Save settings'}
                                    </PillButton>
                                </FormActions>
                            </div>
                        </GlassCard>

                        <GlassCard padding="p-6">
                            <div className="space-y-2">
                                <h3 className="settings-page__card-title">Point UniFi here</h3>
                                <p className="settings-page__card-subtitle">
                                    In the UniFi dashboard, set Guest Control → External Portal Server to this URL,
                                    and add the domain to the pre-authorization allowlist:
                                </p>
                                <code className="block rounded-lg bg-black/5 px-3 py-2 text-sm break-all">{portal_url}</code>
                            </div>
                        </GlassCard>
                    </form>
                </MainColumn>
            </TwoColumnLayout>
        </PageShell>
    );
}
