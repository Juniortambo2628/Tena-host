import React, { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, useForm, Link, router } from '@inertiajs/react';
import './Index.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import TabbedModal from '@/Components/Dashboard/TabbedModal';
import { Plus, Download, Trash2, Info, Home } from 'lucide-react';
import { FilePond, registerPlugin } from 'react-filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

// Import FilePond styles
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

// Register the plugins
registerPlugin(FilePondPluginImagePreview);

export default function PropertyIndex({ properties }) {
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [editingProperty, setEditingProperty] = useState(null);

    const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({
        name: '',
        address: '',
        wifi_ssid: '',
        splash_image_path: '',
        splash_image: null,
        occupancy_threshold: 20,
    });

    const openCreateModal = () => {
        setEditingProperty(null);
        reset();
        clearErrors();
        setShowCreateModal(true);
    };

    const openEditModal = (property) => {
        setEditingProperty(property);
        setData({
            name: property.name || '',
            address: property.address || '',
            wifi_ssid: property.wifi_ssid || '',
            splash_image_path: property.splash_image_path || '',
            splash_image: null,
            occupancy_threshold: property.occupancy_threshold || 20,
        });
        clearErrors();
        setShowCreateModal(true);
    };

    const submit = (e) => {
        if (e) e.preventDefault();

        // Use post with _method patch for file uploads in update
        const method = editingProperty ? 'post' : 'post';
        const url = editingProperty ? route('host.properties.update', editingProperty.id) : route('host.properties.store');

        const options = {
            onSuccess: () => {
                setShowCreateModal(false);
                reset();
            },
            forceFormData: true,
        };

        if (editingProperty) {
            // Laravel handles files in POST but not always in PATCH
            post(url, {
                ...options,
                data: { ...data, _method: 'patch' }
            });
        } else {
            post(url, options);
        }
    };

    const breadcrumbs = [{ label: 'WiFi Access' }];

    const actions = [
        { label: 'Export Data', icon: <Download size={16} /> },
        { label: 'Add Property', variant: 'primary', icon: <Plus size={16} />, onClick: openCreateModal },
    ];

    const stats = [
        { label: 'Active Units', value: properties.length },
        { label: 'Avg Occupancy', value: '14%', trend: -2 },
        { label: 'WiFi Availability', value: '99.9%' },
    ];

    const modalTabs = [
        {
            label: 'Basic Info',
            content: (
                <div className="space-y-6">
                    <div>
                        <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Property Name</label>
                        <input
                            type="text"
                            className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                            placeholder="e.g. Blue Haven Apartments"
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
                            placeholder="Full physical address"
                            value={data.address}
                            onChange={e => setData('address', e.target.value)}
                        />
                    </div>
                </div>
            )
        },
        {
            label: 'WiFi Configuration',
            content: (
                <div className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">WiFi SSID</label>
                            <input
                                type="text"
                                className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                                placeholder="StayAway_WiFi"
                                value={data.wifi_ssid}
                                onChange={e => setData('wifi_ssid', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Occupancy Limit</label>
                            <div className="relative">
                                <input
                                    type="number"
                                    className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                                    value={data.occupancy_threshold}
                                    onChange={e => setData('occupancy_threshold', e.target.value)}
                                />
                                <span className="absolute right-6 top-1/2 -translate-y-1/2 font-black text-black/20">%</span>
                            </div>
                        </div>
                    </div>
                    <div className="p-6 bg-black/[0.02] rounded-[2rem] border border-black/5">
                        <p className="text-[10px] font-bold text-black/50 leading-relaxed">
                            <Info size={16} className="mr-2 inline"></Info>
                            Guests will be notified if the network occupancy exceeds this threshold for stability.
                        </p>
                    </div>
                </div>
            )
        },
        {
            label: 'Brand & Splash',
            content: (
                <div className="space-y-6">
                    <div>
                        <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Splash Background</label>
                        <div className="tena-filepond-wrapper">
                            <FilePond
                                files={data.splash_image ? [data.splash_image] : []}
                                onupdatefiles={fileItems => {
                                    setData('splash_image', fileItems[0]?.file || null);
                                }}
                                allowMultiple={false}
                                maxFiles={1}
                                name="splash_image"
                                labelIdle='Drag & Drop your image or <span class="filepond--label-action">Browse</span>'
                                imagePreviewHeight={170}
                                imageCropAspectRatio="16:9"
                                imageResizeTargetWidth={1200}
                                imageResizeTargetHeight={675}
                                credits={false}
                                className="rounded-[2rem] overflow-hidden"
                            />
                        </div>
                        <p className="text-[10px] text-black/30 mt-3 font-bold uppercase tracking-wider italic">
                            This image will be used as the background for your guest portal splash screen.
                        </p>
                    </div>
                    {data.splash_image_path && !data.splash_image && (
                        <div className="space-y-3">
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 ml-1">Current Image</label>
                            <div className="w-full h-32 rounded-[2rem] overflow-hidden border border-black/5 relative group">
                                <img src={data.splash_image_path} className="w-full h-full object-cover transition-transform group-hover:scale-110" alt="Current" />
                                <div className="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span className="text-[10px] font-black text-white uppercase tracking-widest bg-black/20 px-4 py-2 rounded-full backdrop-blur-sm">Active Preview</span>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )
        }
    ];

    return (
        <DashboardLayout title="WiFi Access">
            <Head title="Properties" />

            <DashboardHero
                title="Your Properties"
                breadcrumbs={breadcrumbs}
                actions={actions}
                stats={stats}
            />

            <div className="host-properties-index-grid">
                {properties.map((property) => (
                    <GlassCard
                        key={property.id}
                        padding="p-0"
                        bgImage={property.splash_image_path}
                        className="group flex flex-col h-full"
                    >
                        <div className="host-properties-index-card-body">
                            <div className="host-properties-index-card-top">
                                <Link
                                    href={route('host.properties.index')}
                                    className="w-14 h-14 rounded-2xl bg-[#FFD300]/10 flex items-center justify-center text-[#FFD300] shadow-sm hover:scale-110 transition-transform active:scale-95"
                                >
                                     <Home size={24}></Home>
                                </Link>
                                <span className={`px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest ${property.pms_connection_status === 'connected' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'} shadow-sm`}>
                                    {property.pms_connection_status}
                                </span>
                            </div>

                            <h3 className="host-properties-index-card-title">{property.name}</h3>
                            <p className="host-properties-index-card-address">{property.address || 'No address set'}</p>

                            <div className="host-properties-index-card-details">
                                <div className="host-properties-index-detail-row">
                                    <span className="host-properties-index-detail-label">WiFi SSID</span>
                                    <span className="host-properties-index-detail-value">{property.wifi_ssid || 'Unset'}</span>
                                </div>
                                <div className="host-properties-index-detail-row">
                                    <span className="host-properties-index-detail-label">Occupancy</span>
                                    <span className="host-properties-index-detail-value">{property.occupancy_threshold}% Max</span>
                                </div>
                            </div>
                        </div>

                        <div className="host-properties-index-card-actions">
                            <PillButton
                                onClick={() => openEditModal(property)}
                                variant="secondary"
                                className="flex-1"
                            >
                                Configure Unit
                            </PillButton>
                            <button
                                onClick={() => {
                                    if (confirm(`Delete "${property.name}"? This cannot be undone.`)) {
                                        router.delete(route('host.properties.destroy', property.id));
                                    }
                                }}
                                className="w-14 h-14 bg-white/40 backdrop-blur-md border border-white/40 rounded-2xl flex items-center justify-center text-black/20 hover:text-red-500 hover:bg-white transition-all active:scale-90"
                            >
                                 <Trash2 size={16}></Trash2>
                            </button>
                        </div>
                    </GlassCard>
                ))}

                {/* Add Card Placeholder */}
                <button
                    onClick={openCreateModal}
                    className="host-properties-index-add-card"
                >
                    <div className="host-properties-index-add-icon">
                         <Plus size={32}></Plus>
                    </div>
                    <div className="text-center">
                        <span className="host-properties-index-add-title">Add New Property</span>
                        <span className="host-properties-index-add-subtitle">Start onboarding</span>
                    </div>
                </button>
            </div>

            <TabbedModal
                show={showCreateModal}
                onClose={() => setShowCreateModal(false)}
                title={editingProperty ? 'Property Config' : 'Quick Onboard'}
                description={editingProperty ? `Updating ${editingProperty.name}` : 'Setup your new listing in seconds'}
                tabs={modalTabs}
                onConfirm={submit}
                confirmLabel={editingProperty ? 'Update Property' : 'Create Property'}
                processing={processing}
            />
        </DashboardLayout>
    );
}
