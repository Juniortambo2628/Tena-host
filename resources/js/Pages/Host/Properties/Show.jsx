import React, { useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import './Show.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { Wifi, Users, Radio, MapPin, ArrowLeft, Trash2 } from 'lucide-react';
import { T } from '@/Components/Dashboard/Terms';
import { useConfirm } from '@/hooks/useConfirm';

export default function PropertyShow({ property, wifi = {} }) {
    const { confirm, ConfirmDialogEl } = useConfirm();
    useEffect(() => {
        const timer = setInterval(() => {
            if (document.visibilityState === 'visible') router.reload({ only: ['property', 'wifi'] });
        }, 30000);
        return () => clearInterval(timer);
    }, []);
    const breadcrumbs = [
        { label: 'WiFi Access', href: route('host.properties.index') },
        { label: property.name },
    ];

    const stats = [
        { label: 'Guests', value: property.guests_count },
        { label: 'Access Points', value: property.access_points_count },
        { label: 'Occupancy Limit', value: property.occupancy_threshold + ' guests' },
    ];

    return (
        <>
            <Head title={property.name} />

            <DashboardHero
                title={property.name}
                breadcrumbs={breadcrumbs}
                stats={stats}
                actions={[
                    { label: 'Back', icon: <ArrowLeft size={16} />, href: route('host.properties.index') },
                    { label: 'Edit', href: route('host.properties.edit', property.id) },
                    { label: 'Splash Studio', variant: 'primary', href: route('host.properties.splash', property.id) },
                ]}
            />

            <div className="host-properties-show">
                {/* Main Info */}
                <div className="host-properties-show-main">
                    <GlassCard padding="p-8">
                        <h3 className="font-black text-lg">WiFi setup</h3>
                        <ol className="list-decimal pl-5 space-y-3 mt-4 text-sm">
                            <li><Link className="underline" href={route('host.properties.splash', property.id)}>Design the splash page in the Splash Studio</Link> (welcome note, house info, local picks, offers), then <a className="underline" href={route('host.properties.splash-preview', property.id)} target="_blank" rel="noopener noreferrer">preview the saved splash</a>.</li>
                            <li><Link className="underline" href={route('host.access-points.index')}>Register the UniFi access point</Link> using its device MAC and select this property. {property.access_points_count} registered.</li>
                            <li>Controller: <strong>{wifi.controllerConfigured ? 'Credentials saved — admin must verify connectivity' : 'Not configured — ask the administrator to connect UniFi'}</strong>.</li>
                            <li>Enable the guest hotspot / external portal on the controller for <strong>{property.wifi_ssid || 'your guest SSID'}</strong>. Portal URL: <code className="break-all">{wifi.portalUrl}</code>. Allow tena-fi.com over HTTPS before guest authentication.</li>
                            <li>Join the guest WiFi from a phone with mobile data disabled. Complete the splash form and check internet access and the guest activity below.</li>
                        </ol>
                        <p className="text-xs mt-4">Saving an SSID here does not configure the router. Preview does not authorize devices. Device status syncs approximately every 5 minutes; this page refreshes every 30 seconds.</p>
                        <div className="mt-4 space-y-2">
                            {(property.access_points || []).map(ap => <div key={ap.id} className="text-sm border-t pt-2"><strong>{ap.name}</strong> · {ap.mac_address} · {ap.status} · {ap.connected_clients_count} clients<br /><small>Last seen: {ap.last_seen ? new Date(ap.last_seen).toLocaleString() : 'Not seen yet'}</small></div>)}
                        </div>
                    </GlassCard>
                    <GlassCard padding="p-8">
                        <h3 className="font-black text-lg">WiFi guest engagement</h3>
                        <p className="text-sm my-3">{wifi.guests || 0} WiFi guests · {wifi.visits || 0} visits · {wifi.recentGuests || 0} guests connected in the last 24 hours · {wifi.marketingOptIns || 0} opted in to offers</p>
                        <p className="text-xs mb-4">Visits are counted after controller authorization; reconnects within 6 hours count as one visit. Client count above comes from UniFi and includes devices that may not have completed the form.</p>
                        {(wifi.latestGuests || []).length === 0 && <p className="text-sm">No WiFi guests yet. The first successful connection will appear here.</p>}
                        {(wifi.latestGuests || []).map(guest => <div key={guest.id} className="border-t py-3 text-sm"><Link className="underline font-bold" href={route('host.guests.show', guest.id)}>{guest.first_name}</Link> · {guest.total_visits} visits · {guest.last_connected ? new Date(guest.last_connected).toLocaleString() : '—'} · {guest.marketing_opt_in ? 'Offers opted in' : 'No offers consent'}</div>)}
                    </GlassCard>
                    <GlassCard padding="p-8">
                        <div className="host-properties-show-header">
                            <div className="host-properties-show-icon">
                                <MapPin size={24} className="text-black" />
                            </div>
                            <div>
                                <h3 className="font-black text-lg"><T>Property Details</T></h3>
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
                                    <p className="text-[10px] font-black uppercase tracking-widest text-black/30"><T>Total Guests</T></p>
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
                            onClick={async () => {
                                const ok = await confirm({
                                    title: 'Delete Property',
                                    message: `Delete "${property.name}"? This will remove all associated guests and data. This cannot be undone.`,
                                    confirmLabel: 'Delete',
                                    variant: 'danger',
                                });
                                if (!ok) return;
                                router.delete(route('host.properties.destroy', property.id), {
                                    onSuccess: () => notify.success('Property deleted'),
                                    onError: () => notify.error('Failed to delete property'),
                                });
                            }}
                            className="w-full py-3 bg-red-50 text-red-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-100 transition-colors flex items-center justify-center gap-2"
                        >
                            <Trash2 size={14} />
                            Delete Property
                        </button>
                    </GlassCard>
                </div>
            </div>
            {ConfirmDialogEl}
        </>
    );
}
