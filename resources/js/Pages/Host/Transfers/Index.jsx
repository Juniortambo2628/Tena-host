import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeftRight, Check, X, Send } from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import PillButton from '@/Components/Dashboard/PillButton';
import EmptyState from '@/Components/Dashboard/EmptyState';
import { FormField, TextInput, Select, FormActions, CheckboxField } from '@/Components/Forms/FormPrimitives';
import { T, useTerms } from '@/Components/Dashboard/Terms';
import { useConfirm } from '@/hooks/useConfirm';
import { notify } from '@/Components/Toast';
import './Index.css';

const STATUS = { pending: 'Waiting', accepted: 'Cooling off', completed: 'Completed', declined: 'Declined', cancelled: 'Cancelled', expired: 'Expired' };
const COLORS = {
    waiting: 'bg-primary/20 text-dark',
    'cooling off': 'bg-amber-100 text-amber-700',
    completed: 'bg-green-100 text-green-700',
    declined: 'bg-black/5 text-muted',
    cancelled: 'bg-black/5 text-muted',
    expired: 'bg-black/5 text-muted',
};
const day = (iso) => (iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
const kes = (n) => `KES ${Number(n || 0).toLocaleString()}`;
const when = (iso) => (iso ? new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—');

/**
 * Properties -> Transfers (docs/plans/property-transfer.md, step 1): hand one
 * of your properties to another TenaFi account, accept or decline one offered
 * to you, and cancel an offer while it waits.
 */
export default function Transfers({ properties, outgoing, incoming, validDays, holdDays, coolingOffHours }) {
    const t = useTerms();
    const { confirm, ConfirmDialogEl } = useConfirm();
    const waiting = incoming.filter((x) => x.status === 'pending');
    const form = useForm({ property_id: properties[0]?.id || '', email: '', password: '', device_takeover: false });
    // Step 4: what's still owed on the chosen property's devices, and the offers whose payments the recipient agreed to take over.
    const chosen = properties.find((p) => String(p.id) === String(form.data.property_id));
    const debt = chosen?.devices?.count > 0 ? chosen.devices : null;
    const [agreed, setAgreed] = useState({});

    const start = async (e) => {
        e.preventDefault();
        const property = properties.find((p) => String(p.id) === String(form.data.property_id));
        const ok = await confirm({
            title: 'Transfer this property?',
            message: `${property?.name} will go to ${form.data.email || 'the new owner'} with its WiFi page, devices and ${t('guest').toLowerCase()} list once they accept. You can cancel until then. After that it's theirs.`,
            confirmLabel: 'Offer transfer',
            variant: 'warning',
        });
        if (!ok) return;
        form.transform((data) => ({ ...data, device_takeover: Boolean(debt) && data.device_takeover })).post(route('host.transfers.store'), {
            preserveScroll: true,
            onSuccess: () => { notify.success('Transfer offered'); form.reset('email', 'password'); },
            onError: () => form.reset('password'),
        });
    };

    const accept = async (x) => {
        const ok = await confirm({
            title: `Accept ${x.property}?`,
            message: `It becomes yours ${coolingOffHours} hours after you accept (${x.from} can still cancel until then), with its WiFi page, devices and ${t('guest').toLowerCase()} list. Its ${t('guests').toLowerCase()} then get a short note saying you run it now, and you can send them campaigns after ${holdDays} days, so they have time to opt out.`,
            confirmLabel: 'Accept',
            variant: 'success',
        });
        if (ok) router.post(route('host.transfers.accept', x.id), { takeover: Boolean(agreed[x.id]) }, { preserveScroll: true });
    };
    const decline = async (x) => {
        const ok = await confirm({ title: `Decline ${x.property}?`, message: `It stays with ${x.from}.`, confirmLabel: 'Decline', variant: 'danger' });
        if (ok) router.post(route('host.transfers.decline', x.id), {}, { preserveScroll: true });
    };
    const cancel = async (x) => {
        const ok = await confirm({ title: 'Cancel this transfer?', message: `${x.property} stays yours and nothing moves${x.status === 'accepted' ? ', even though they accepted' : ''}.`, confirmLabel: 'Cancel transfer', variant: 'danger' });
        if (ok) router.post(route('host.transfers.cancel', x.id), {}, { preserveScroll: true });
    };

    const history = [
        ...outgoing.map((x) => ({ ...x, direction: 'out' })),
        ...incoming.filter((x) => x.status !== 'pending').map((x) => ({ ...x, direction: 'in' })),
    ].sort((a, b) => b.created_at.localeCompare(a.created_at));

    const columns = [
        {
            id: 'property',
            header: <T>Property</T>,
            accessorFn: (x) => x.property,
            cell: ({ row: { original: x } }) => (
                <span className="transfers__stack">
                    <b>{x.property}</b>
                    <small>{x.direction === 'out' ? `To ${x.to_email}` : `From ${x.from}`}</small>
                </span>
            ),
        },
        { id: 'offered', header: 'Offered', accessorFn: (x) => x.created_at, cell: ({ row: { original: x } }) => day(x.created_at) },
        {
            id: 'status',
            header: 'Status',
            accessorFn: (x) => x.status,
            cell: ({ row: { original: x } }) => (
                <span className="transfers__stack">
                    <StatusBadge status={STATUS[x.status] || x.status} colors={COLORS} />
                    {x.status === 'accepted' && <small>{x.held ? 'Paused by TenaFi' : `Hand-over ${when(x.completes_at)}`}</small>}
                </span>
            ),
        },
        { id: 'until', header: 'Open until', accessorFn: (x) => x.expires_at, meta: { detailOnly: true }, cell: ({ row: { original: x } }) => day(x.expires_at) },
        { id: 'decided', header: 'Answered', accessorFn: (x) => x.decided_at || '', meta: { detailOnly: true }, cell: ({ row: { original: x } }) => day(x.decided_at) },
        {
            id: 'completes',
            header: 'Hand-over',
            accessorFn: (x) => x.completed_at || x.completes_at || '',
            meta: { detailOnly: true },
            cell: ({ row: { original: x } }) => (x.completed_at ? `Done ${day(x.completed_at)}` : x.status === 'accepted' ? (x.held ? 'Paused by TenaFi' : `On ${when(x.completes_at)}`) : '—'),
        },
        {
            id: 'moved',
            header: 'What moved',
            accessorFn: (x) => (x.moved ? `${x.moved.guests} ${t('guests').toLowerCase()}, ${x.moved.access_points} devices, ${x.moved.campaigns} campaigns` : '—'),
            meta: { detailOnly: true },
        },
        {
            id: 'devices',
            header: 'Device payments taken over',
            accessorFn: (x) => (x.devices ? `${x.devices.count} ${x.devices.count === 1 ? 'device' : 'devices'}, ${kes(x.devices.balance)} left, ${kes(x.devices.monthly)} a month` : '—'),
            meta: { detailOnly: true },
        },
        {
            id: 'notice',
            header: `${t('Guests')} told`,
            accessorFn: (x) => (x.notice ? (x.notice.status === 'none' ? 'Nobody to tell' : `${x.notice.sent} of ${x.notice.total}${x.notice.status === 'pending' ? ' so far' : ''}`) : '—'),
            meta: { detailOnly: true },
        },
        {
            id: 'hold',
            header: 'Campaigns to them from',
            accessorFn: (x) => x.hold_until || '',
            meta: { detailOnly: true },
            cell: ({ row: { original: x } }) => day(x.hold_until),
        },
        {
            id: 'action',
            header: '',
            enableSorting: false,
            cell: ({ row: { original: x } }) => (x.direction === 'out' && ['pending', 'accepted'].includes(x.status)
                ? <PillButton variant="ghost" onClick={() => cancel(x)} className="whitespace-nowrap">Cancel</PillButton>
                : null),
        },
    ];

    return (
        <>
            <Head title="Property transfers" />
            <DashboardHero
                title="Transfers"
                subtitle={`Hand a ${t('property').toLowerCase()} to another TenaFi account, or accept one handed to you. Offers stay open for ${validDays} days.`}
                breadcrumbs={[{ label: t('Properties'), href: route('host.properties.index') }, { label: 'Transfers' }]}
                rootRoute="host.dashboard"
            />

            {waiting.length > 0 && (
                <section className="transfers__section">
                    <h2 className="transfers__heading">Waiting for you</h2>
                    {waiting.map((x) => (
                        <GlassCard key={x.id}>
                            <div className="transfers__offer">
                                <div className="transfers__stack">
                                    <b>{x.property}</b>
                                    <small>From {x.from} · open until {day(x.expires_at)}</small>
                                    {x.preview && (
                                        <small>
                                            Comes with {x.preview.guests} {t('guests').toLowerCase()}, {x.preview.access_points} {x.preview.access_points === 1 ? 'device' : 'devices'} and {x.preview.campaigns} campaigns.
                                        </small>
                                    )}
                                    {x.devices && (
                                        <div className="transfers__devices">
                                            <p>
                                                Its TenaFi {x.devices.count === 1 ? 'device is' : 'devices are'} still being paid off: <b>{kes(x.devices.balance)}</b> left,{' '}
                                                {kes(x.devices.monthly)} a month on the same dates. TenaFi checks with you before the hand-over.
                                            </p>
                                            <CheckboxField
                                                label="I agree to take over these device payments"
                                                checked={Boolean(agreed[x.id])}
                                                onChange={() => setAgreed((a) => ({ ...a, [x.id]: !a[x.id] }))}
                                            />
                                        </div>
                                    )}
                                </div>
                                <div className="transfers__buttons">
                                    <PillButton variant="ghost" onClick={() => decline(x)} icon={<X size={16} />}>Decline</PillButton>
                                    <PillButton variant="primary" onClick={() => accept(x)} icon={<Check size={16} />} disabled={Boolean(x.devices) && !agreed[x.id]}>Accept</PillButton>
                                </div>
                            </div>
                        </GlassCard>
                    ))}
                </section>
            )}

            <section className="transfers__section">
                <h2 className="transfers__heading">Transfer a {t('property').toLowerCase()}</h2>
                <GlassCard>
                    {properties.length === 0 ? (
                        <EmptyState icon={ArrowLeftRight} message={`You have no ${t('properties').toLowerCase()} to transfer`} compact />
                    ) : (
                        <form onSubmit={start} className="transfers__form">
                            <p className="transfers__note">
                                Everything goes with it: the WiFi page, the TenaFi devices, the {t('guest').toLowerCase()} list, visits, campaigns and events.
                                Your team members lose access to it. The WiFi keeps working throughout. Its {t('guests').toLowerCase()} get a short note naming the
                                new owner, who can message them after {holdDays} days. The new owner needs a TenaFi account with this email, and accepts from
                                Properties → Transfers.
                            </p>
                            <div className="transfers__row">
                                <FormField label={t('Property')} required error={form.errors.property_id}>
                                    <Select id="transfer-property" value={form.data.property_id} onChange={(e) => form.setData('property_id', e.target.value)} required>
                                        {properties.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                                    </Select>
                                </FormField>
                                <FormField label="New owner’s email" required error={form.errors.email}>
                                    <TextInput id="transfer-email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} autoComplete="off" required />
                                </FormField>
                            </div>
                            {debt && (
                                <div className="transfers__devices">
                                    <p>
                                        A TenaFi {debt.count === 1 ? 'device' : 'devices'} here {debt.count === 1 ? 'is' : 'are'} still being paid off: <b>{kes(debt.balance)}</b> left,{' '}
                                        {kes(debt.monthly)} a month{debt.last_due ? ` until ${day(debt.last_due)}` : ''}. Either pay it off first (TenaFi records the payment), or:
                                    </p>
                                    <CheckboxField
                                        label="The new owner takes over the remaining payments (TenaFi checks with them before the hand-over)"
                                        checked={form.data.device_takeover}
                                        onChange={() => form.setData('device_takeover', !form.data.device_takeover)}
                                    />
                                </div>
                            )}
                            <FormField label="Your password" hint="To confirm it’s you." required error={form.errors.password}>
                                <TextInput id="transfer-password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} autoComplete="current-password" required />
                            </FormField>
                            <FormActions>
                                <PillButton variant="primary" type="submit" disabled={form.processing} icon={<Send size={16} />}>Offer transfer</PillButton>
                            </FormActions>
                        </form>
                    )}
                </GlassCard>
            </section>

            <section className="transfers__section">
                <h2 className="transfers__heading">History</h2>
                <GlassCard padding="p-0 overflow-hidden">
                    <DataTable
                        data={history}
                        columns={columns}
                        searchPlaceholder="Search transfers..."
                        detailTitle="Transfer"
                        emptyIcon={ArrowLeftRight}
                        emptyMessage="No transfers yet"
                    />
                </GlassCard>
            </section>

            {ConfirmDialogEl}
        </>
    );
}
