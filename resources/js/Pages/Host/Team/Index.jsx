import React, { useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { UserPlus, PencilLine, Trash2, UserCog } from 'lucide-react';
import { notify } from '@/Components/Toast';
import { useConfirm } from '@/hooks/useConfirm';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import PillButton from '@/Components/Dashboard/PillButton';
import GlassModal from '@/Components/GlassModal';
import { FormField, TextInput, FormActions } from '@/Components/Forms/FormPrimitives';
import './Index.css';

const cap = (word = '') => word.charAt(0).toUpperCase() + word.slice(1);

const ROLES = [
    { value: 'manager', label: 'Manager', description: 'Uses the dashboard for their {places}: {people}, WiFi pages and access points. Billing and marketing stay with you.' },
    { value: 'staff', label: 'Staff', description: 'A simple front-desk view of their {places}.' },
];

/**
 * The owner's team: each person gets their own login and only the sites
 * they're given (TeamController).
 */
export default function TeamIndex({ members, sites }) {
    const { auth } = usePage().props;
    const words = auth.account || { place: 'property', places: 'properties', people: 'guests' };
    const fill = (text) => text.replace('{places}', words.places).replace('{people}', words.people);
    const { confirm, ConfirmDialogEl } = useConfirm();
    const [editing, setEditing] = useState(null);
    const [open, setOpen] = useState(false);
    const siteName = useMemo(() => Object.fromEntries(sites.map((s) => [s.id, s.name])), [sites]);

    const form = useForm({ first_name: '', last_name: '', email: '', phone: '', team_role: 'manager', sites: [] });

    const openInvite = () => {
        setEditing(null);
        form.setData({ first_name: '', last_name: '', email: '', phone: '', team_role: 'manager', sites: sites.length === 1 ? [sites[0].id] : [] });
        form.clearErrors();
        setOpen(true);
    };
    const openEdit = (member) => {
        setEditing(member);
        form.setData({ first_name: '', last_name: '', email: '', phone: '', team_role: member.team_role, sites: member.sites });
        form.clearErrors();
        setOpen(true);
    };
    const toggleSite = (id) => form.setData('sites', form.data.sites.includes(id) ? form.data.sites.filter((s) => s !== id) : [...form.data.sites, id]);

    const submit = (e) => {
        e.preventDefault();
        const done = { preserveScroll: true, onSuccess: (page) => { const f = page.props.flash || {}; f.error ? notify.error(f.error) : notify.success(f.success || 'Saved'); setOpen(false); } };
        if (editing) {
            form.transform((d) => ({ team_role: d.team_role, sites: d.sites }));
            form.put(route('host.team.update', editing.id), done);
        } else {
            form.transform((d) => d);
            form.post(route('host.team.store'), done);
        }
    };

    const remove = async (member) => {
        const ok = await confirm({
            title: 'Remove from team',
            message: `Remove ${member.name}? Their login stops working straight away.`,
            confirmLabel: 'Remove',
            variant: 'danger',
        });
        if (ok) router.delete(route('host.team.destroy', member.id), { preserveScroll: true, onSuccess: () => notify.success(`${member.name} removed`) });
    };

    const columns = [
        {
            id: 'name',
            header: 'Name',
            accessorFn: (m) => m.name,
            cell: ({ row: { original: m } }) => (
                <div className="team-page__person">
                    <b>{m.name}</b>
                    <small>{m.email || m.phone}</small>
                </div>
            ),
        },
        {
            id: 'role',
            header: 'Role',
            accessorFn: (m) => m.team_role,
            cell: ({ row: { original: m } }) => <span className="team-page__role">{cap(m.team_role)}</span>,
        },
        {
            id: 'sites',
            header: cap(words.places),
            accessorFn: (m) => m.sites.map((id) => siteName[id]).join(', '),
            cell: ({ row: { original: m } }) => <span className="team-page__sites">{m.sites.map((id) => siteName[id]).filter(Boolean).join(', ') || '—'}</span>,
        },
        {
            id: 'status',
            header: 'Status',
            accessorFn: (m) => (m.joined ? 'active' : 'pending'),
            cell: ({ row: { original: m } }) => <StatusBadge status={m.joined ? 'Active' : 'Pending'} colors={{ active: 'bg-green-100 text-green-700', pending: 'bg-amber-100 text-amber-700' }} />,
        },
    ];

    return (
        <>
            <Head title="Team" />
            <DashboardHero
                title="Team"
                subtitle={`Invite managers and staff. Each gets their own login and only the ${words.places} you choose.`}
                breadcrumbs={[{ label: 'Team' }]}
                rootRoute="host.dashboard"
                actions={sites.length ? [{ label: 'Invite someone', icon: <UserPlus size={16} />, onClick: openInvite }] : []}
            />

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={members}
                    columns={columns}
                    searchPlaceholder="Search your team..."
                    emptyIcon={UserCog}
                    emptyMessage={sites.length ? 'No one on your team yet' : `Add your first ${words.place} before inviting a team`}
                    emptyAction={sites.length ? { label: 'Invite someone', icon: <UserPlus size={16} />, onClick: openInvite } : null}
                    rowActions={[
                        { key: 'edit', label: 'Change access', icon: <PencilLine size={14} />, onClick: openEdit },
                        { key: 'remove', label: 'Remove', icon: <Trash2 size={14} />, variant: 'danger', onClick: remove },
                    ]}
                />
            </GlassCard>

            <GlassModal isOpen={open} onClose={() => setOpen(false)} title={editing ? `${editing.name}'s access` : 'Invite someone'} maxWidth="lg">
                <form onSubmit={submit} className="team-page__form">
                    {!editing && (
                        <>
                            <div className="team-page__row">
                                <FormField label="First name" required error={form.errors.first_name}>
                                    <TextInput id="team-first-name" value={form.data.first_name} onChange={(e) => form.setData('first_name', e.target.value)} required autoComplete="off" />
                                </FormField>
                                <FormField label="Last name" error={form.errors.last_name}>
                                    <TextInput id="team-last-name" value={form.data.last_name} onChange={(e) => form.setData('last_name', e.target.value)} autoComplete="off" />
                                </FormField>
                            </div>
                            <FormField label="Email" hint="The invite goes here and by WhatsApp. Add at least one." error={form.errors.email}>
                                <TextInput id="team-email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} autoComplete="off" />
                            </FormField>
                            <FormField label="WhatsApp number" error={form.errors.phone}>
                                <TextInput id="team-phone" type="tel" placeholder="07XX XXX XXX" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} autoComplete="off" />
                            </FormField>
                        </>
                    )}

                    <fieldset className="team-page__choice">
                        <legend>Role</legend>
                        {ROLES.map((role) => (
                            <label key={role.value} className={`team-page__option ${form.data.team_role === role.value ? 'team-page__option--on' : ''}`}>
                                <input type="radio" name="team_role" value={role.value} checked={form.data.team_role === role.value} onChange={() => form.setData('team_role', role.value)} />
                                <span><b>{role.label}</b><small>{fill(role.description)}</small></span>
                            </label>
                        ))}
                    </fieldset>

                    <fieldset className="team-page__choice">
                        <legend>{cap(words.places)} they can see</legend>
                        {sites.map((site) => (
                            <label key={site.id} className="team-page__site">
                                <input type="checkbox" checked={form.data.sites.includes(site.id)} onChange={() => toggleSite(site.id)} />
                                <span>{site.name}{site.region && <small> · {site.region}</small>}</span>
                            </label>
                        ))}
                        {(form.errors.sites || form.errors['sites.0']) && <p className="team-page__error">{form.errors.sites || form.errors['sites.0']}</p>}
                    </fieldset>

                    <FormActions>
                        <PillButton type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</PillButton>
                        <PillButton type="submit" disabled={form.processing}>{editing ? 'Save access' : 'Send invite'}</PillButton>
                    </FormActions>
                </form>
            </GlassModal>

            {ConfirmDialogEl}
        </>
    );
}
