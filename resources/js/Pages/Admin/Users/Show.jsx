import React, { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { ArrowLeft, Mail, Phone, Calendar, Shield, Trash2, Loader2, ChevronDown } from 'lucide-react';
import './Show.css';

const ROLES = [
    { value: 'admin', label: 'Admin' },
    { value: 'host', label: 'Host' },
    { value: 'staff', label: 'Staff' },
    { value: 'guest', label: 'Guest' },
];

export default function UserShow({ user }) {
    const { delete: destroy, processing } = useForm();
    const [editingRole, setEditingRole] = useState(false);
    const [newRole, setNewRole] = useState(user.role);

    const breadcrumbs = [
        { label: 'Users', href: route('admin.users.index') },
        { label: `${user.first_name} ${user.last_name}` },
    ];

    const handleDelete = async () => {
        const ok = await confirm({
            title: 'Delete User',
            message: `Are you sure you want to delete ${user.first_name} ${user.last_name}? This action cannot be undone.`,
            confirmLabel: 'Delete',
            variant: 'danger',
        });
        if (ok) destroy(route('admin.users.destroy', user.id));
    };

    const handleRoleChange = () => {
        if (newRole === user.role) {
            setEditingRole(false);
            return;
        }
        router.put(route('admin.users.update', user.id), { role: newRole }, {
            preserveScroll: true,
            onSuccess: () => {
                notify.success(`Role changed to ${newRole}`);
                setEditingRole(false);
            },
            onError: () => notify.error('Failed to update role'),
        });
    };

    const roleColors = {
        admin: 'bg-purple-100 text-purple-700',
        host: 'bg-blue-100 text-blue-700',
        staff: 'bg-amber-100 text-amber-700',
        guest: 'bg-green-100 text-green-700',
    };

    return (
        <DashboardLayout title={`${user.first_name} ${user.last_name}`}>
            <Head title={`${user.first_name} ${user.last_name}`} />

            <DashboardHero
                title={`${user.first_name} ${user.last_name}`}
                breadcrumbs={breadcrumbs}
                rootRoute="admin.dashboard"
                actions={[
                    { label: 'Back', icon: <ArrowLeft size={16} />, onClick: () => router.get(route('admin.users.index')) },
                ]}
            />

            <div className="user-show-grid">
                <div className="user-show-main">
                    <GlassCard padding="p-8">
                        <div className="user-profile-header">
                            <div className="user-avatar">
                                {user.first_name[0]}{user.last_name[0]}
                            </div>
                            <div>
                                <h3 className="user-name">{user.first_name} {user.last_name}</h3>
                                <p className="user-email">{user.email}</p>
                                <div className="flex items-center gap-2 mt-1">
                                    {editingRole ? (
                                        <div className="flex items-center gap-2">
                                            <select
                                                value={newRole}
                                                onChange={(e) => setNewRole(e.target.value)}
                                                className="text-xs font-bold border border-black/10 rounded-lg px-3 py-1.5 focus:outline-none focus:border-[#FFD300]"
                                            >
                                                {ROLES.map(r => (
                                                    <option key={r.value} value={r.value}>{r.label}</option>
                                                ))}
                                            </select>
                                            <button onClick={handleRoleChange} className="text-xs font-bold text-[#FFD300] hover:underline">Save</button>
                                            <button onClick={() => { setEditingRole(false); setNewRole(user.role); }} className="text-xs text-black/40 hover:text-black">Cancel</button>
                                        </div>
                                    ) : (
                                        <button onClick={() => setEditingRole(true)} className="flex items-center gap-1">
                                            <span className={`user-role-badge ${roleColors[user.role] || 'role-default'}`}>
                                                {user.role}
                                            </span>
                                            <ChevronDown size={12} className="text-black/30" />
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="user-info-list">
                            <div className="user-info-item">
                                <Mail size={16} className="user-info-icon" />
                                <span className="user-info-text">{user.email}</span>
                            </div>
                            {user.phone_number && (
                                <div className="user-info-item">
                                    <Phone size={16} className="user-info-icon" />
                                    <span className="user-info-text">{user.phone_number}</span>
                                </div>
                            )}
                            <div className="user-info-item">
                                <Calendar size={16} className="user-info-icon" />
                                <span className="user-info-text">Joined {new Date(user.created_at).toLocaleDateString()}</span>
                            </div>
                            <div className="user-info-item">
                                <Shield size={16} className="user-info-icon" />
                                <span className="user-info-text">
                                    Email {user.email_verified_at ? 'Verified' : 'Not Verified'}
                                </span>
                            </div>
                        </div>
                    </GlassCard>
                </div>

                <div className="user-sidebar">
                    <GlassCard padding="p-6">
                        <h4 className="user-actions-title">Actions</h4>
                        <div className="user-actions-list">
                            <button
                                onClick={handleDelete}
                                disabled={processing}
                                className="user-delete-btn"
                            >
                                {processing ? <Loader2 className="animate-spin" size={14} /> : <Trash2 size={14} />}
                                Delete User
                            </button>
                        </div>
                    </GlassCard>
                </div>
            </div>
        </DashboardLayout>
    );
}
