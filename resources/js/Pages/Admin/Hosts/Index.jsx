import React, { useMemo, useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { Building2, Search, Eye, Trash2 } from 'lucide-react';
import DataTable from '@/Components/Dashboard/DataTable';
import './Index.css';

export default function Index({ hosts }) {
    // If hosts is paginated, we use hosts.data. If we want client side, we need all data.
    // For this step, I will use hosts.data and accept per-page sorting to avoid breaking controller yet.

    const data = useMemo(() => hosts.data || hosts, [hosts]);

    const columns = useMemo(() => [
        {
            header: 'Host Name',
            accessorKey: 'name',
            cell: info => (
                <div className="flex flex-col">
                    <span className="font-black text-sm">{info.getValue()}</span>
                    <span className="text-[10px] text-black/40 font-bold leading-none mt-1">{info.row.original.email}</span>
                </div>
            )
        },
        {
            header: 'Properties',
            accessorKey: 'properties_count',
            id: 'properties_count',
            cell: info => (
                <div className="flex items-center gap-2">
                    <Building2 size={16} className="text-black/20" />
                    <span className="text-sm font-black">{info.getValue()}</span>
                </div>
            )
        },
        {
            header: 'Status',
            id: 'status',
            cell: info => {
                const verified = info.getValue();
                return (
                    <span className={`px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest ${verified ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'}`}>
                        {verified ? 'Active' : 'Pending'}
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
                            onClick={() => {
                                if (confirm(`Delete host "${host.first_name} ${host.last_name}"?`)) {
                                    router.delete(route('admin.hosts.destroy', host.id), { preserveScroll: true });
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

    return (
        <DashboardLayout title="Hosts Management">
            <Head title="Hosts" />
            <DashboardHero
                title="Hosts Management"
                subtitle="Manage property hosts and approvals"
                breadcrumbs={[{ label: 'Hosts', href: route('admin.hosts.index') }]}
                rootRoute="admin.dashboard"
            />

            <GlassCard padding="p-0 overflow-hidden">
                <div className="border-b border-black/5">
                    <DataTable data={data} columns={columns} searchPlaceholder="Search hosts..." />
                </div>
                {/* Keep Server Side Pagination Links if needed, or hide if using client side on small batch */}
                {hosts.links && (
                     <div className="hosts-pagination-bar">
                        <span className="hosts-pagination-info">
                            Server Pagination (Total: {hosts.total})
                        </span>
                        <div className="hosts-pagination-links">
                             {hosts.links.map((link, i) => (
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        className={`hosts-pagination-link ${link.active ? 'hosts-pagination-link-active' : 'hosts-pagination-link-inactive'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        className="hosts-pagination-disabled"
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
