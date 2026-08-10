import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import './Show.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { Wifi, Users, Radio, MapPin, ArrowLeft, Trash2 } from 'lucide-react';

export default function PropertyShow({ property }) {
    const breadcrumbs = [
        { label: 'WiFi Access', href: route('host.properties.index') },
        { label: property.name },
    ];

    const stats = [
        { label: 'Guests', value: property.guests_count },
        { label: 'Access Points', value: property.access_points_count },
        { label: 'Occupancy Limit', value: property.occupancy_threshold + '%' },
    ];

    return (
        <DashboardLayout title={property.name}>
            <Head title={property.name} />

            <DashboardHero
                title={property.name}
                breadcrumbs={breadcrumbs}
                stats={stats}
                actions={[
                    { label: 'Back', icon: <ArrowLeft size={16} />, onClick: () => router.get(route('host.properties.index')) },
                    { label: 'Edit', variant: 'primary', onClick: () => router.get(route('host.properties.edit', property.id)) },
                ]}
            />

            <div className="host-properties-show">
                {/* Main Info */}
                <div className="host-properties-show-main">
                    <GlassCard padding="p-8">
                        <div className="host-properties-show-header">
                            <div className="host-properties-show-icon">
                                <MapPin size={24} className="text-black" />
                            </div>
                            <div>
                                <h3 className="font-black text-lg">Property Details</h3>
                                <p className="text-[10px] font-bold text-black/40 uppercase tracking-widest">Basic information</p>
                            </div>
                        </div>
                        <div className="host-properties-show-details">
                            <div className="host-properties-show-detail-row">
                                <span className="text-[10px] font-black uppercase tracking-widest text-black/40">Address</span>
                                <span className="text-sm font-bold">{property.address || 'Not set'}</span>
                            </div>
                            <div className="host-properties-show-detail-row">
                                <span className="host-properties-show-detail-label">WiFi SSID</span>
                                <span className="host-properties-show-detail-value">{property.wifi_ssid || 'Not configured'}</span>
                            </div>
                            <div className="host-properties-show-detail-row">
                                <span className="host-properties-show-detail-label">PMS Integration</span>
                                <span className={`px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest ${property.pms_connection_status === 'connected' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                    {property.pms_integration_type || 'None'} — {property.pms_connection_status}
                                </span>
                            </div>
                        </div>
                    </GlassCard>

                    {/* Splash Image Preview */}
                    {property.splash_image_path && (
                        <GlassCard padding="p-0 overflow-hidden">
                            <div className="relative h-64">
                                <img src={property.splash_image_path} alt={property.name} className="w-full h-full object-cover" />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
                                <div className="absolute bottom-6 left-8 text-white">
                                    <p className="text-[10px] font-black uppercase tracking-widest opacity-60 mb-1">Splash Image</p>
                                    <p className="text-lg font-black">{property.name}</p>
                                </div>
                            </div>
                        </GlassCard>
                    )}
                </div>

                <div className="host-properties-show-sidebar">
                    <GlassCard padding="p-6">
                        <h4 className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-4">Quick Stats</h4>
                        <div className="space-y-4">
                            <div className="flex items-center gap-3 p-4 bg-black/[0.02] rounded-2xl">
                                <Users size={16} className="text-black/30" />
                                <div className="flex-1">
                                    <p className="text-[10px] font-black uppercase tracking-widest text-black/30">Total Guests</p>
                                    <p className="text-lg font-black">{property.guests_count}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 p-4 bg-black/[0.02] rounded-2xl">
                                <Radio size={16} className="text-black/30" />
                                <div className="flex-1">
                                    <p className="text-[10px] font-black uppercase tracking-widest text-black/30">Access Points</p>
                                    <p className="text-lg font-black">{property.access_points_count}</p>
                                </div>
                            </div>
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-6">
                        <h4 className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-4">Danger Zone</h4>
                        <button
                            onClick={() => {
                                if (confirm(`Delete "${property.name}"? This will remove all associated guests and data. This cannot be undone.`)) {
                                    router.delete(route('host.properties.destroy', property.id), {
                                        onSuccess: () => notify.success('Property deleted'),
                                        onError: () => notify.error('Failed to delete property'),
                                    });
                                }
                            }}
                            className="w-full py-3 bg-red-50 text-red-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-100 transition-colors flex items-center justify-center gap-2"
                        >
                            <Trash2 size={14} />
                            Delete Property
                        </button>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
