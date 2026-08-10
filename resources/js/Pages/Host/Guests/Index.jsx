import React, { useState, useMemo } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import './Index.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import TabbedModal from '@/Components/Dashboard/TabbedModal';
import BulkActions from '@/Components/Dashboard/BulkActions';
import ServerPagination from '@/Components/Dashboard/ServerPagination';
import { Plus, Edit2, Trash2, Loader2, Search } from 'lucide-react';

export default function GuestIndex({ guests, filters, properties }) {
    const [showModal, setShowModal] = useState(false);
    const [editingGuest, setEditingGuest] = useState(null);
    const [selectedIds, setSelectedIds] = useState([]);
    const [searchQuery, setSearchQuery] = useState(filters?.search || '');

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        property_id: '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
    });

    const handleSearch = (value) => {
        setSearchQuery(value);
        router.get(route('host.guests.index'), { search: value }, { preserveState: true, replace: true });
    };

    const openCreateModal = () => {
        setEditingGuest(null);
        reset();
        setShowModal(true);
    };

    const openEditModal = (guest) => {
        setEditingGuest(guest);
        setData({
            property_id: guest.property_id,
            first_name: guest.first_name,
            last_name: guest.last_name,
            email: guest.email,
            phone: guest.phone || '',
        });
        setShowModal(true);
    };

    const submit = () => {
        const options = {
            onSuccess: () => {
                setShowModal(false);
                reset();
            },
        };

        if (editingGuest) {
            patch(route('host.guests.update', editingGuest.id), options);
        } else {
            post(route('host.guests.store'), options);
        }
    };

    const handleDelete = (guest) => {
        if (confirm(`Delete guest "${guest.first_name} ${guest.last_name}"?`)) {
            router.delete(route('host.guests.destroy', guest.id), {
                preserveScroll: true,
                onSuccess: () => notify.success('Guest deleted'),
                onError: () => notify.error('Failed to delete guest'),
            });
        }
    };

    const breadcrumbs = [{ label: 'Guests' }];
    const actions = [
        { label: 'Add Guest', variant: 'primary', icon: <Plus size={16} />, onClick: openCreateModal },
    ];

    const modalTabs = [
        {
            label: 'Details',
            content: (
                <div className="space-y-6">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">First Name</label>
                            <input
                                type="text"
                                className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                                value={data.first_name}
                                onChange={e => setData('first_name', e.target.value)}
                            />
                            {errors.first_name && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.first_name}</p>}
                        </div>
                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Last Name</label>
                            <input
                                type="text"
                                className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                                value={data.last_name}
                                onChange={e => setData('last_name', e.target.value)}
                            />
                            {errors.last_name && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.last_name}</p>}
                        </div>
                    </div>
                    <div>
                        <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Email</label>
                        <input
                            type="email"
                            className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                            value={data.email}
                            onChange={e => setData('email', e.target.value)}
                        />
                        {errors.email && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.email}</p>}
                    </div>
                    <div>
                        <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Phone</label>
                        <input
                            type="text"
                            className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                            value={data.phone}
                            onChange={e => setData('phone', e.target.value)}
                            placeholder="+254..."
                        />
                    </div>
                    {!editingGuest && (
                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Property</label>
                            <select
                                className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                                value={data.property_id}
                                onChange={e => setData('property_id', e.target.value)}
                            >
                                <option value="">Select property...</option>
                                {properties.map(p => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                            </select>
                            {errors.property_id && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.property_id}</p>}
                        </div>
                    )}
                </div>
            )
        }
    ];

    return (
        <DashboardLayout title="Guests">
            <Head title="Guest List" />

            <DashboardHero
                title="Guest Database"
                breadcrumbs={breadcrumbs}
                actions={actions}
                stats={[
                    { label: 'Total Guests', value: guests.total || 0 },
                ]}
            />

            <GlassCard padding="p-0 overflow-hidden">
                {/* Search Bar */}
                <div className="px-6 py-4 border-b border-black/5">
                    <div className="flex items-center gap-2 bg-black/5 px-4 py-2.5 rounded-xl w-72">
                        <Search size={16} className="text-black/30" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => handleSearch(e.target.value)}
                            placeholder="Search guests..."
                            className="bg-transparent border-none p-0 text-sm font-bold placeholder:text-black/30 focus:ring-0 w-full"
                        />
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="host-guests-table-header">
                                <th className="host-guests-table-header-cell w-10">
                                    <input
                                        type="checkbox"
                                        checked={guests.data.length > 0 && selectedIds.length === guests.data.length}
                                        onChange={() => {
                                            if (selectedIds.length === guests.data.length) {
                                                setSelectedIds([]);
                                            } else {
                                                setSelectedIds(guests.data.map(g => g.id));
                                            }
                                        }}
                                        className="w-4 h-4 rounded border-black/20 text-[#FFD300] focus:ring-[#FFD300]/20"
                                    />
                                </th>
                                <th className="host-guests-table-header-cell">Guest</th>
                                <th className="host-guests-table-header-cell">Contact</th>
                                <th className="host-guests-table-header-cell">Property</th>
                                <th className="host-guests-table-header-cell">Activity</th>
                                <th className="host-guests-table-header-cell">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-black/5">
                            {guests.data.length > 0 ? (
                                guests.data.map((guest) => (
                                    <tr key={guest.id} className={`host-guests-table-row ${selectedIds.includes(guest.id) ? 'bg-[#FFD300]/5' : ''}`}>
                                        <td className="host-guests-table-cell w-10">
                                            <input
                                                type="checkbox"
                                                checked={selectedIds.includes(guest.id)}
                                                onChange={() => {
                                                    if (selectedIds.includes(guest.id)) {
                                                        setSelectedIds(selectedIds.filter(id => id !== guest.id));
                                                    } else {
                                                        setSelectedIds([...selectedIds, guest.id]);
                                                    }
                                                }}
                                                className="w-4 h-4 rounded border-black/20 text-[#FFD300] focus:ring-[#FFD300]/20"
                                            />
                                        </td>
                                        <td className="host-guests-table-cell">
                                            <div className="flex items-center gap-4">
                                                <div className="host-guests-avatar">
                                                    {guest.first_name?.charAt(0) || 'G'}
                                                </div>
                                                <div className="flex flex-col">
                                                    <Link href={route('host.guests.show', guest.id)} className="host-guests-name-link">
                                                        {guest.first_name} {guest.last_name}
                                                    </Link>
                                                    <span className="host-guests-id-badge">ID: {guest.id}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="host-guests-table-cell">
                                            <div className="flex flex-col gap-1">
                                                <span className="host-guests-email">{guest.email}</span>
                                                <span className="host-guests-phone">{guest.phone || 'No phone'}</span>
                                            </div>
                                        </td>
                                        <td className="host-guests-table-cell">
                                            <span className="host-guests-property">
                                                {guest.property?.name || 'Unknown'}
                                            </span>
                                        </td>
                                        <td className="host-guests-table-cell">
                                            <div className="flex flex-col">
                                                <span className="host-guests-visits">{guest.total_visits} visits</span>
                                                <span className="host-guests-last-date">
                                                    {guest.last_connected ? new Date(guest.last_connected).toLocaleDateString() : 'Never'}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="host-guests-table-cell">
                                            <div className="host-guests-actions">
                                                <button
                                                    onClick={() => openEditModal(guest)}
                                                    className="host-guests-edit-btn"
                                                    title="Edit"
                                                >
                                                    <Edit2 size={14} />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(guest)}
                                                    className="host-guests-delete-btn"
                                                    title="Delete"
                                                >
                                                    <Trash2 size={14} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="6" className="host-guests-empty">
                                        No guests found
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <ServerPagination links={guests.links} className="justify-center mt-6" />
            </GlassCard>

            <BulkActions
                selectedCount={selectedIds.length}
                onClearSelection={() => setSelectedIds([])}
                actions={[
                    {
                        label: 'Delete',
                        icon: <Trash2 size={16} />,
                        variant: 'danger',
                        onClick: () => {
                            if (confirm(`Delete ${selectedIds.length} guest(s)?`)) {
                                selectedIds.forEach(id => {
                                    router.delete(route('host.guests.destroy', id), { preserveScroll: true });
                                });
                                notify.success(`${selectedIds.length} guest(s) deleted`);
                                setSelectedIds([]);
                            }
                        },
                    },
                ]}
            />

            <TabbedModal
                show={showModal}
                onClose={() => setShowModal(false)}
                title={editingGuest ? 'Edit Guest' : 'Add Guest'}
                description={editingGuest ? `Updating ${editingGuest.first_name} ${editingGuest.last_name}` : 'Add a new guest to your property'}
                tabs={modalTabs}
                onConfirm={submit}
                confirmLabel={editingGuest ? 'Update Guest' : 'Add Guest'}
                processing={processing}
            />
        </DashboardLayout>
    );
}
