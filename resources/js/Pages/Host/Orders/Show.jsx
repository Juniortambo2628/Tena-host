import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import './Show.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { ArrowLeft, User, Building2, Package, Clock } from 'lucide-react';

export default function OrderShow({ order }) {
    const { patch } = useForm();

    const updateStatus = (status) => {
        patch(route('host.orders.update', order.id), { data: { status } });
    };

    const statusBadge = (status) => {
        const classes = {
            pending: 'bg-[#FFD300]/10 text-[#b39700]',
            fulfilled: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return (
            <span className={`px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest ${classes[status]}`}>
                {status}
            </span>
        );
    };

    return (
        <DashboardLayout title="Order Details">
            <Head title={`Order #${order.id}`} />

            <DashboardHero
                title={`Order #${order.id}`}
                breadcrumbs={[
                    { label: 'Orders', href: route('host.orders.index') },
                    { label: `#${order.id}` },
                ]}
                actions={[
                    ...(order.status === 'pending' ? [
                        { label: 'Fulfill', variant: 'primary', onClick: () => updateStatus('fulfilled') },
                        { label: 'Cancel', variant: 'white', onClick: () => updateStatus('cancelled') },
                    ] : []),
                ]}
            />

            <div className="host-orders-show">
                <div className="host-orders-show-main">
                    <GlassCard padding="p-8">
                        <div className="flex items-center justify-between mb-8">
                            <div>
                                <p className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-2">Status</p>
                                {statusBadge(order.status)}
                            </div>
                            <div className="text-right">
                                <p className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-2">Total</p>
                                <p className="text-3xl font-black">${parseFloat(order.total).toFixed(2)}</p>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <div className="flex items-center gap-4 p-4 bg-black/[0.02] rounded-2xl">
                                <div className="w-10 h-10 rounded-xl bg-black/5 flex items-center justify-center">
                                    <Package size={20} className="text-black/40" />
                                </div>
                                <div>
                                    <p className="text-xs font-black uppercase tracking-widest text-black/40">Amenity</p>
                                    <p className="font-black text-lg">{order.amenity?.name || 'Unknown'}</p>
                                </div>
                            </div>

                            <div className="flex items-center gap-4 p-4 bg-black/[0.02] rounded-2xl">
                                <div className="w-10 h-10 rounded-xl bg-black/5 flex items-center justify-center">
                                    <User size={20} className="text-black/40" />
                                </div>
                                <div>
                                    <p className="text-xs font-black uppercase tracking-widest text-black/40">Guest</p>
                                    <p className="font-black text-lg">{order.guest?.first_name} {order.guest?.last_name}</p>
                                    <p className="text-xs text-black/50 font-medium">{order.guest?.email}</p>
                                </div>
                            </div>
                        </div>
                    </GlassCard>
                </div>

                <div className="host-orders-show-sidebar">
                    <GlassCard padding="p-6">
                        <div className="host-orders-show-sidebar-header">
                            <div className="host-orders-show-sidebar-icon">
                                <Building2 size={20} className="text-black/40" />
                            </div>
                            <div>
                                <h4 className="text-sm font-black">Property</h4>
                                <p className="text-[10px] font-black uppercase tracking-widest text-black/40">Order placed at</p>
                            </div>
                        </div>
                        <p className="font-black text-lg">{order.property?.name || 'Unknown'}</p>
                        <p className="text-xs text-black/50 font-medium mt-1">{order.property?.address || 'No address'}</p>
                    </GlassCard>

                    <GlassCard padding="p-6">
                        <div className="flex items-center gap-4 mb-4">
                            <Clock size={20} className="text-black/40" />
                            <div>
                                <h4 className="text-sm font-black">Timeline</h4>
                            </div>
                        </div>
                        <div className="space-y-3 text-xs font-bold text-black/60">
                            <p>Created: {new Date(order.created_at).toLocaleString()}</p>
                            <p>Updated: {new Date(order.updated_at).toLocaleString()}</p>
                        </div>
                    </GlassCard>

                    <Link
                        href={route('host.orders.index')}
                        className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-black/40 hover:text-black transition-colors"
                    >
                        <ArrowLeft size={14} />
                        Back to Orders
                    </Link>
                </div>
            </div>
        </DashboardLayout>
    );
}
