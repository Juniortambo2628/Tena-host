import React, { useState } from 'react';
import PageShell from '@/Layouts/PageShell';
import { Link, router, useForm } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DataTable from '@/Components/Dashboard/DataTable';
import BulkActions from '@/Components/Dashboard/BulkActions';
import PillButton from '@/Components/Dashboard/PillButton';
import GlassModal from '@/Components/GlassModal';
import { FormField, TextInput, FormActions } from '@/Components/Forms/FormPrimitives';
import { useConfirm } from '@/hooks/useConfirm';
import { UserPlus, XCircle, Trash2, PencilLine } from 'lucide-react';
import './Index.css';

const TYPE_TABS = [
    { value: null, label: 'All' },
    { value: 'host', label: 'Hosts' },
    { value: 'business', label: 'Businesses' },
    { value: 'event', label: 'Events' },
];

// "customersPerDay" / "primary_platform" -> "Customers Per Day" / "Primary Platform"
const humanize = (key) => key.replace(/([a-z])([A-Z])/g, '$1 $2').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

export default function RegistrationIndex({ registrations, filters = {}, typeCounts = {} }) {
    const [selectedIds, setSelectedIds] = useState([]);
    const { confirm, ConfirmDialogEl } = useConfirm();

    const statusClass = (status) => {
        const map = {
            active: 'registrations-page__status-badge--active',
            inactive: 'registrations-page__status-badge--inactive',
            converted: 'registrations-page__status-badge--converted',
        };
        return map[status] || 'registrations-page__status-badge--default';
    };

    const handleStatusChange = (id, status) => {
        router.put(route('admin.registrations.update', id), { status }, {
            preserveScroll: true,
            onSuccess: () => notify.success(`Registration ${status}`),
            onError: () => notify.error('Failed to update registration'),
        });
    };

    // Create the account and invite by every channel the sign-up gave us.
    // "Edit details": a call-back lead arrives with a number only; the team adds what they learn on the call.
    const [editing, setEditing] = useState(null);
    const details = useForm({ first_name: '', last_name: '', email: '', business_name: '' });
    const openDetails = (item) => {
        details.setData({ first_name: item.first_name || '', last_name: item.last_name || '', email: item.email || '', business_name: item.business_name || '' });
        details.clearErrors();
        setEditing(item);
    };
    const saveDetails = (e) => {
        e.preventDefault();
        details.put(route('admin.registrations.update', editing.id), {
            preserveScroll: true,
            onSuccess: () => { notify.success('Details saved'); setEditing(null); },
        });
    };

    const inviteChannels = (item) => [item.email && 'email', item.phone && 'whatsapp'].filter(Boolean);

    const convertOne = (item) => new Promise((resolve, reject) => {
        router.post(route('admin.registrations.convert', item.id), { channels: inviteChannels(item) }, {
            preserveScroll: true,
            onSuccess: (page) => {
                const { success, error } = page.props.flash || {};
                error ? notify.error(error) : notify.success(success || 'Account created');
                resolve();
            },
            onError: () => { notify.error('Could not create the account'); reject(); },
        });
    });

    const handleConvert = async (item) => {
        if (!item.first_name) {
            notify.error('Call them first: add their name, then create the account.');
            openDetails(item);
            return;
        }
        const via = inviteChannels(item).map((c) => (c === 'whatsapp' ? 'WhatsApp' : 'email')).join(' and ');
        const ok = await confirm({
            title: item.user_id ? 'Re-send invite' : 'Create account',
            message: item.user_id
                ? `Send ${item.first_name} a new sign-in link by ${via}?`
                : `Create a TenaFi account for ${item.first_name} with their plan and a first property, and invite them by ${via}?`,
            confirmLabel: item.user_id ? 'Re-send' : 'Create & invite',
        });
        if (ok) await convertOne(item).catch(() => {});
    };

    const handleDelete = (id) => {
        router.delete(route('admin.registrations.destroy', id), {
            preserveScroll: true,
            onSuccess: () => notify.success('Registration deleted'),
            onError: () => notify.error('Failed to delete registration'),
        });
    };

    const bulkUpdateStatus = async (ids, status) => {
        for (const id of ids) {
            await new Promise((resolve, reject) => {
                router.put(route('admin.registrations.update', id), { status }, {
                    preserveScroll: true,
                    onSuccess: resolve,
                    onError: reject,
                });
            });
        }
    };

    const bulkDelete = async (ids) => {
        for (const id of ids) {
            await new Promise((resolve, reject) => {
                router.delete(route('admin.registrations.destroy', id), {
                    preserveScroll: true,
                    onSuccess: resolve,
                    onError: reject,
                });
            });
        }
    };

    const columns = [
        {
            id: 'name',
            accessorFn: (item) => `${item.first_name} ${item.last_name}`,
            header: 'Name',
            cell: ({ row: { original: item } }) => (
                item.first_name
                    ? <span className="registrations-page__name">{item.first_name} {item.last_name}</span>
                    : <span className="registrations-page__name registrations-page__name--pending">Call-back request</span>
            ),
        },
        {
            id: 'email',
            accessorFn: (item) => item.email,
            header: 'Email',
            cell: ({ row: { original: item } }) => (
                <span className="registrations-page__email">{item.email || item.phone || '-'}</span>
            ),
        },
        {
            id: 'type',
            accessorFn: (item) => item.type,
            header: 'Type',
            cell: ({ row: { original: item } }) => (
                <span className={`registrations-page__type registrations-page__type--${item.type}`}>{item.type}</span>
            ),
        },
        {
            id: 'business',
            accessorFn: (item) => item.business_name,
            header: 'Business / Property',
            cell: ({ row: { original: item } }) => (
                <span className="registrations-page__property-type">
                    {item.business_name || item.answers?.businessType || (item.property_type !== 'other' ? item.property_type : '-')}
                </span>
            ),
        },
        {
            id: 'location',
            accessorFn: (item) => item.location,
            header: 'Location',
            cell: ({ row: { original: item } }) => <span className="registrations-page__units">{item.location || '-'}</span>,
        },
        {
            id: 'units',
            accessorFn: (item) => item.units,
            header: 'Units',
            cell: ({ row: { original: item } }) => (
                <span className="registrations-page__units">{item.units || item.answers?.locations || '-'}</span>
            ),
        },
        {
            id: 'phone',
            accessorFn: (item) => item.phone,
            header: 'Phone',
            meta: { detailOnly: true },
            cell: ({ row: { original: item } }) => item.phone || '-',
        },
        {
            id: 'answers',
            accessorFn: (item) => null,
            header: 'Other answers',
            meta: { detailOnly: true },
            cell: ({ row: { original: item } }) => {
                const rows = Object.entries({
                    primary_platform: item.primary_platform,
                    biggest_challenge: item.biggest_challenge,
                    referral_source: item.referral_source,
                    ...(item.answers || {}),
                }).filter(([, v]) => v !== null && v !== undefined && v !== '');
                return rows.length ? (
                    <ul className="registrations-page__answers">
                        {rows.map(([k, v]) => <li key={k}><strong>{humanize(k)}:</strong> {Array.isArray(v) ? v.join(', ') : String(v)}</li>)}
                    </ul>
                ) : '-';
            },
        },
        {
            id: 'consent',
            accessorFn: (item) => item.consented_at,
            header: 'Consent',
            meta: { detailOnly: true },
            cell: ({ row: { original: item } }) => item.consented_at
                ? <span className="registrations-page__consent">{new Date(item.consented_at).toLocaleString()} via /{item.source_page}: “{item.consent_text}”</span>
                : '-',
        },
        {
            id: 'status',
            accessorFn: (item) => item.status,
            header: 'Status',
            cell: ({ row: { original: item } }) => (
                <span className={`registrations-page__status-badge ${statusClass(item.status)}`}>
                    {item.status}
                </span>
            ),
        },
    ];

    const tableActions = [
        {
            key: 'details',
            label: 'Edit details',
            icon: <PencilLine size={14} />,
            onClick: openDetails,
        },
        {
            key: 'convert',
            label: 'Create account',
            icon: <UserPlus size={14} />,
            variant: 'success',
            onClick: handleConvert,
        },
        {
            key: 'deactivate',
            label: 'Deactivate',
            icon: <XCircle size={14} />,
            variant: 'warning',
            onClick: (item) => handleStatusChange(item.id, 'inactive'),
        },
        {
            key: 'delete',
            label: 'Delete',
            icon: <Trash2 size={14} />,
            variant: 'danger',
            onClick: (item) => handleDelete(item.id),
        },
    ];

    const bulkActions = [
        {
            label: 'Create accounts',
            icon: <UserPlus size={16} />,
            variant: 'success',
            onClick: async () => {
                const items = registrations.data.filter((r) => selectedIds.includes(r.id));
                const ok = await confirm({
                    title: 'Create accounts',
                    message: `Create accounts for ${items.length} sign-up(s) and invite each by email and/or WhatsApp?`,
                    confirmLabel: 'Create & invite',
                });
                if (!ok) return;
                for (const item of items) {
                    await convertOne(item).catch(() => {});
                }
                setSelectedIds([]);
            },
        },
        {
            label: 'Deactivate',
            icon: <XCircle size={16} />,
            variant: 'warning',
            onClick: async () => {
                await bulkUpdateStatus(selectedIds, 'inactive');
                notify.success(`${selectedIds.length} registration(s) deactivated`);
                setSelectedIds([]);
            },
        },
        {
            label: 'Delete',
            icon: <Trash2 size={16} />,
            variant: 'danger',
            onClick: async () => {
                const ok = await confirm({
                    title: 'Delete Registrations',
                    message: `Are you sure you want to delete ${selectedIds.length} registration(s)? This action cannot be undone.`,
                    confirmLabel: 'Delete',
                    variant: 'danger',
                });
                if (ok) {
                    await bulkDelete(selectedIds);
                    notify.success(`${selectedIds.length} registration(s) deleted`);
                    setSelectedIds([]);
                }
            },
        },
    ];

    return (
        <PageShell
            title="Sign-ups"
            breadcrumbs={[{ label: 'Sign-ups' }]}
            rootRoute="admin.dashboard"
            stats={[
                { label: 'Total', value: registrations.total },
                { label: 'Active', value: registrations.data.filter(r => r.status === 'active').length },
                { label: 'Converted', value: registrations.data.filter(r => r.status === 'converted').length },
            ]}
        >
            <div className="registrations-page__tabs" role="tablist">
                {TYPE_TABS.map((tab) => {
                    const count = tab.value ? (typeCounts[tab.value] || 0) : Object.values(typeCounts).reduce((a, b) => a + Number(b), 0);
                    return (
                        <button
                            key={tab.label}
                            type="button"
                            role="tab"
                            aria-selected={filters.type === tab.value}
                            className={`registrations-page__tab ${(filters.type ?? null) === tab.value ? 'registrations-page__tab--active' : ''}`}
                            onClick={() => router.get(route('admin.registrations.index'), tab.value ? { type: tab.value } : {}, { preserveScroll: true })}
                        >
                            {tab.label} <span className="registrations-page__tab-count">{count}</span>
                        </button>
                    );
                })}
            </div>

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={registrations.data}
                    columns={columns}
                    rowActions={tableActions}
                    detailTitle="Registration Details"
                    emptyMessage="No registrations found"
                    enableSelection
                    selectedIds={selectedIds}
                    onSelectionChange={setSelectedIds}
                    serverPagination={registrations}
                    searchPlaceholder="Search registrations..."
                />
            </GlassCard>


            <BulkActions
                selectedCount={selectedIds.length}
                onClearSelection={() => setSelectedIds([])}
                actions={bulkActions}
            />

            <GlassModal isOpen={!!editing} onClose={() => setEditing(null)} title={editing?.first_name ? `Edit ${editing.first_name}` : 'Call-back request'} maxWidth="lg">
                {editing && (
                    <form onSubmit={saveDetails} className="registrations-page__details">
                        <p className="registrations-page__details-phone">{editing.phone || editing.email}</p>
                        <FormField label="First name" required error={details.errors.first_name}>
                            <TextInput id="lead-first-name" value={details.data.first_name} onChange={(e) => details.setData('first_name', e.target.value)} required autoComplete="off" />
                        </FormField>
                        <FormField label="Last name" error={details.errors.last_name}>
                            <TextInput id="lead-last-name" value={details.data.last_name} onChange={(e) => details.setData('last_name', e.target.value)} autoComplete="off" />
                        </FormField>
                        <FormField label="Email" hint="Optional. The invite goes by WhatsApp without it." error={details.errors.email}>
                            <TextInput id="lead-email" type="email" value={details.data.email} onChange={(e) => details.setData('email', e.target.value)} autoComplete="off" />
                        </FormField>
                        <FormField label={editing.type === 'event' ? 'Event or venue name' : 'Business or property name'} error={details.errors.business_name}>
                            <TextInput id="lead-business" value={details.data.business_name} onChange={(e) => details.setData('business_name', e.target.value)} autoComplete="off" />
                        </FormField>
                        <FormActions>
                            <PillButton type="button" variant="ghost" onClick={() => setEditing(null)}>Cancel</PillButton>
                            <PillButton type="submit" disabled={details.processing}>Save details</PillButton>
                        </FormActions>
                    </form>
                )}
            </GlassModal>

            {ConfirmDialogEl}
        </PageShell>
    );
}
