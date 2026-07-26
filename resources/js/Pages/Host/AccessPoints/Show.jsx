import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import './Show.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { Wifi, WifiOff, ArrowLeft, Building2 } from 'lucide-react';

export default function AccessPointShow({ accessPoint }) {
    const { patch, processing } = useForm({
        status: accessPoint.status,
    });

    const toggleStatus = () => {
        patch(route('host.access-points.update', accessPoint.id), {
            data: { status: accessPoint.status === 'online' ? 'offline' : 'online' },
        });
    };

    return (
        <DashboardLayout title="Access Point Details">
            <Head title={accessPoint.name} />

            <DashboardHero
                title={accessPoint.name}
                breadcrumbs={[
                    { label: 'Access Points', href: route('host.access-points.index') },
                    { label: accessPoint.name },
                ]}
                actions={[
                    {
                        label: accessPoint.status === 'online' ? 'Set Offline' : 'Set Online',
                        variant: accessPoint.status === 'online' ? 'white' : 'primary',
                        onClick: toggleStatus,
                    },
                ]}
            />

            <div className="host-accesspoints-show">
                <div className="host-accesspoints-show-main">
                    <GlassCard padding="p-8">
                        <div className="flex items-center gap-4 mb-8">
                            <div className={accessPoint.status === 'online' ? 'host-accesspoints-status-icon-online' : 'host-accesspoints-status-icon-offline'}>
                                {accessPoint.status === 'online' ? <Wifi size={32} /> : <WifiOff size={32} />}
                            </div>
                            <div>
                                <h3 className="host-accesspoints-show-name">{accessPoint.name}</h3>
                                <p className="host-accesspoints-show-mac">MAC: {accessPoint.mac_address}</p>
                            </div>
                        </div>

                        <div className="host-accesspoints-show-stats">
                            <div className="host-accesspoints-stat-card">
                                <span className="host-accesspoints-stat-label">Status</span>
                                <span className={accessPoint.status === 'online' ? 'host-accesspoints-stat-online' : 'host-accesspoints-stat-offline'}>{accessPoint.status}</span>
                            </div>
                            <div className="host-accesspoints-stat-card">
                                <span className="host-accesspoints-stat-label">Connected Clients</span>
                                <span className="host-accesspoints-stat-value">{accessPoint.connected_clients_count}</span>
                            </div>
                            <div className="host-accesspoints-stat-card">
                                <span className="host-accesspoints-stat-label">Last Seen</span>
                                <span className="host-accesspoints-stat-value">{accessPoint.last_seen ? new Date(accessPoint.last_seen).toLocaleString() : 'Never'}</span>
                            </div>
                            <div className="host-accesspoints-stat-card">
                                <span className="host-accesspoints-stat-label">Added</span>
                                <span className="host-accesspoints-stat-value">{new Date(accessPoint.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                    </GlassCard>
                </div>

                <div className="host-accesspoints-show-sidebar">
                    <GlassCard padding="p-6">
                        <div className="flex items-center gap-4 mb-6">
                            <div className="w-10 h-10 rounded-xl bg-black/5 flex items-center justify-center">
                                <Building2 size={20} className="text-black/40" />
                            </div>
                            <div>
                                <h4 className="text-sm font-black">Property</h4>
                                <p className="text-[10px] font-black uppercase tracking-widest text-black/40">Assigned location</p>
                            </div>
                        </div>
                        <p className="font-black text-lg">{accessPoint.property?.name || 'Unknown'}</p>
                        <p className="text-xs text-black/50 font-medium mt-1">{accessPoint.property?.address || 'No address'}</p>
                    </GlassCard>

                    <Link
                        href={route('host.access-points.index')}
                        className="host-accesspoints-back-link"
                    >
                        <ArrowLeft size={14} />
                        Back to Access Points
                    </Link>
                </div>
            </div>
        </DashboardLayout>
    );
}
