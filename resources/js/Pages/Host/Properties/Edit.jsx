import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, useForm, router } from '@inertiajs/react';
import './Edit.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { ArrowLeft, Loader2 } from 'lucide-react';

export default function PropertyEdit({ property }) {
    const { data, setData, post, processing, errors } = useForm({
        name: property.name || '',
        address: property.address || '',
        wifi_ssid: property.wifi_ssid || '',
        occupancy_threshold: property.occupancy_threshold || 20,
        splash_image: null,
        _method: 'patch',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('host.properties.update', property.id), { forceFormData: true });
    };

    const breadcrumbs = [
        { label: 'WiFi Access', href: route('host.properties.index') },
        { label: property.name, href: route('host.properties.show', property.id) },
        { label: 'Edit' },
    ];

    return (
        <DashboardLayout title={`Edit ${property.name}`}>
            <Head title={`Edit ${property.name}`} />

            <DashboardHero
                title={`Editing: ${property.name}`}
                breadcrumbs={breadcrumbs}
                actions={[
                    { label: 'Cancel', icon: <ArrowLeft size={16} />, onClick: () => router.get(route('host.properties.show', property.id)) },
                ]}
            />

            <div className="host-properties-edit">
                <GlassCard padding="p-8">
                    <form onSubmit={submit} className="host-properties-edit-form">
                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Property Name</label>
                            <input
                                type="text"
                                className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                            />
                            {errors.name && <p className="text-red-500 text-[10px] mt-2 font-bold uppercase tracking-widest ml-1">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Address</label>
                            <textarea
                                rows="2"
                                className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all resize-none"
                                value={data.address}
                                onChange={e => setData('address', e.target.value)}
                            />
                        </div>

                        <div className="host-properties-edit-grid">
                            <div>
                                <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">WiFi SSID</label>
                                <input
                                    type="text"
                                    className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                                    value={data.wifi_ssid}
                                    onChange={e => setData('wifi_ssid', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Occupancy Limit</label>
                                <input
                                    type="number"
                                    className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                                    value={data.occupancy_threshold}
                                    onChange={e => setData('occupancy_threshold', e.target.value)}
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Splash Image</label>
                            <input
                                type="file"
                                accept="image/*"
                                onChange={e => setData('splash_image', e.target.files[0])}
                                className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                            />
                            {property.splash_image_path && (
                                <div className="mt-4">
                                    <img src={property.splash_image_path} alt="Current" className="w-full h-32 object-cover rounded-2xl" />
                                </div>
                            )}
                        </div>

                        <div className="host-properties-edit-actions">
                            <PillButton
                                variant="secondary"
                                onClick={() => router.get(route('host.properties.show', property.id))}
                                className="flex-1"
                            >
                                Cancel
                            </PillButton>
                            <PillButton
                                variant="primary"
                                onClick={submit}
                                disabled={processing}
                                className="flex-1"
                            >
                                {processing ? <Loader2 className="animate-spin" size={16} /> : null}
                                Save Changes
                            </PillButton>
                        </div>
                    </form>
                </GlassCard>
            </div>
        </DashboardLayout>
    );
}
