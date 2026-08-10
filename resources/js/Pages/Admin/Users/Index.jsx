import React, { useMemo, useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import DataTable from '@/Components/Dashboard/DataTable';
import BulkActions from '@/Components/Dashboard/BulkActions';
import { User, Eye, Trash2, Download } from 'lucide-react';
import StatusBadge from '@/Components/Dashboard/StatusBadge';
import './Index.css';

export default function Index({ users, stats }) {
    const data = useMemo(() => users.data || users, [users]);
    const [selectedRows, setSelectedRows] = useState([]);

    const summaryStats = useMemo(() => [
        { label: 'Total Users', value: stats?.total ?? data.length },
        { label: 'Active', value: stats?.active ?? data.filter(u => u.role === 'host').length },
        { label: 'Admins', value: stats?.admins ?? data.filter(u => u.role === 'admin').length },
        { label: 'New This Month', value: stats?.new_month ?? 0 },
    ], [data, stats]);

    const columns = useMemo(() => [
        {
            header: 'User Name',
            accessorKey: 'first_name',
            cell: info => (
                <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-black/5 flex items-center justify-center">
                        <User size={14} className="text-black/40" />
                    </div>
                    <div className="flex flex-col">
                        <span className="font-black text-sm">{info.row.original.first_name} {info.row.original.last_name}</span>
                        <span className="text-[10px] text-black/40 font-bold leading-none mt-1">{info.row.original.email}</span>
                    </div>
                </div>
            )
        },
        {
            header: 'Email',
            accessorKey: 'email',
            cell: info => (
                <span className="text-xs font-bold text-black/60">{info.getValue()}</span>
            )
        },
        {
            header: 'Role',
            accessorKey: 'role',
            cell: info => <StatusBadge status={info.getValue()} />
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
                const user = info.row.original;
                return (
                    <div className="flex items-center gap-1">
                        <button
                            onClick={() => router.get(route('admin.users.show', user.id))}
                            className="p-2 hover:bg-black/5 rounded-lg text-black/40 hover:text-black transition-colors"
                            title="View Details"
                        >
                            <Eye size={14} />
                        </button>
                        <button
                            onClick={() => {
                                if (confirm(`Delete user "${user.first_name} ${user.last_name}"?`)) {
                                    router.delete(route('admin.users.destroy', user.id), {
                                        preserveScroll: true,
                                        onSuccess: () => notify.success('User deleted'),
                                        onError: () => notify.error('Failed to delete user'),
                                    });
                                }
                            }}
                            className="p-2 hover:bg-red-50 rounded-lg text-black/40 hover:text-red-500 transition-colors"
                            title="Delete User"
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
            onClick: () => {
                if (confirm(`Delete ${selectedRows.length} user(s)?`)) {
                    selectedRows.forEach(id => {
                        router.delete(route('admin.users.destroy', id), { preserveScroll: true });
                    });
                    notify.success(`${selectedRows.length} user(s) deleted`);
                    setSelectedRows([]);
                }
            },
        },
    ];

    return (
        <DashboardLayout title="User Management">
            <Head title="Users" />
            <DashboardHero
                title="User Management"
                subtitle="Manage registered guests"
                breadcrumbs={[{ label: 'Users', href: route('admin.users.index') }]}
                rootRoute="admin.dashboard"
                stats={summaryStats}
            />

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={data}
                    columns={columns}
                    searchPlaceholder="Search users..."
                    serverPagination={users}
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
        </DashboardLayout>
    );
}
