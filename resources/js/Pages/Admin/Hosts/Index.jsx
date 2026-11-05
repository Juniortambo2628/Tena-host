import React, { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import { useConfirm } from '@/hooks/useConfirm';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import DataTable from '@/Components/Dashboard/DataTable';
import BulkActions from '@/Components/Dashboard/BulkActions';
import { Building2, Eye, Trash2 } from 'lucide-react';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import ViewSwitcher from '@/Components/Dashboard/ViewSwitcher';
import './Index.css';

// Account types (App\Support\AccountType): rentals, businesses and events.
const TYPE_LABELS = { host: 'Rentals', business: 'Business', event: 'Events' };
const TYPE_VIEWS = [{ id: 'all', label: 'All' }, ...Object.entries(TYPE_LABELS).map(([id, label]) => ({ id, label }))];

export default function Index({ hosts, stats, filters = {}, typeCounts = {} }) {
    const data = useMemo(() => hosts.data || hosts, [hosts]);
    const [selectedRows, setSelectedRows] = useState([]);
    const { confirm, ConfirmDialogEl } = useConfirm();

    const summaryStats = useMemo(() => [
        { label: 'Accounts', value: stats?.total ?? Object.values(typeCounts).reduce((a, b) => a + Number(b), 0) },
        { label: 'Active', value: stats?.active ?? data.filter(h => h.is_active !== false).length },
        { label: 'Properties', value: stats?.properties ?? data.reduce((sum, h) => sum + (h.properties_count || 0), 0) },
        { label: 'New This Month', value: stats?.new_month ?? 0 },
    ], [data, stats]);

    const columns = useMemo(() => [
        {
            header: 'Account',
            accessorKey: 'name',
            cell: info => (
                <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-black/5 flex items-center justify-center">
                        <Building2 size={14} className="text-black/40" />
                    </div>
                    <div className="flex flex-col">
                        <span className="font-black text-sm">{info.getValue()}</span>
                        <span className="text-xs text-black/40 font-bold leading-none mt-1">{info.row.original.email || info.row.original.phone_number}</span>
                    </div>
                </div>
            )
        },
        {
            header: 'Type',
            accessorKey: 'account_type',
            cell: info => (
                <span className="text-xs font-black">{TYPE_LABELS[info.getValue()] || 'Rentals'}</span>
            )
        },
        {
            header: 'Plan',
            accessorKey: 'billing_plan',
            cell: info => (
                <span className="text-xs font-bold text-black/60">
                    {info.getValue() ? `${info.getValue()[0].toUpperCase()}${info.getValue().slice(1)} · ${info.row.original.billing_units}` : '—'}
                </span>
            )
        },
        {
            header: 'Places',
            accessorKey: 'properties_count',
            cell: info => (
                <span className="text-sm font-black">{info.getValue() ?? 0}</span>
            )
        },
        {
            header: 'Status',
            id: 'status',
            cell: () => <StatusBadge status="active" />
        },
        {
            header: 'Joined',
            accessorKey: 'created_at',
            cell: info => (
                <span className="text-xs font-bold text-black/60">{new Date(info.getValue()).toLocaleDateString()}</span>
            )
        },
        {
            header: 'Actions',
            id: 'actions',
            enableSorting: false,
            cell: info => {
                const host = info.row.original;
                return (
                    <div className="flex items-center gap-1">
                        <button
                            onClick={() => router.get(route('admin.hosts.show', host.id))}
                            className="p-2 hover:bg-black/5 rounded-lg text-black/40 hover:text-black transition-colors"
                            title="View Details"
                        >
                            <Eye size={14} />
                        </button>
                        <button
                            onClick={async () => {
                                const result = await confirm({
                                    title: 'Delete account',
                                    message: `Delete the account "${host.first_name} ${host.last_name}"?`,
                                    confirmLabel: 'Delete',
                                    variant: 'danger',
                                });
                                if (result) {
                                    router.delete(route('admin.hosts.destroy', host.id), {
                                        preserveScroll: true,
                                        onSuccess: () => notify.success('Account deleted'),
                                        onError: () => notify.error('Failed to delete account'),
                                    });
                                }
                            }}
                            className="p-2 hover:bg-red-50 rounded-lg text-black/40 hover:text-red-500 transition-colors"
                            title="Delete account"
                        >
                            <Trash2 size={14} />
                        </button>
                    </div>
                );
            }
        }
    ], []);

    const bulkActions = [
        {
            label: 'Delete',
            icon: <Trash2 size={16} />,
            variant: 'danger',
            onClick: async () => {
                const result = await confirm({
                    title: 'Delete accounts',
                    message: `Delete ${selectedRows.length} account(s)?`,
                    confirmLabel: 'Delete',
                    variant: 'danger',
                });
                if (result) {
                    for (const id of selectedRows) {
                        await new Promise((resolve, reject) => {
                            router.delete(route('admin.hosts.destroy', id), {
                                preserveScroll: true,
                                onSuccess: resolve,
                                onError: reject,
                            });
                        });
                    }
                    notify.success(`${selectedRows.length} account(s) deleted`);
                    setSelectedRows([]);
                }
            },
        },
    ];

    return (
        <>
            <Head title="Accounts" />
            <DashboardHero
                title="Accounts"
                subtitle="Rentals, businesses and events"
                breadcrumbs={[{ label: 'Accounts', href: route('admin.hosts.index') }]}
                rootRoute="admin.dashboard"
                stats={summaryStats}
            />

            <ViewSwitcher
                label="Account type"
                views={TYPE_VIEWS.map((v) => ({ ...v, label: `${v.label} (${v.id === 'all' ? Object.values(typeCounts).reduce((a, b) => a + Number(b), 0) : (typeCounts[v.id] || 0)})` }))}
                value={filters.type || 'all'}
                onChange={(id) => router.get(route('admin.hosts.index'), id === 'all' ? {} : { type: id }, { preserveScroll: true })}
            />

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    emptyMessage="No accounts yet"
                    data={data}
                    columns={columns}
                    searchPlaceholder="Search accounts..."
                    serverPagination={hosts}
                    enableSelection
                    selectedIds={selectedRows}
                    onSelectionChange={setSelectedRows}
                />
            </GlassCard>

            <BulkActions
                selectedCount={selectedRows.length}
                onSelectionChange={setSelectedRows}
                onClearSelection={() => setSelectedRows([])}
                actions={bulkActions}
            />
            {ConfirmDialogEl}
        </>
    );
}
