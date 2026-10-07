import React from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import { TwoColumnLayout, MainColumn } from '@/Layouts/LayoutPrimitives';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import { FormField, TextInput, Select, CheckboxField, FormActions } from '@/Components/Forms/FormPrimitives';
import { Smartphone, AlertCircle, CheckCircle2, Wallet } from 'lucide-react';
import { notify } from '@/Components/Toast';

const kes = (n) => `KES ${Number(n || 0).toLocaleString('en-KE')}`;

/**
 * TenaFi's M-Pesa paybill: used for host subscriptions and guest extras.
 * Secrets are write-only (blank keeps the stored value).
 */
export default function Mpesa({ settings, configured, isPlaceholder, callbacks, payouts = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        env: settings.env || 'sandbox',
        account_type: settings.account_type || 'paybill',
        shortcode: settings.shortcode || '',
        consumer_key: settings.consumer_key || '',
        consumer_secret: '',
        passkey: '',
        extras_enabled: settings.extras_enabled ?? true,
        extras_fee_percent: settings.extras_fee_percent ?? 0,
    });

    const save = () => post(route('admin.mpesa.update'), {
        preserveScroll: true,
        onSuccess: () => notify.success('M-Pesa settings saved.'),
        onError: () => notify.error('Could not save settings. Check the fields.'),
    });

    const settle = (payout) => {
        if (!window.confirm(`Mark ${kes(payout.owed)} to ${payout.host} as paid out?`)) return;
        router.post(route('admin.mpesa.settle', payout.host_id), {}, { preserveScroll: true });
    };

    const status = configured
        ? { ok: true, text: `Live on ${data.env === 'production' ? 'production' : 'the sandbox'}: payments go to ${data.account_type} ${settings.shortcode}.` }
        : { ok: false, text: isPlaceholder
            ? "Placeholders only (Safaricom's sandbox paybill). Add TenaFi's paybill and Daraja API keys to take payments. Until then, guest extras are paid at the property."
            : 'Add the Daraja consumer key and secret to start taking payments.' };

    return (
        <PageShell
            title="M-Pesa"
            subtitle="TenaFi's paybill for host subscriptions and guest extras"
            headTitle="M-Pesa"
            breadcrumbs={[{ label: 'M-Pesa', href: route('admin.mpesa.edit') }]}
            rootRoute="admin.dashboard"
        >
            <Head title="M-Pesa" />
            <TwoColumnLayout>
                <MainColumn span={12}>
                    <form onSubmit={(e) => e.preventDefault()} className="space-y-6">
                        <GlassCard padding="p-8">
                            <div className="space-y-6">
                                <div className="flex items-center gap-3">
                                    <div className="settings-page__card-icon"><Smartphone size={24} /></div>
                                    <div>
                                        <h3 className="settings-page__card-title">Lipa na M-Pesa</h3>
                                        <p className="settings-page__card-subtitle">From the Safaricom Daraja portal (developer.safaricom.co.ke).</p>
                                    </div>
                                </div>

                                <div
                                    className="flex items-start gap-2 rounded-xl px-4 py-3 text-sm"
                                    style={{ background: status.ok ? '#dcfce7' : '#fef9c3', color: status.ok ? '#166534' : '#854d0e' }}
                                >
                                    {status.ok ? <CheckCircle2 size={18} /> : <AlertCircle size={18} />}
                                    <span>{status.text}</span>
                                </div>

                                <FormField label="Environment" error={errors.env}>
                                    <Select value={data.env} onChange={(e) => setData('env', e.target.value)}>
                                        <option value="sandbox">Sandbox (testing)</option>
                                        <option value="production">Production (real money)</option>
                                    </Select>
                                </FormField>

                                <FormField label="Account type" error={errors.account_type}>
                                    <Select value={data.account_type} onChange={(e) => setData('account_type', e.target.value)}>
                                        <option value="paybill">Paybill</option>
                                        <option value="till">Till (Buy Goods)</option>
                                    </Select>
                                </FormField>

                                <FormField label={data.account_type === 'till' ? 'Till number' : 'Paybill number'} error={errors.shortcode}>
                                    <TextInput value={data.shortcode} onChange={(e) => setData('shortcode', e.target.value)} placeholder="174379" />
                                </FormField>

                                <FormField label="Consumer key" error={errors.consumer_key}>
                                    <TextInput value={data.consumer_key} onChange={(e) => setData('consumer_key', e.target.value)} autoComplete="off" />
                                </FormField>

                                <FormField label="Consumer secret" error={errors.consumer_secret}>
                                    <TextInput
                                        type="password"
                                        value={data.consumer_secret}
                                        onChange={(e) => setData('consumer_secret', e.target.value)}
                                        autoComplete="new-password"
                                        placeholder={settings.has_secret ? '•••••••• (leave blank to keep current)' : 'Daraja consumer secret'}
                                    />
                                </FormField>

                                <FormField label="Lipa na M-Pesa passkey" error={errors.passkey}>
                                    <TextInput
                                        type="password"
                                        value={data.passkey}
                                        onChange={(e) => setData('passkey', e.target.value)}
                                        autoComplete="new-password"
                                        placeholder={settings.has_custom_passkey ? '•••••••• (leave blank to keep current)' : 'Sandbox passkey in use'}
                                    />
                                </FormField>

                                <CheckboxField
                                    label="Guests pay for extras (late checkout, cleaning, welcome packs) by M-Pesa"
                                    checked={data.extras_enabled}
                                    onChange={() => setData('extras_enabled', !data.extras_enabled)}
                                />

                                <FormField label="TenaFi fee on extras (%)" error={errors.extras_fee_percent}>
                                    <TextInput type="number" min="0" max="50" step="0.5" value={data.extras_fee_percent}
                                        onChange={(e) => setData('extras_fee_percent', e.target.value)} />
                                </FormField>

                                <FormActions>
                                    <PillButton variant="primary" onClick={save} disabled={processing}>
                                        {processing ? 'Saving…' : 'Save settings'}
                                    </PillButton>
                                </FormActions>
                            </div>
                        </GlassCard>

                        <GlassCard padding="p-6">
                            <div className="space-y-2">
                                <h3 className="settings-page__card-title">Callback URLs</h3>
                                <p className="settings-page__card-subtitle">TenaFi sends these with every payment request; the server must be reachable from the internet.</p>
                                <p className="text-xs font-bold text-black/50">Subscriptions</p>
                                <code className="block rounded-lg bg-black/5 px-3 py-2 text-sm break-all">{callbacks.subscriptions}</code>
                                <p className="text-xs font-bold text-black/50">Guest extras</p>
                                <code className="block rounded-lg bg-black/5 px-3 py-2 text-sm break-all">{callbacks.extras}</code>
                            </div>
                        </GlassCard>

                        <GlassCard padding="p-6">
                            <div className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <div className="settings-page__card-icon"><Wallet size={20} /></div>
                                    <div>
                                        <h3 className="settings-page__card-title">Host payouts</h3>
                                        <p className="settings-page__card-subtitle">Extras guests paid on TenaFi's paybill, less TenaFi's fee. Send the money, then mark it paid out.</p>
                                    </div>
                                </div>
                                {payouts.length === 0 ? (
                                    <p className="text-sm text-black/50">Nothing owed right now.</p>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="text-left text-xs text-black/50">
                                                    <th className="py-2">Host</th><th>Orders</th><th>Paid by guests</th><th>TenaFi fee</th><th>Owed</th><th />
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {payouts.map((p) => (
                                                    <tr key={p.host_id} className="border-t border-black/5">
                                                        <td className="py-3 font-bold">{p.host}<span className="block text-xs font-normal text-black/50">{p.phone}</span></td>
                                                        <td>{p.orders}</td>
                                                        <td>{kes(p.gross)}</td>
                                                        <td>{kes(p.fee)}</td>
                                                        <td className="font-black">{kes(p.owed)}</td>
                                                        <td className="text-right">
                                                            <PillButton variant="secondary" onClick={() => settle(p)}>Mark paid out</PillButton>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        </GlassCard>
                    </form>
                </MainColumn>
            </TwoColumnLayout>
        </PageShell>
    );
}
