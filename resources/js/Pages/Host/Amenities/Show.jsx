import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import './Show.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { ArrowLeft, Building2, CheckCircle2, XCircle } from 'lucide-react';

export default function AmenityShow({ amenity }) {
    const { patch, processing } = useForm({
        is_active: amenity.is_active,
    });

    const toggleActive = () => {
        patch(route('host.amenities.update', amenity.id), {
            data: { is_active: !amenity.is_active },
        });
    };

    return (
        <DashboardLayout title="Amenity Details">
            <Head title={amenity.name} />

            <DashboardHero
                title={amenity.name}
                breadcrumbs={[
                    { label: 'Amenities', href: route('host.amenities.index') },
                    { label: amenity.name },
                ]}
                actions={[
                    {
                        label: amenity.is_active ? 'Deactivate' : 'Activate',
                        variant: amenity.is_active ? 'white' : 'primary',
                        onClick: toggleActive,
                    },
                ]}
            />

            <div className="host-amenities-show">
                <div className="host-amenities-show-main">
                    <GlassCard padding="p-8">
                        <div className="flex items-start gap-6 mb-8">
                            {amenity.image_path ? (
                                <img src={amenity.image_path} alt="" className="host-amenities-show-image" />
                            ) : (
                                <div className="host-amenities-show-placeholder">
                                    {amenity.name.charAt(0)}
                                </div>
                            )}
                            <div>
                                <h3 className="host-amenities-show-name">{amenity.name}</h3>
                                <p className="host-amenities-show-desc">{amenity.description || 'No description'}</p>
                            </div>
                        </div>

                        <div className="host-amenities-show-stats">
                            <div className="host-amenities-stat-card">
                                <span className="host-amenities-stat-label">Price</span>
                                <span className="host-amenities-stat-value">${parseFloat(amenity.price).toFixed(2)}</span>
                            </div>
                            <div className="host-amenities-stat-card">
                                <span className="host-amenities-stat-label">Status</span>
                                <span className={amenity.is_active ? 'host-amenities-stat-active' : 'host-amenities-stat-inactive'}>
                                    {amenity.is_active ? <CheckCircle2 size={14} /> : <XCircle size={14} />}
                                    {amenity.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        </div>
                    </GlassCard>
                </div>

                <div className="host-amenities-show-sidebar">
                    <GlassCard padding="p-6">
                        <div className="flex items-center gap-4 mb-6">
                            <div className="w-10 h-10 rounded-xl bg-black/5 flex items-center justify-center">
                                <Building2 size={20} className="text-black/40" />
                            </div>
                            <div>
                                <h4 className="text-sm font-black">Property</h4>
                                <p className="text-[10px] font-black uppercase tracking-widest text-black/40">Offered at</p>
                            </div>
                        </div>
                        <p className="font-black text-lg">{amenity.property?.name || 'Unknown'}</p>
                        <p className="text-xs text-black/50 font-medium mt-1">{amenity.property?.address || 'No address'}</p>
                    </GlassCard>

                    <Link
                        href={route('host.amenities.index')}
                        className="host-amenities-back-link"
                    >
                        <ArrowLeft size={14} />
                        Back to Amenities
                    </Link>
                </div>
            </div>
        </DashboardLayout>
    );
}
