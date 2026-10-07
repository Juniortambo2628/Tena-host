import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import './Show.css';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import { MessageCircle, Mail, Pencil, X } from 'lucide-react';
import { notify } from '@/Components/Toast';
import { T } from '@/Components/Dashboard/Terms';

const formatDate = (iso) => new Date(iso).toLocaleDateString('en-KE', { day: 'numeric', month: 'short', year: 'numeric' });

export default function GuestShow({ guest, activity = [] }) {
    const [editing, setEditing] = useState(false);
    const name = [guest.first_name, guest.last_name].filter(Boolean).join(' ');

    const details = useForm({
        first_name: guest.first_name || '',
        last_name: guest.last_name || '',
        email: guest.email || '',
        phone: guest.phone || '',
    });
    const notes = useForm({ notes: guest.notes || '' });

    const saveDetails = (e) => {
        e.preventDefault();
        details.put(route('host.guests.update', guest.id), {
            preserveScroll: true,
            onSuccess: () => { setEditing(false); notify.success('Guest updated'); },
        });
    };

    const saveNotes = () => notes.put(route('host.guests.update', guest.id), {
        preserveScroll: true,
        onSuccess: () => notify.success('Notes saved'),
    });

    const whatsapp = guest.phone ? `https://wa.me/${guest.phone.replace(/\D/g, '')}` : null;

    const actions = [
        { label: editing ? 'Cancel' : 'Edit Guest', icon: editing ? <X size={16} /> : <Pencil size={16} />, onClick: () => setEditing(!editing) },
        whatsapp && { label: 'WhatsApp', variant: 'primary', icon: <MessageCircle size={16} />, onClick: () => window.open(whatsapp, '_blank', 'noopener') },
        !whatsapp && guest.email && { label: 'Email', variant: 'primary', icon: <Mail size={16} />, onClick: () => { window.location.href = `mailto:${guest.email}`; } },
    ].filter(Boolean);

    const stats = [
        { label: 'Total Visits', value: guest.total_visits || 0 },
        { label: 'Property', value: guest.property?.name || 'N/A' },
        { label: 'Offers', value: guest.marketing_opt_in ? 'Opted in' : (guest.consented_at ? 'Visit messages only' : 'No consent record') },
        { label: 'Last Connection', value: guest.last_connected ? formatDate(guest.last_connected) : 'Never' },
    ];

    return (
        <DashboardLayout title="Guest Profile">
            <Head title={`Guest: ${name}`} />

            <DashboardHero
                title={name}
                breadcrumbs={[{ label: 'Guests', href: route('host.guests.index') }, { label: name }]}
                actions={actions}
                stats={stats}
            />

            <div className="host-guests-show">
                <div className="host-guests-show-contact">
                    <GlassCard padding="p-8">
                        <h3 className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-6">Contact Information</h3>

                        {editing ? (
                            <form onSubmit={saveDetails} className="space-y-4">
                                {[
                                    ['first_name', 'First name', 'text'],
                                    ['last_name', 'Last name', 'text'],
                                    ['phone', 'Phone (WhatsApp)', 'tel'],
                                    ['email', 'Email', 'email'],
                                ].map(([key, label, type]) => (
                                    <div key={key}>
                                        <label className="host-guests-show-label">{label}</label>
                                        <input
                                            type={type}
                                            className="host-guests-notes-textarea !min-h-0 py-3"
                                            value={details.data[key]}
                                            onChange={(e) => details.setData(key, e.target.value)}
                                        />
                                        {details.errors[key] && <p className="text-xs font-bold text-red-600 mt-1">{details.errors[key]}</p>}
                                    </div>
                                ))}
                                <PillButton variant="primary" disabled={details.processing} className="w-full">Save</PillButton>
                            </form>
                        ) : (
                            <div className="space-y-6">
                                <div>
                                    <label className="host-guests-show-label">Email Address</label>
                                    <p className="host-guests-show-value">{guest.email || 'Not provided'}</p>
                                </div>
                                <div>
                                    <label className="host-guests-show-label">Phone Number</label>
                                    <p className="host-guests-show-value">{guest.phone || 'Not provided'}</p>
                                </div>
                                <div>
                                    <label className="host-guests-show-label">Consent</label>
                                    <p className="host-guests-show-value">
                                        {guest.consented_at ? `Agreed ${formatDate(guest.consented_at)}` : 'Not recorded'}
                                        {guest.marketing_opt_in ? ' · Opted in to offers' : ''}
                                    </p>
                                    {guest.consent_text && <p className="host-guests-show-address-note">“{guest.consent_text}”</p>}
                                </div>
                                <div>
                                    <label className="host-guests-show-label"><T>Associated Property</T></label>
                                    <p className="host-guests-show-value">{guest.property?.name}</p>
                                    <p className="host-guests-show-address-note">{guest.property?.address}</p>
                                </div>
                            </div>
                        )}
                    </GlassCard>

                    <GlassCard padding="p-8" className="host-guests-marketing-card">
                        <h3 className="host-guests-marketing-title mb-4">Marketing</h3>
                        <p className="host-guests-marketing-desc">
                            {guest.marketing_opt_in || !guest.consented_at
                                ? <><T>This guest</T> can receive your campaigns{guest.total_visits > 1 ? ` and has visited ${guest.total_visits} times` : ''}.</>
                                : <><T>This guest</T> agreed to visit messages only, so campaigns skip them.</>}
                        </p>
                    </GlassCard>
                </div>

                <div className="host-guests-show-info">
                    <GlassCard padding="p-0 overflow-hidden">
                        <div className="px-8 py-6 border-b border-black/5 flex justify-between items-center">
                            <h3 className="font-black text-xs uppercase tracking-widest text-black/50">Activity</h3>
                            <span className="text-[10px] font-black text-black/30 uppercase tracking-widest">{guest.total_visits || 0} visits</span>
                        </div>
                        {activity.length === 0 ? (
                            <p className="px-8 py-6 text-sm text-black/50">No activity yet.</p>
                        ) : (
                            <ol className="divide-y divide-black/5">
                                {activity.map((event, i) => (
                                    <li key={i} className="px-8 py-4 flex justify-between gap-6">
                                        <div>
                                            <p className="text-sm font-bold">{event.label}</p>
                                            {event.detail && <p className="text-xs text-black/50 mt-0.5">{event.detail}</p>}
                                        </div>
                                        <time className="text-xs font-bold text-black/40 whitespace-nowrap">{formatDate(event.at)}</time>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </GlassCard>

                    <GlassCard padding="p-8">
                        <h3 className="text-[10px] font-black uppercase tracking-widest text-black/40 mb-6">Internal Notes</h3>
                        <textarea
                            className="host-guests-notes-textarea"
                            placeholder="Add private notes about this guest..."
                            value={notes.data.notes}
                            onChange={(e) => notes.setData('notes', e.target.value)}
                        />
                        <div className="host-guests-notes-actions">
                            <PillButton variant="primary" onClick={saveNotes} disabled={notes.processing || !notes.isDirty}>Save Notes</PillButton>
                        </div>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
