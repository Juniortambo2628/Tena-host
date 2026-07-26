import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { ArrowLeft, Mail, Phone, Calendar, Building2, Trash2, Loader2 } from 'lucide-react';
import './Show.css';

export default function HostShow({ host }) {
    const { delete: destroy, processing } = useForm();

    const breadcrumbs = [
        { label: 'Hosts', href: route('admin.hosts.index') },
        { label: `${host.first_name} ${host.last_name}` },
    ];

    const handleDelete = () => {
        if (confirm(`Delete host "${host.first_name} ${host.last_name}"? All their properties will be orphaned.`)) {
            destroy(route('admin.hosts.destroy', host.id));
        }
    };

    return (
        <DashboardLayout title={`${host.first_name} ${host.last_name}`}>
            <Head title={`${host.first_name} ${host.last_name}`} />

            <DashboardHero
                title={`${host.first_name} ${host.last_name}`}
                breadcrumbs={breadcrumbs}
                rootRoute="admin.dashboard"
                actions={[
                    { label: 'Back', icon: <ArrowLeft size={16} />, onClick: () => router.get(route('admin.hosts.index')) },
                ]}
            />

            <div className="host-show-grid">
                <div className="host-show-main">
                    <GlassCard padding="p-8">
                        <div className="host-profile-header">
                            <div className="host-avatar">
                                {host.first_name[0]}{host.last_name[0]}
                            </div>
                            <div>
                                <h3 className="host-name">{host.first_name} {host.last_name}</h3>
                                <p className="host-email">{host.email}</p>
                            </div>
                        </div>

                        <div className="host-info-list">
                            <div className="host-info-item">
                                <Mail size={16} className="host-info-icon" />
                                <span className="host-info-text">{host.email}</span>
                            </div>
                            {host.phone_number && (
                                <div className="host-info-item">
                                    <Phone size={16} className="host-info-icon" />
                                    <span className="host-info-text">{host.phone_number}</span>
                                </div>
                            )}
                            <div className="host-info-item">
                                <Calendar size={16} className="host-info-icon" />
                                <span className="host-info-text">Joined {new Date(host.created_at).toLocaleDateString()}</span>
                            </div>
                            <div className="host-info-item">
                                <Building2 size={16} className="host-info-icon" />
                                <span className="host-info-text">{host.properties_count} properties</span>
                            </div>
                        </div>
                    </GlassCard>
                </div>

                <div className="host-sidebar">
                    <GlassCard padding="p-6">
                        <h4 className="host-actions-title">Actions</h4>
                        <div className="host-actions-list">
                            <button
                                onClick={handleDelete}
                                disabled={processing}
                                className="host-delete-btn"
                            >
                                {processing ? <Loader2 className="animate-spin" size={14} /> : <Trash2 size={14} />}
                                Delete Host
                            </button>
                        </div>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
