import React, { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { CalendarPlus, CalendarDays } from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import ViewSwitcher from '@/Components/Dashboard/ViewSwitcher';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import GlassModal from '@/Components/GlassModal';
import EventForm from '@/Components/Events/EventForm';
import { STATUS_LABEL, STATUS_COLORS, dateRange } from '@/Components/Events/eventFormat';
import './Index.css';

const VIEWS = [
    { id: 'all', label: 'All' },
    { id: 'live', label: 'Live now' },
    { id: 'upcoming', label: 'Upcoming' },
    { id: 'past', label: 'Past' },
];

/** An event account's events: upcoming, live now and past, with who came. */
export default function EventsIndex({ events, venues }) {
    const [view, setView] = useState(events.some((e) => e.status === 'live') ? 'live' : 'all');
    const [creating, setCreating] = useState(false);
    const shown = useMemo(() => (view === 'all' ? events : events.filter((e) => e.status === view)), [events, view]);
    const count = (status) => events.filter((e) => e.status === status).length;

    const columns = [
        {
            id: 'name',
            header: 'Event',
            accessorFn: (e) => e.name,
            cell: ({ row: { original: e } }) => (
                <Link href={route('host.events.show', e.id)} className="events-page__event">
                    <b>{e.name}</b>
                    <small>{e.venue}</small>
                </Link>
            ),
        },
        { id: 'dates', header: 'Dates', accessorFn: (e) => e.starts_on, cell: ({ row: { original: e } }) => dateRange(e.starts_on, e.ends_on) },
        { id: 'attendees', header: 'Attendees', accessorFn: (e) => e.attendees },
        {
            id: 'status',
            header: 'Status',
            accessorFn: (e) => e.status,
            cell: ({ row: { original: e } }) => <StatusBadge status={STATUS_LABEL[e.status]} colors={STATUS_COLORS} />,
        },
    ];

    const create = venues.length ? { label: 'New event', icon: <CalendarPlus size={16} />, onClick: () => setCreating(true) } : null;

    return (
        <>
            <Head title="Events" />
            <DashboardHero
                title="Events"
                subtitle="Each event gets its own sign-in while it runs, and its attendee list."
                breadcrumbs={[{ label: 'Events' }]}
                rootRoute="host.dashboard"
                actions={create ? [{ ...create, variant: 'primary' }] : []}
                stats={[
                    { label: 'Live now', value: count('live') },
                    { label: 'Upcoming', value: count('upcoming') },
                    { label: 'Attendees', value: events.reduce((sum, e) => sum + e.attendees, 0).toLocaleString() },
                ]}
            />

            {events.length > 0 && (
                <ViewSwitcher label="Event status" views={VIEWS.map((v) => ({ ...v, label: v.id === 'all' ? `All (${events.length})` : `${v.label} (${count(v.id)})` }))} value={view} onChange={setView} />
            )}

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={shown}
                    columns={columns}
                    searchPlaceholder="Search events..."
                    emptyIcon={CalendarDays}
                    emptyMessage={venues.length ? (events.length ? 'No events here' : 'No events yet') : 'Add a venue before creating events'}
                    emptyAction={events.length ? null : (create || { label: 'Add a venue', href: route('host.properties.index') })}
                />
            </GlassCard>

            <GlassModal isOpen={creating} onClose={() => setCreating(false)} title="New event" maxWidth="lg">
                <EventForm venues={venues} onDone={() => setCreating(false)} onCancel={() => setCreating(false)} />
            </GlassModal>
        </>
    );
}
