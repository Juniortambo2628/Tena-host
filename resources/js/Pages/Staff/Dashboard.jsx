import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { Building2, Users, Package, ArrowRight } from 'lucide-react';
import './Dashboard.css';

export default function StaffDashboard({ properties, recentGuests, pendingOrders, stats }) {
    return (
        <DashboardLayout title="Staff Dashboard">
            <Head title="Staff Dashboard" />

            <DashboardHero
                title="Staff Overview"
                breadcrumbs={[{ label: 'Overview' }]}
                stats={[
                    { label: 'Assigned Properties', value: stats.assignedProperties },
                    { label: 'Total Guests', value: stats.totalGuests },
                    { label: 'Pending Orders', value: stats.pendingOrders },
                ]}
            />

            <div className="staff-dashboard-grid">
                <div className="staff-main-content">
                    <GlassCard padding="p-0 overflow-hidden">
                        <div className="staff-table-header">
                            <h3>Assigned Properties</h3>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="staff-table">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Guests</th>
                                        <th>Access Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {properties.map((property) => (
                                        <tr key={property.id}>
                                            <td>
                                                <div className="flex flex-col">
                                                    <span className="font-black text-sm">{property.name}</span>
                                                    <span className="text-[10px] text-black/40 font-bold">{property.address || 'No address'}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span className="text-sm font-black">{property.guests_count}</span>
                                            </td>
                                            <td>
                                                <span className="text-sm font-black">{property.access_points_count}</span>
                                            </td>
                                        </tr>
                                    ))}
                                    {properties.length === 0 && (
                                        <tr>
                                            <td colSpan="3" className="staff-table-empty">
                                                No assigned properties
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-0 overflow-hidden">
                        <div className="staff-table-header">
                            <h3>Recent Guests</h3>
                        </div>
                        <div className="divide-y divide-black/5">
                            {recentGuests.map((guest) => (
                                <div key={guest.id} className="staff-guest-item">
                                    <div className="flex items-center gap-4">
                                        <div className="staff-guest-avatar">
                                            {guest.first_name?.charAt(0) || 'G'}
                                        </div>
                                        <div>
                                            <p className="staff-guest-name">{guest.first_name} {guest.last_name}</p>
                                            <p className="staff-guest-property">{guest.property?.name}</p>
                                        </div>
                                    </div>
                                    <span className="staff-guest-date">
                                        {new Date(guest.created_at).toLocaleDateString()}
                                    </span>
                                </div>
                            ))}
                            {recentGuests.length === 0 && (
                                <div className="staff-table-empty">
                                    No recent guests
                                </div>
                            )}
                        </div>
                    </GlassCard>
                </div>

                <div className="staff-sidebar">
                    <GlassCard padding="p-6">
                        <div className="staff-sidebar-header">
                            <div className="staff-sidebar-icon">
                                <Package size={20} className="text-black/40" />
                            </div>
                            <div>
                                <h3 className="staff-sidebar-title">Pending Orders</h3>
                                <p className="staff-sidebar-subtitle">Awaiting fulfilment</p>
                            </div>
                        </div>
                        <div className="space-y-4">
                            {pendingOrders.map((order) => (
                                <div key={order.id} className="staff-order-item">
                                    <div className="staff-order-header">
                                        <span className="staff-order-name">{order.amenity?.name}</span>
                                        <span className="staff-order-price">${parseFloat(order.total).toFixed(2)}</span>
                                    </div>
                                    <p className="staff-order-details">{order.guest?.first_name} {order.guest?.last_name} • {order.property?.name}</p>
                                </div>
                            ))}
                            {pendingOrders.length === 0 && (
                                <p className="staff-empty">No pending orders</p>
                            )}
                        </div>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
