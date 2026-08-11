import React from 'react';
import PageShell from '@/Layouts/PageShell';
import { useForm } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import { Bell, Mail, Monitor, Loader2 } from 'lucide-react';
import './NotificationPreferences.css';

export default function NotificationPreferences({ preferences }) {
    const { data, setData, post, processing } = useForm({
        preferences: Object.values(preferences).map(p => ({
            category: p.category,
            email_enabled: p.email_enabled,
            dashboard_enabled: p.dashboard_enabled,
        })),
    });

    const categoryLabels = {
        system: { name: 'System Updates', desc: 'Platform maintenance, new features, and security alerts' },
        user: { name: 'User Activity', desc: 'Guest check-ins, profile changes, and account activity' },
        registration: { name: 'Registrations', desc: 'New waitlist signups and registration approvals' },
        export: { name: 'Exports', desc: 'Data export completion and report generation' },
    };

    const togglePref = (category, field) => {
        const updated = data.preferences.map(p => {
            if (p.category === category) {
                return { ...p, [field]: !p[field] };
            }
            return p;
        });
        setData('preferences', updated);
    };

    const submit = () => {
        post(route('profile.notifications.update'));
    };

    return (
        <PageShell
            title="Notification Preferences"
            headTitle="Notifications"
            breadcrumbs={[
                { label: 'Profile', href: route('profile.edit') },
                { label: 'Notifications' },
            ]}
            actions={[
                { label: processing ? 'Saving...' : 'Save Preferences', variant: 'primary', onClick: submit, icon: processing ? <Loader2 className="animate-spin" size={16} /> : null },
            ]}
        >
            <div className="notifications-page">
                <GlassCard padding="p-8">
                    <div className="notifications-page__card">
                        <div className="notifications-page__header">
                            <div className="notifications-page__header-icon">
                                <Bell size={24} className="notifications-page__header-icon-svg" />
                            </div>
                            <div>
                                <h3 className="notifications-page__title">Channel Settings</h3>
                                <p className="notifications-page__subtitle">Choose how you receive notifications</p>
                            </div>
                        </div>

                        <div className="notifications-page__list">
                            {data.preferences.map((pref) => (
                                <div key={pref.category} className="notifications-page__item">
                                    <div className="notifications-page__item-header">
                                        <div>
                                            <h4 className="notifications-page__item-title">{categoryLabels[pref.category]?.name || pref.category}</h4>
                                            <p className="notifications-page__item-description">{categoryLabels[pref.category]?.desc}</p>
                                        </div>
                                    </div>
                                    <div className="notifications-page__channels">
                                        <label className="notifications-page__channel">
                                            <div
                                                onClick={() => togglePref(pref.category, 'email_enabled')}
                                                className={`notifications-page__channel-switch ${pref.email_enabled ? 'notifications-page__channel-switch--checked' : 'notifications-page__channel-switch--unchecked'}`}
                                            >
                                                <span className={`notifications-page__channel-knob ${pref.email_enabled ? 'notifications-page__channel-knob--checked' : 'notifications-page__channel-knob--unchecked'}`} />
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Mail size={14} className="notifications-page__channel-icon" />
                                                <span className="notifications-page__channel-label">Email</span>
                                            </div>
                                        </label>
                                        <label className="notifications-page__channel">
                                            <div
                                                onClick={() => togglePref(pref.category, 'dashboard_enabled')}
                                                className={`notifications-page__channel-switch ${pref.dashboard_enabled ? 'notifications-page__channel-switch--checked' : 'notifications-page__channel-switch--unchecked'}`}
                                            >
                                                <span className={`notifications-page__channel-knob ${pref.dashboard_enabled ? 'notifications-page__channel-knob--checked' : 'notifications-page__channel-knob--unchecked'}`} />
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Monitor size={14} className="notifications-page__channel-icon" />
                                                <span className="notifications-page__channel-label">Dashboard</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </GlassCard>
            </div>
        </PageShell>
    );
}
