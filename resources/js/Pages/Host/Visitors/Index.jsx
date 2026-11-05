import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { UserPlus, DoorOpen, LogOut } from 'lucide-react';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import PillButton from '@/Components/Dashboard/PillButton';
import GlassModal from '@/Components/GlassModal';
import { FormField, TextInput, Select, FormActions } from '@/Components/Forms/FormPrimitives';
import { notify } from '@/Components/Toast';
import './Index.css';

const time = (iso) => (iso ? new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—');

/** Reception's visitor log: a day's visits at the sites that keep one, walk-ins and sign-outs. */
export default function VisitorsIndex({ visits, sites, filters, isToday }) {
    const [adding, setAdding] = useState(false);
    const form = useForm({ property_id: sites.length === 1 ? sites[0].id : '', name: '', contact: '', visiting: '' });
    const stillIn = visits.filter((v) => !v.left_at).length;

    const filter = (changes) => router.get(route('host.visitors.index'), { ...filters, ...changes }, { preserveState: true, preserveScroll: true, replace: true });

    const signOut = (v) => router.patch(route('host.visitors.leave', v.id), {}, {
        preserveScroll: true,
        onSuccess: () => notify.success(`${v.name} signed out`),
    });

    const addWalkIn = (e) => {
        e.preventDefault();
        form.post(route('host.visitors.store'), {
            preserveScroll: true,
            onSuccess: () => { notify.success(`${form.data.name} signed in`); form.reset('name', 'contact', 'visiting'); setAdding(false); },
        });
    };

    const columns = [
        {
            id: 'name',
            header: 'Visitor',
            accessorFn: (v) => v.name,
            cell: ({ row: { original: v } }) => (
                <span className="visitors-page__who"><b>{v.name}</b><small>{v.contact || (v.walk_in ? 'Walk-in' : '—')}</small></span>
            ),
        },
        { id: 'visiting', header: 'Here to see', accessorFn: (v) => v.visiting },
        ...(sites.length > 1 ? [{ id: 'site', header: 'Site', accessorFn: (v) => v.site }] : []),
        { id: 'in', header: 'In', accessorFn: (v) => v.arrived_at, cell: ({ row: { original: v } }) => time(v.arrived_at) },
        {
            id: 'out',
            header: 'Out',
            accessorFn: (v) => v.left_at || '',
            cell: ({ row: { original: v } }) => (v.left_at
                ? time(v.left_at)
                : <PillButton variant="ghost" icon={<LogOut size={14} />} onClick={() => signOut(v)} className="visitors-page__out">Sign out</PillButton>),
        },
    ];

    return (
        <>
            <Head title="Visitors" />
            <DashboardHero
                title="Visitors"
                subtitle="Everyone who signed in at reception or on the WiFi, who they came to see, and when they left."
                breadcrumbs={[{ label: 'Visitors' }]}
                rootRoute="host.dashboard"
                actions={sites.length ? [{ label: 'Add a walk-in', icon: <UserPlus size={16} />, onClick: () => setAdding(true), variant: 'primary' }] : []}
                stats={[
                    { label: isToday ? 'Visitors today' : 'Visitors', value: visits.length },
                    { label: isToday ? 'Still here' : 'Not signed out', value: stillIn },
                ]}
            />

            {sites.length > 0 && (
                <div className="visitors-page__filters">
                    <label>
                        <span>Day</span>
                        <input type="date" value={filters.date} max={new Date().toISOString().slice(0, 10)} onChange={(e) => e.target.value && filter({ date: e.target.value })} />
                    </label>
                    {sites.length > 1 && (
                        <label>
                            <span>Site</span>
                            <select value={filters.site || ''} onChange={(e) => filter({ site: e.target.value || null })}>
                                <option value="">All sites</option>
                                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </label>
                    )}
                    {!isToday && <PillButton variant="ghost" onClick={() => filter({ date: null })}>Back to today</PillButton>}
                </div>
            )}

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={visits}
                    columns={columns}
                    searchPlaceholder="Search visitors..."
                    emptyIcon={DoorOpen}
                    emptyMessage={sites.length ? (isToday ? 'No visitors yet today' : 'No visitors that day') : 'No site keeps a visitor log yet'}
                    emptyAction={sites.length ? null : { label: 'Turn it on for a site', href: route('host.properties.index') }}
                />
            </GlassCard>

            <GlassModal isOpen={adding} onClose={() => setAdding(false)} title="Add a walk-in" maxWidth="md">
                <form onSubmit={addWalkIn} className="visitors-page__form">
                    {sites.length > 1 && (
                        <FormField label="Site" required error={form.errors.property_id}>
                            <Select id="visit-site" value={form.data.property_id} onChange={(e) => form.setData('property_id', e.target.value)} required>
                                <option value="">Choose a site</option>
                                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </Select>
                        </FormField>
                    )}
                    <FormField label="Name" required error={form.errors.name || (sites.length === 1 ? form.errors.property_id : null)}>
                        <TextInput id="visit-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                    </FormField>
                    <FormField label="Phone or email (optional)" error={form.errors.contact}>
                        <TextInput id="visit-contact" value={form.data.contact} onChange={(e) => form.setData('contact', e.target.value)} />
                    </FormField>
                    <FormField label="Here to see" required error={form.errors.visiting}>
                        <TextInput id="visit-visiting" value={form.data.visiting} onChange={(e) => form.setData('visiting', e.target.value)} placeholder="Name or department" required />
                    </FormField>
                    <FormActions>
                        <PillButton variant="secondary" onClick={() => setAdding(false)}>Cancel</PillButton>
                        <PillButton variant="primary" type="submit" disabled={form.processing}>Sign in</PillButton>
                    </FormActions>
                </form>
            </GlassModal>
        </>
    );
}
