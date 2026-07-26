import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import './Show.css';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';

export default function GuestShow({ guest }) {
    const breadcrumbs = [
        { label: 'Guests', href: route('host.guests.index') },
        { label: guest.first_name + ' ' + guest.last_name }
    ];

    const actions = [
        { label: 'Edit Guest', icon: <i className="fas fa-edit" />, onClick: () => console.log('Edit guest') },
        { label: 'Send Email', variant: 'primary', icon: <i className="fas fa-paper-plane" />, onClick: () => console.log('Send email') },
    ];

    const stats = [
        { label: 'Total Visits', value: guest.total_visits || 0 },
        { label: 'Property', value: guest.property?.name || 'N/A' },
        { label: 'Status', value: 'Verified' },
        { label: 'Last Connection', value: guest.last_connected ? new Date(guest.last_connected).toLocaleDateString() : 'Never' },
    ];

    return (
        <DashboardLayout title="Guest Profile">
            <Head title={`Guest: ${guest.first_name} ${guest.last_name}`} />

            <DashboardHero
                title={`${guest.first_name} ${guest.last_name}`}
                breadcrumbs={breadcrumbs}
                actions={actions}
                stats={stats}
            />

            <div className="host-guests-show">
                {/* Left Column: Contact & Info */}
                <div className="host-guests-show-contact">
                    <GlassCard padding="p-8">
                        <h3 className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-6">Contact Information</h3>

                        <div className="space-y-6">
                            <div>
                                <label className="host-guests-show-label">Email Address</label>
                                <p className="host-guests-show-value">{guest.email}</p>
                            </div>
                            <div>
                                <label className="host-guests-show-label">Phone Number</label>
                                <p className="host-guests-show-value">{guest.phone || 'Not provided'}</p>
                            </div>
                            <div>
                                <label className="host-guests-show-label">Associated Property</label>
                                <p className="host-guests-show-value">{guest.property?.name}</p>
                                <p className="host-guests-show-address-note">{guest.property?.address}</p>
                            </div>
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-8" className="host-guests-marketing-card">
                        <div className="flex items-center gap-4 mb-6">
                            <i className="fas fa-magic text-[#FFD300] text-xl"></i>
                            <h3 className="host-guests-marketing-title">Marketing Insights</h3>
                        </div>
                        <p className="host-guests-marketing-desc">
                            This guest has high re-booking potential. Consider sending a custom discount code for their next stay at {guest.property?.name}.
                        </p>
                        <PillButton variant="secondary" className="w-full">Generate Offer</PillButton>
                    </GlassCard>
                </div>

                {/* Right Column: Activity History */}
                <div className="host-guests-show-info">
                    <GlassCard padding="p-0 overflow-hidden">
                        <div className="px-8 py-6 border-b border-black/5 flex justify-between items-center">
                            <h3 className="font-black text-xs uppercase tracking-widest text-black/50">Visit History</h3>
                            <span className="text-[10px] font-black text-black/30 uppercase tracking-widest">{guest.total_visits || 0} Total stays</span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="host-guests-visit-table">
                                <thead>
                                    <tr className="host-guests-visit-header">
                                        <th className="host-guests-visit-header-cell">Date</th>
                                        <th className="host-guests-visit-header-cell">Property</th>
                                        <th className="host-guests-visit-header-cell">Device</th>
                                        <th className="host-guests-visit-header-cell">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-black/5 text-sm">
                                    <tr className="host-guests-visit-row">
                                        <td className="host-guests-visit-cell">{stats[3].value}</td>
                                        <td className="host-guests-visit-cell">{guest.property?.name}</td>
                                        <td className="host-guests-visit-device">iPhone 14 Pro</td>
                                        <td className="host-guests-visit-cell">
                                            <span className="host-guests-visit-status">Completed</span>
                                        </td>
                                    </tr>
                                    {/* Additional mock visits can be added here */}
                                </tbody>
                            </table>
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-8">
                        <h3 className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-6">Internal Notes</h3>
                        <textarea
                            className="host-guests-notes-textarea"
                            placeholder="Add private notes about this guest..."
                        />
                        <div className="host-guests-notes-actions">
                            <PillButton variant="primary">Save Notes</PillButton>
                        </div>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
