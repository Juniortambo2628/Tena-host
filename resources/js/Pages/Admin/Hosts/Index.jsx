import React, { useMemo, useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import { useConfirm } from '@/hooks/useConfirm';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import DataTable from '@/Components/Dashboard/DataTable';
import BulkActions from '@/Components/Dashboard/BulkActions';
import { Building2, Eye, Trash2 } from 'lucide-react';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import './Index.css';

export default function Index({ hosts, stats }) {
    const data = useMemo(() => hosts.data || hosts, [hosts]);
    const [selectedRows, setSelectedRows] = useState([]);
    const { confirm, ConfirmDialogEl } = useConfirm();

    const summaryStats = useMemo(() => [
        { label: 'Total Hosts', value: stats?.total ?? data.length },
        { label: 'Active', value: stats?.active ?? data.filter(h => h.is_active !== false).length },
        { label: 'Properties', value: stats?.properties ?? data.reduce((sum, h) => sum + (h.properties_count || 0), 0) },
        { label: 'New This Month', value: stats?.new_month ?? 0 },
    ], [data, stats]);

    const columns = useMemo(() => [
        {
            header: 'Host Name',
            accessorKey: 'name',
            cell: info => (
                <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-black/5 flex items-center justify-center">
                        <Building2 size={14} className="text-black/40" />
                    </div>
                    <div className="flex flex-col">
                        <span className="font-black text-sm">{info.getValue()}</span>
                        <span className="text-[10px] text-black/40 font-bold leading-none mt-1">{info.row.original.email}</span>
                    </div>
                </div>
            )
        },
        {
            header: 'Properties',
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
                                    title: 'Delete Host',
                                    message: `Delete host "${host.first_name} ${host.last_name}"?`,
                                    confirmLabel: 'Delete',
                                    variant: 'danger',
                                });
                                if (result) {
                                    router.delete(route('admin.hosts.destroy', host.id), {
                                        preserveScroll: true,
                                        onSuccess: () => notify.success('Host deleted'),
                                        onError: () => notify.error('Failed to delete host'),
                                    });
                                }
                            }}
                            className="p-2 hover:bg-red-50 rounded-lg text-black/40 hover:text-red-500 transition-colors"
                            title="Delete Host"
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
                    title: 'Delete Hosts',
                    message: `Delete ${selectedRows.length} host(s)?`,
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
                    notify.success(`${selectedRows.length} host(s) deleted`);
                    setSelectedRows([]);
                }
            },
        },
    ];

    return (
        <DashboardLayout title="Hosts Management">
            <Head title="Hosts" />
            <DashboardHero
                title="Hosts Management"
                subtitle="Manage property hosts and approvals"
                breadcrumbs={[{ label: 'Hosts', href: route('admin.hosts.index') }]}
                rootRoute="admin.dashboard"
                stats={summaryStats}
            />

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={data}
                    columns={columns}
                    searchPlaceholder="Search hosts..."
                    serverPagination={hosts}
                    enableSelection
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
        </DashboardLayout>
    );
}
