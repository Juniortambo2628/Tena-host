import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { ArrowLeft, Mail, Phone, Calendar, Building2, Trash2, Loader2 } from 'lucide-react';
import './Show.css';
import { useConfirm } from '@/hooks/useConfirm';
import { notify } from '@/Components/Toast';
import { FormField, Select } from '@/Components/Forms/FormPrimitives';

// App\Support\AccountType: what the account is called and which pages it gets.
const TYPES = [['host', 'Rentals'], ['business', 'Business'], ['event', 'Events']];
const PROFILES = [['venue', 'Venue'], ['organiser', 'Organiser'], ['both', 'Venue and organiser']];

export default function HostShow({ host }) {
    const { confirm, ConfirmDialogEl } = useConfirm();
    const { delete: destroy, processing } = useForm();
    const type = useForm({ account_type: host.account_type || 'host', account_profile: host.account_profile || '' });
    const saveType = (e) => {
        e.preventDefault();
        type.transform((d) => ({ ...d, account_profile: d.account_type === 'event' ? (d.account_profile || null) : null }));
        type.put(route('admin.hosts.update', host.id), { preserveScroll: true, onSuccess: () => notify.success('Account type saved') });
    };

    const breadcrumbs = [
        { label: 'Accounts', href: route('admin.hosts.index') },
        { label: `${host.first_name} ${host.last_name}` },
    ];

    const handleDelete = async () => {
        const ok = await confirm({
            title: 'Delete account',
            message: `Delete the account "${host.first_name} ${host.last_name}"? All their places will be orphaned.`,
            confirmLabel: 'Delete',
            variant: 'danger',
        });
        if (ok) destroy(route('admin.hosts.destroy', host.id));
    };

    return (
        <>
            <Head title={`${host.first_name} ${host.last_name}`} />

            <DashboardHero
                title={`${host.first_name} ${host.last_name}`}
                breadcrumbs={breadcrumbs}
                rootRoute="admin.dashboard"
                actions={[
                    { label: 'Back', icon: <ArrowLeft size={16} />, href: route('admin.hosts.index') },
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
                                <span className="host-info-text">{host.properties_count} places</span>
                            </div>
                        </div>
                    </GlassCard>
                </div>

                <div className="host-sidebar">
                    <GlassCard padding="p-6">
                        <h4 className="host-actions-title">Account type</h4>
                        <form onSubmit={saveType} className="host-type-form">
                            <FormField label="Type" error={type.errors.account_type}>
                                <Select id="account-type" value={type.data.account_type} onChange={(e) => type.setData('account_type', e.target.value)}>
                                    {TYPES.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                                </Select>
                            </FormField>
                            {type.data.account_type === 'event' && (
                                <FormField label="Runs events as" hint="Changes defaults and wording only." error={type.errors.account_profile}>
                                    <Select id="account-profile" value={type.data.account_profile} onChange={(e) => type.setData('account_profile', e.target.value)}>
                                        <option value="">Not set</option>
                                        {PROFILES.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                                    </Select>
                                </FormField>
                            )}
                            <PillButton type="submit" disabled={type.processing || !type.isDirty}>Save</PillButton>
                        </form>
                    </GlassCard>
                    <GlassCard padding="p-6">
                        <h4 className="host-actions-title">Actions</h4>
                        <div className="host-actions-list">
                            <button
                                onClick={handleDelete}
                                disabled={processing}
                                className="host-delete-btn"
                            >
                                {processing ? <Loader2 className="animate-spin" size={14} /> : <Trash2 size={14} />}
                                Delete account
                            </button>
                        </div>
                    </GlassCard>
                </div>
            </div>
            {ConfirmDialogEl}
        </>
    );
}
