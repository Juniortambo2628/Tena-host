import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { PencilLine, Trash2, Users } from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import GlassModal from '@/Components/GlassModal';
import EventForm from '@/Components/Events/EventForm';
import { notify } from '@/Components/Toast';
import { useConfirm } from '@/hooks/useConfirm';
import { STATUS_LABEL, STATUS_COLORS, dateRange } from '@/Components/Events/eventFormat';
import '../Events/Index.css';

/** One event: when and where, its question and sponsor, and who came. */
export default function EventShow({ event, attendees, stats, venues }) {
    const [editing, setEditing] = useState(false);
    const { confirm, ConfirmDialogEl } = useConfirm();

    const remove = async () => {
        const ok = await confirm({
            title: 'Delete event',
            message: `Delete "${event.name}"? Its attendee list goes too; the people stay on your list.`,
            confirmLabel: 'Delete',
            variant: 'danger',
        });
        if (ok) router.delete(route('host.events.destroy', event.id), { onSuccess: () => notify.success('Event deleted') });
    };

    const columns = [
        {
            id: 'name',
            header: 'Attendee',
            accessorFn: (a) => a.name,
            cell: ({ row: { original: a } }) => (
                <span className="event-page__attendee"><b>{a.name || 'No name'}</b><small>{a.contact}</small></span>
            ),
        },
        { id: 'type', header: 'New or returning', accessorFn: (a) => (a.returning ? 'Returning' : 'New') },
        { id: 'days', header: 'Days', accessorFn: (a) => a.days },
        ...(event.question ? [{ id: 'answer', header: event.question, accessorFn: (a) => a.answer || '—' }] : []),
        { id: 'opted', header: 'Offers', accessorFn: (a) => (a.opted_in ? 'Opted in' : '—') },
    ];

    return (
        <>
            <Head title={event.name} />
            <DashboardHero
                title={event.name}
                subtitle={`${dateRange(event.starts_on, event.ends_on)} · ${event.venue}`}
                breadcrumbs={[{ label: 'Events', href: route('host.events.index') }, { label: event.name }]}
                rootRoute="host.dashboard"
                actions={[
                    { label: 'Edit', icon: <PencilLine size={16} />, onClick: () => setEditing(true) },
                    { label: 'Delete', icon: <Trash2 size={16} />, onClick: remove },
                ]}
                stats={[
                    { label: 'Attendees', value: stats.attendees },
                    { label: 'Returning', value: stats.returning },
                    { label: 'Opted in to offers', value: stats.optedIn },
                    ...(event.question ? [{ label: 'Answered the question', value: stats.answered }] : []),
                ]}
            />

            <GlassCard padding="p-6">
                <div className="event-page__details">
                    <div className="event-page__detail"><span>Status</span><StatusBadge status={STATUS_LABEL[event.status]} colors={STATUS_COLORS} className="self-start" /></div>
                    <div className="event-page__detail"><span>Extra question</span><b>{event.question || 'None'}</b></div>
                    <div className="event-page__detail"><span>Sponsor</span><b>{event.sponsor?.name || 'None'}</b></div>
                    <div className="event-page__detail"><span>Sponsor message</span><b>{event.sponsor?.message || '—'}</b></div>
                </div>
                <p className="event-page__note">While the event runs, everyone who signs in to the venue’s WiFi is added to this list.</p>
            </GlassCard>

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    title="Who came"
                    data={attendees.data}
                    columns={columns}
                    serverPagination={attendees}
                    emptyIcon={Users}
                    emptyMessage={event.status === 'upcoming' ? 'Attendees appear here once the event starts' : 'No attendees yet'}
                />
            </GlassCard>

            <GlassModal isOpen={editing} onClose={() => setEditing(false)} title="Edit event" maxWidth="lg">
                <EventForm event={event} venues={venues} onDone={() => { setEditing(false); notify.success('Event saved'); }} onCancel={() => setEditing(false)} />
            </GlassModal>

            {ConfirmDialogEl}
        </>
    );
}
