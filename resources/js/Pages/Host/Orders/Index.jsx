import React, { useMemo } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import './Index.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import DataTable from '@/Components/Dashboard/DataTable';
import { ArrowLeft, Building2, User, Package, Clock } from 'lucide-react';

export default function OrderIndex({ orders, filters }) {
    const { patch } = useForm();

    const handleStatusFilter = (status) => {
        router.get(route('host.orders.index'), { status }, { preserveState: true });
    };

    const updateStatus = (order, status) => {
        patch(route('host.orders.update', order.id), { data: { status } });
    };

    const statusBadge = (status) => {
        const classes = {
            pending: 'bg-[#FFD300]/10 text-[#b39700]',
            fulfilled: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return (
            <span className={`px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest ${classes[status]}`}>
                {status}
            </span>
        );
    };

    const columns = useMemo(() => [
        {
            header: 'Order ID',
            accessorKey: 'id',
            cell: info => (
                <span className="text-sm font-black">#{info.getValue()}</span>
            )
        },
        {
            header: 'Guest',
            accessorKey: 'guest',
            cell: info => {
                const guest = info.getValue();
                return (
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-black/5 flex items-center justify-center">
                            <User size={14} className="text-black/40" />
                        </div>
                        <span className="font-black text-sm">{guest?.first_name} {guest?.last_name}</span>
                    </div>
                );
            }
        },
        {
            header: 'Amenity',
            accessorKey: 'amenity.name',
            cell: info => (
                <span className="text-xs font-bold text-black/70">{info.getValue()}</span>
            )
        },
        {
            header: 'Property',
            accessorKey: 'property.name',
            cell: info => (
                <span className="text-[10px] font-black uppercase tracking-widest text-black/50">{info.getValue()}</span>
            )
        },
        {
            header: 'Total',
            accessorKey: 'total',
            cell: info => (
                <span className="text-sm font-black">${parseFloat(info.getValue()).toFixed(2)}</span>
            )
        },
        {
            header: 'Status',
            accessorKey: 'status',
            cell: info => statusBadge(info.getValue())
        },
        {
            header: 'Actions',
            id: 'actions',
            cell: info => {
                const order = info.row.original;
                return (
                    <div className="flex items-center gap-2">
                        <Link
                            href={route('host.orders.show', order.id)}
                            className="p-2 hover:bg-black/5 rounded-lg text-black/40 hover:text-black transition-colors text-[10px] font-black uppercase tracking-widest"
                        >
                            View
                        </Link>
                        {order.status === 'pending' && (
                            <>
                                <button
                                    onClick={() => updateStatus(order, 'fulfilled')}
                                    className="p-2 hover:bg-green-50 rounded-lg text-black/40 hover:text-green-600 transition-colors text-[10px] font-black uppercase tracking-widest"
                                >
                                    Fulfill
                                </button>
                                <button
                                    onClick={() => updateStatus(order, 'cancelled')}
                                    className="p-2 hover:bg-red-50 rounded-lg text-black/40 hover:text-red-600 transition-colors text-[10px] font-black uppercase tracking-widest"
                                >
                                    Cancel
                                </button>
                            </>
                        )}
                    </div>
                );
            }
        }
    ], []);

    return (
        <DashboardLayout title="Orders">
            <Head title="Orders" />

            <DashboardHero
                title="Guest Orders"
                breadcrumbs={[{ label: 'Orders' }]}
                stats={[
                    { label: 'Total Orders', value: orders.total || 0 },
                ]}
            />

            <GlassCard padding="p-0 overflow-hidden">
                <div className="host-orders-filter-bar">
                    {['all', 'pending', 'fulfilled', 'cancelled'].map(status => (
                        <button
                            key={status}
                            onClick={() => handleStatusFilter(status === 'all' ? '' : status)}
                            className={`px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-colors ${filters.status === status || (status === 'all' && !filters.status) ? 'bg-black text-[#FFD300]' : 'bg-black/5 text-black hover:bg-black/10'}`}
                        >
                            {status}
                        </button>
                    ))}
                </div>
                <DataTable
                    data={orders.data || []}
                    columns={columns}
                    searchPlaceholder="Search orders..."
                    serverPagination={orders}
                />
            </GlassCard>
        </DashboardLayout>
    );
}
