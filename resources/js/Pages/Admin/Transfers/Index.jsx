import React, { useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeftRight, Pause, Play, X } from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import ViewSwitcher from '@/Components/Dashboard/ViewSwitcher';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import PillButton from '@/Components/Dashboard/PillButton';
import GlassModal from '@/Components/GlassModal';
import { FormField, TextInput, FormActions } from '@/Components/Forms/FormPrimitives';
import { notify } from '@/Components/Toast';
import './Index.css';

const STATUS = { pending: 'Waiting', accepted: 'Cooling off', completed: 'Completed', declined: 'Declined', cancelled: 'Cancelled', expired: 'Expired' };
const COLORS = {
    waiting: 'bg-primary/20 text-dark',
    'cooling off': 'bg-amber-100 text-amber-700',
    held: 'bg-red-100 text-red-700',
    completed: 'bg-green-100 text-green-700',
    declined: 'bg-black/5 text-muted',
    cancelled: 'bg-black/5 text-muted',
    expired: 'bg-black/5 text-muted',
};
const BY = { owner: 'the owner', link: 'the owner’s email link', admin: 'TenaFi', system: 'the system' };
const when = (iso) => (iso ? new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—');

/**
 * Admin -> Transfers (docs/plans/property-transfer.md, step 3): every property
 * transfer. During an accepted transfer's cooling-off, an admin can hold it
 * (it won't complete), release it, or cancel it, with a note both owners see
 * reflected in their emails.
 */
export default function Transfers({ transfers, enabled, coolingOffHours }) {
    const open = transfers.filter((t) => ['pending', 'accepted'].includes(t.status));
    const cooling = transfers.filter((t) => t.status === 'accepted');
    const [view, setView] = useState(open.length ? 'open' : 'all');
    const [acting, setActing] = useState(null); // { transfer, action: 'hold' | 'cancel' }
    const form = useForm({ note: '' });

    const shown = useMemo(() => ({ open, cooling, all: transfers }[view] || transfers), [transfers, view]); // eslint-disable-line react-hooks/exhaustive-deps

    const start = (transfer, action) => { form.setData('note', ''); form.clearErrors(); setActing({ transfer, action }); };
    const submit = (e) => {
        e.preventDefault();
        form.post(route(`admin.transfers.${acting.action}`, acting.transfer.id), {
            preserveScroll: true,
            onSuccess: () => { notify.success(acting.action === 'hold' ? 'Transfer held' : 'Transfer cancelled'); setActing(null); },
        });
    };
    const release = (t) => form.post(route('admin.transfers.release', t.id), { preserveScroll: true, onSuccess: () => notify.success('Transfer released') });

    const columns = [
        {
            id: 'property',
            header: 'Property',
            accessorFn: (t) => `${t.property} ${t.from} ${t.to_email}`,
            cell: ({ row: { original: t } }) => (
                <span className="admin-transfers__stack"><b>{t.property}</b><small>{t.from} → {t.to || t.to_email}</small></span>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            accessorFn: (t) => t.status,
            cell: ({ row: { original: t } }) => <StatusBadge status={t.held ? 'Held' : (STATUS[t.status] || t.status)} colors={COLORS} />,
        },
        {
            id: 'key',
            header: 'When',
            accessorFn: (t) => t.completes_at || t.expires_at,
            cell: ({ row: { original: t } }) => ({
                pending: `Open until ${when(t.expires_at)}`,
                accepted: t.held ? (t.devices ? 'Held: check the new owner for the device payments' : 'Held') : `Completes ${when(t.completes_at)}`,
                completed: `Completed ${when(t.completed_at)}`,
                cancelled: `Cancelled ${when(t.cancelled_at)}${t.cancelled_by ? ` by ${BY[t.cancelled_by] || t.cancelled_by}` : ''}`,
            }[t.status] || when(t.decided_at || t.created_at)),
        },
        { id: 'from_email', header: 'Owner’s email', accessorFn: (t) => t.from_email || '—', meta: { detailOnly: true } },
        { id: 'offered', header: 'Offered', accessorFn: (t) => when(t.created_at), meta: { detailOnly: true } },
        { id: 'ips', header: 'IP addresses', accessorFn: (t) => `Offered from ${t.from_ip || '—'}, answered from ${t.decided_ip || '—'}`, meta: { detailOnly: true } },
        { id: 'note', header: 'Note', accessorFn: (t) => t.admin_note || '—', meta: { detailOnly: true } },
        {
            id: 'devices',
            header: 'Device payments taken over',
            accessorFn: (t) => (t.devices ? `${t.devices.count} ${t.devices.count === 1 ? 'device' : 'devices'}: KES ${Number(t.devices.balance).toLocaleString()} left, KES ${Number(t.devices.monthly).toLocaleString()} a month` : '—'),
            meta: { detailOnly: true },
        },
        {
            id: 'moved',
            header: 'What moved',
            accessorFn: (t) => (t.moved ? `${t.moved.guests} guests, ${t.moved.access_points} devices, ${t.moved.campaigns} campaigns, ${t.moved.events} events` : '—'),
            meta: { detailOnly: true },
        },
        {
            id: 'notice',
            header: 'Guests told',
            accessorFn: (t) => (t.notice ? (t.notice.status === 'none' ? 'Nobody to tell' : `${t.notice.sent} of ${t.notice.total}`) : '—'),
            meta: { detailOnly: true },
        },
        {
            id: 'actions',
            header: '',
            enableSorting: false,
            cell: ({ row: { original: t } }) => (
                <span className="admin-transfers__actions">
                    {t.status === 'accepted' && !t.held && <PillButton variant="ghost" icon={<Pause size={14} />} onClick={() => start(t, 'hold')}>Hold</PillButton>}
                    {t.status === 'accepted' && t.held && <PillButton variant="ghost" icon={<Play size={14} />} onClick={() => release(t)}>Release</PillButton>}
                    {['pending', 'accepted'].includes(t.status) && <PillButton variant="ghost" icon={<X size={14} />} onClick={() => start(t, 'cancel')}>Cancel</PillButton>}
                </span>
            ),
        },
    ];

    return (
        <>
            <Head title="Transfers" />
            <DashboardHero
                title="Transfers"
                subtitle={enabled
                    ? `Properties handed from one account to another. An accepted transfer waits ${coolingOffHours} hours before the hand-over; hold or cancel it here if something looks wrong.`
                    : 'Property transfers are switched off in Admin → Settings. Past transfers are listed here.'}
                breadcrumbs={[{ label: 'Transfers' }]}
                rootRoute="admin.dashboard"
                stats={[
                    { label: 'Under way', value: open.length },
                    { label: 'Cooling off', value: cooling.length },
                    { label: 'Completed', value: transfers.filter((t) => t.status === 'completed').length },
                ]}
            />

            {transfers.length > 0 && (
                <ViewSwitcher
                    label="Transfers"
                    views={[
                        { id: 'open', label: `Under way (${open.length})` },
                        { id: 'cooling', label: `Cooling off (${cooling.length})` },
                        { id: 'all', label: `All (${transfers.length})` },
                    ]}
                    value={view}
                    onChange={setView}
                />
            )}

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={shown}
                    columns={columns}
                    searchPlaceholder="Search properties, owners or emails..."
                    detailTitle="Transfer"
                    emptyIcon={ArrowLeftRight}
                    emptyMessage={transfers.length ? 'Nothing here' : 'No transfers yet'}
                />
            </GlassCard>

            <GlassModal
                isOpen={!!acting}
                onClose={() => setActing(null)}
                title={acting ? `${acting.action === 'hold' ? 'Hold' : 'Cancel'} the transfer of ${acting.transfer.property}` : 'Transfer'}
                maxWidth="md"
            >
                {acting && (
                    <form onSubmit={submit} className="admin-transfers__form">
                        <p className="admin-transfers__summary">
                            {acting.action === 'hold'
                                ? 'It won’t complete until you release it. The owners aren’t told; use this while you check with them.'
                                : 'It stops here and nothing moves. Both owners are told by notification and email.'}
                        </p>
                        <FormField label="Note (kept on the transfer)" error={form.errors.note || form.errors.transfer}>
                            <TextInput id="transfer-note" value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} placeholder="e.g. The owner called: they didn’t offer this" />
                        </FormField>
                        <FormActions>
                            <PillButton variant="secondary" onClick={() => setActing(null)}>Back</PillButton>
                            <PillButton variant="primary" type="submit" disabled={form.processing}>{acting.action === 'hold' ? 'Hold transfer' : 'Cancel transfer'}</PillButton>
                        </FormActions>
                    </form>
                )}
            </GlassModal>
        </>
    );
}
