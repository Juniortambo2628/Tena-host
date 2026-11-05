import React, { useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Receipt, BadgeDollarSign } from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import ViewSwitcher from '@/Components/Dashboard/ViewSwitcher';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import PillButton from '@/Components/Dashboard/PillButton';
import GlassModal from '@/Components/GlassModal';
import { FormField, TextInput, FormActions } from '@/Components/Forms/FormPrimitives';
import { notify } from '@/Components/Toast';
import { dateRange } from '@/Components/Events/eventFormat';
import './Index.css';

const LABEL = { requested: 'To price', quoted: 'Quoted', paid: 'Paid' };
const COLORS = { 'to price': 'bg-primary/20 text-dark', quoted: 'bg-amber-100 text-amber-700', paid: 'bg-green-100 text-green-700' };
const kes = (n) => (n ? `KES ${Number(n).toLocaleString()}` : '—');

/** Pay per event: organisers' quote requests, the price set for each, and payments. */
export default function EventQuotes({ events, enabled }) {
    const count = (s) => events.filter((e) => e.status === s).length;
    const [view, setView] = useState(count('requested') ? 'requested' : 'all');
    const [pricing, setPricing] = useState(null);
    const shown = useMemo(() => (view === 'all' ? events : events.filter((e) => e.status === view)), [events, view]);
    const form = useForm({ quote_amount: '', quote_note: '' });

    const openPricing = (e) => {
        form.setData({ quote_amount: e.amount || '', quote_note: e.quote_note || '' });
        form.clearErrors();
        setPricing(e);
    };
    const submit = (ev) => {
        ev.preventDefault();
        form.put(route('admin.event-quotes.update', pricing.id), {
            preserveScroll: true,
            onSuccess: () => { notify.success('Quote sent to the organiser'); setPricing(null); },
        });
    };

    const columns = [
        {
            id: 'name',
            header: 'Event',
            accessorFn: (e) => e.name,
            cell: ({ row: { original: e } }) => (
                <span className="event-quotes__event"><b>{e.name}</b><small>{e.venue} · {dateRange(e.starts_on, e.ends_on)}</small></span>
            ),
        },
        {
            id: 'organiser',
            header: 'Organiser',
            accessorFn: (e) => e.organiser,
            cell: ({ row: { original: e } }) => <span className="event-quotes__event"><b>{e.organiser}</b><small>{e.contact}</small></span>,
        },
        { id: 'attendees', header: 'Expected', accessorFn: (e) => e.expected_attendees, cell: ({ row: { original: e } }) => (e.expected_attendees || 0).toLocaleString() },
        { id: 'note', header: 'Their note', accessorFn: (e) => e.request_note || '—', meta: { detailOnly: true } },
        { id: 'amount', header: 'Price', accessorFn: (e) => e.amount || 0, cell: ({ row: { original: e } }) => kes(e.amount) },
        { id: 'status', header: 'Status', accessorFn: (e) => e.status, cell: ({ row: { original: e } }) => <StatusBadge status={LABEL[e.status]} colors={COLORS} /> },
        {
            id: 'price',
            header: '',
            enableSorting: false,
            cell: ({ row: { original: e } }) => (e.status === 'paid' ? null : (
                <PillButton variant={e.status === 'requested' ? 'primary' : 'ghost'} onClick={() => openPricing(e)} className="whitespace-nowrap">
                    {e.status === 'requested' ? 'Set price' : 'Change price'}
                </PillButton>
            )),
        },
    ];

    return (
        <>
            <Head title="Event quotes" />
            <DashboardHero
                title="Event quotes"
                subtitle={enabled ? 'Organisers without a plan ask for a price per event. Set it here; they pay by M-Pesa or card from the event page.' : 'Pay per event is switched off (Admin → Settings). Existing quotes are listed here.'}
                breadcrumbs={[{ label: 'Event quotes' }]}
                rootRoute="admin.dashboard"
                stats={[
                    { label: 'To price', value: count('requested') },
                    { label: 'Awaiting payment', value: count('quoted') },
                    { label: 'Paid', value: count('paid') },
                ]}
            />

            {events.length > 0 && (
                <ViewSwitcher
                    label="Quote status"
                    views={[
                        { id: 'requested', label: `To price (${count('requested')})` },
                        { id: 'quoted', label: `Quoted (${count('quoted')})` },
                        { id: 'paid', label: `Paid (${count('paid')})` },
                        { id: 'all', label: `All (${events.length})` },
                    ]}
                    value={view}
                    onChange={setView}
                />
            )}

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={shown}
                    columns={columns}
                    searchPlaceholder="Search events or organisers..."
                    detailTitle="Quote request"
                    emptyIcon={Receipt}
                    emptyMessage={events.length ? 'Nothing here' : 'No quote requests yet'}
                />
            </GlassCard>

            <GlassModal isOpen={!!pricing} onClose={() => setPricing(null)} title={pricing ? `Price ${pricing.name}` : 'Price'} maxWidth="md">
                {pricing && (
                    <form onSubmit={submit} className="event-quotes__form">
                        <p className="event-quotes__summary">
                            {pricing.venue} · {dateRange(pricing.starts_on, pricing.ends_on)} · about {(pricing.expected_attendees || 0).toLocaleString()} attendees
                            {pricing.request_note && <><br />“{pricing.request_note}”</>}
                        </p>
                        <FormField label="Price (KES)" required error={form.errors.quote_amount}>
                            <TextInput id="quote-amount" type="number" value={form.data.quote_amount} onChange={(e) => form.setData('quote_amount', e.target.value)} placeholder="e.g. 15000" required />
                        </FormField>
                        <FormField label="Note to the organiser (optional)" hint="Shown with the price, e.g. what it covers." error={form.errors.quote_note}>
                            <TextInput id="quote-note" value={form.data.quote_note} onChange={(e) => form.setData('quote_note', e.target.value)} />
                        </FormField>
                        <FormActions>
                            <PillButton variant="secondary" onClick={() => setPricing(null)}>Cancel</PillButton>
                            <PillButton variant="primary" type="submit" disabled={form.processing} icon={<BadgeDollarSign size={16} />}>Send quote</PillButton>
                        </FormActions>
                    </form>
                )}
            </GlassModal>
        </>
    );
}
