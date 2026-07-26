import React, { useMemo } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { User, Search, Eye, Trash2 } from 'lucide-react';
import DataTable from '@/Components/Dashboard/DataTable';
import './Index.css';

export default function Index({ users }) {
    const data = useMemo(() => users.data || users, [users]);

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
            header: 'Status',
            accessorKey: 'email_verified_at',
            cell: info => {
                const verified = info.getValue();
                return (
                    <span className={`px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest ${verified ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'}`}>
                        {verified ? 'Verified' : 'Pending'}
                    </span>
                );
            }
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
                                    router.delete(route('admin.users.destroy', user.id), { preserveScroll: true });
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

    return (
        <DashboardLayout title="User Management">
            <Head title="Users" />
            <DashboardHero
                title="User Management"
                subtitle="Manage registered guests"
                breadcrumbs={[{ label: 'Users', href: route('admin.users.index') }]}
                rootRoute="admin.dashboard"
            />

            <GlassCard padding="p-0 overflow-hidden">
                <div className="border-b border-black/5">
                    <DataTable data={data} columns={columns} searchPlaceholder="Search guests..." />
                </div>
                {/* Server Side Pagination Links */}
                {users.links && (
                    <div className="users-pagination-bar">
                        <span className="users-pagination-info">
                            Server Pagination (Total: {users.total})
                        </span>
                        <div className="users-pagination-links">
                            {users.links.map((link, i) => (
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        className={`users-pagination-link ${link.active ? 'users-pagination-link-active' : 'users-pagination-link-inactive'} `}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        className="users-pagination-disabled"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                )
                            ))}
                        </div>
                    </div>
                )}
            </GlassCard>
        </DashboardLayout>
    );
}
