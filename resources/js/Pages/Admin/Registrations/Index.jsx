import React, { useState } from 'react';
import PageShell from '@/Layouts/PageShell';
import { Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import GlassCard from '@/Components/Dashboard/GlassCard';
import ResponsiveTable from '@/Components/Dashboard/ResponsiveTable';
import BulkActions from '@/Components/Dashboard/BulkActions';
import ServerPagination from '@/Components/Dashboard/ServerPagination';
import { useConfirm } from '@/hooks/useConfirm';
import { CheckCircle2, XCircle, Trash2 } from 'lucide-react';
import './Index.css';

const TYPE_TABS = [
    { value: null, label: 'All' },
    { value: 'host', label: 'Hosts' },
    { value: 'business', label: 'Businesses' },
];

const humanize = (key) => key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

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
            key: 'name',
            label: 'Name',
            render: (item) => (
                <span className="registrations-page__name">{item.first_name} {item.last_name}</span>
            ),
        },
        {
            key: 'email',
            label: 'Email',
            render: (item) => (
                <span className="registrations-page__email">{item.email}</span>
            ),
        },
        {
            key: 'type',
            label: 'Type',
            render: (item) => (
                <span className={`registrations-page__type registrations-page__type--${item.type}`}>{item.type}</span>
            ),
        },
        {
            key: 'business',
            label: 'Business / Property',
            render: (item) => (
                <span className="registrations-page__property-type">
                    {item.business_name || item.answers?.business_type || item.property_type}
                </span>
            ),
        },
        {
            key: 'location',
            label: 'Location',
            render: (item) => <span className="registrations-page__units">{item.location || '-'}</span>,
        },
        {
            key: 'units',
            label: 'Units',
            render: (item) => (
                <span className="registrations-page__units">{item.units || item.answers?.branches || '-'}</span>
            ),
        },
        {
            key: 'phone',
            label: 'Phone',
            detailOnly: true,
            render: (item) => item.phone || '-',
        },
        {
            key: 'answers',
            label: 'Other answers',
            detailOnly: true,
            render: (item) => {
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
            key: 'consent',
            label: 'Consent',
            detailOnly: true,
            render: (item) => item.consented_at
                ? <span className="registrations-page__consent">{new Date(item.consented_at).toLocaleString()} via /{item.source_page}: “{item.consent_text}”</span>
                : '-',
        },
        {
            key: 'status',
            label: 'Status',
            render: (item) => (
                <span className={`registrations-page__status-badge ${statusClass(item.status)}`}>
                    {item.status}
                </span>
            ),
        },
    ];

    const tableActions = [
        {
            key: 'convert',
            label: 'Convert',
            icon: <CheckCircle2 size={14} />,
            variant: 'convert',
            onClick: (item) => handleStatusChange(item.id, 'converted'),
        },
        {
            key: 'deactivate',
            label: 'Deactivate',
            icon: <XCircle size={14} />,
            variant: 'deactivate',
            onClick: (item) => handleStatusChange(item.id, 'inactive'),
        },
        {
            key: 'delete',
            label: 'Delete',
            icon: <Trash2 size={14} />,
            variant: 'delete',
            onClick: (item) => handleDelete(item.id),
        },
    ];

    const bulkActions = [
        {
            label: 'Convert',
            icon: <CheckCircle2 size={16} />,
            variant: 'success',
            onClick: async () => {
                await bulkUpdateStatus(selectedIds, 'converted');
                notify.success(`${selectedIds.length} registration(s) converted`);
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
                <ResponsiveTable
                    data={registrations.data}
                    columns={columns}
                    actions={tableActions}
                    primaryField={(item) => `${item.first_name} ${item.last_name}`}
                    subtitleField="email"
                    detailTitle="Registration Details"
                    emptyMessage="No registrations found"
                    enableSelection
                    selectedIds={selectedIds}
                    onSelectionChange={setSelectedIds}
                    searchPlaceholder="Search registrations..."
                />
            </GlassCard>

            <ServerPagination links={registrations.links} className="justify-center mt-6" />

            <BulkActions
                selectedCount={selectedIds.length}
                onClearSelection={() => setSelectedIds([])}
                actions={bulkActions}
            />

            {ConfirmDialogEl}
        </PageShell>
    );
}
