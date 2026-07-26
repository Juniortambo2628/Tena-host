import React, { useState, useMemo } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, useForm, Link, router } from '@inertiajs/react';
import './Index.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import TabbedModal from '@/Components/Dashboard/TabbedModal';
import DataTable from '@/Components/Dashboard/DataTable';
import { Plus, CheckCircle2, XCircle } from 'lucide-react';

export default function AmenityIndex({ amenities, filters, properties }) {
    const [showModal, setShowModal] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        property_id: properties[0]?.id || '',
        name: '',
        description: '',
        price: '',
        is_active: true,
        image: null,
    });

    const handleSearch = (search) => {
        router.get(route('host.amenities.index'), { search }, { preserveState: true });
    };

    const submit = () => {
        post(route('host.amenities.store'), {
            forceFormData: true,
            onSuccess: () => {
                setShowModal(false);
                reset();
            },
        });
    };

    const columns = useMemo(() => [
        {
            header: 'Amenity',
            accessorKey: 'name',
            cell: info => (
                <div className="host-amenities-amenity-cell">
                    {info.row.original.image_path ? (
                        <img src={info.row.original.image_path} alt="" className="host-amenities-amenity-image" />
                    ) : (
                        <div className="host-amenities-amenity-placeholder">
                            {info.getValue()?.charAt(0)}
                        </div>
                    )}
                    <div className="flex flex-col">
                        <span className="host-amenities-amenity-name">{info.getValue()}</span>
                        <span className="host-amenities-amenity-desc">{info.row.original.description || 'No description'}</span>
                    </div>
                </div>
            )
        },
        {
            header: 'Property',
            accessorKey: 'property.name',
            cell: info => (
                <span className="host-amenities-property">{info.getValue()}</span>
            )
        },
        {
            header: 'Price',
            accessorKey: 'price',
            cell: info => (
                <span className="host-amenities-price">${parseFloat(info.getValue()).toFixed(2)}</span>
            )
        },
        {
            header: 'Status',
            accessorKey: 'is_active',
            cell: info => (
                <span className={info.getValue() ? 'host-amenities-status-active' : 'host-amenities-status-inactive'}>
                    {info.getValue() ? <CheckCircle2 size={12} /> : <XCircle size={12} />}
                    {info.getValue() ? 'Active' : 'Inactive'}
                </span>
            )
        },
        {
            header: 'Actions',
            id: 'actions',
            cell: info => {
                const amenity = info.row.original;
                return (
                    <Link
                        href={route('host.amenities.show', amenity.id)}
                        className="host-amenities-view-link"
                    >
                        View
                    </Link>
                );
            }
        }
    ], []);

    const modalTabs = [
        {
            label: 'Details',
            content: (
                <div className="space-y-6">
                    <div>
                        <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Property</label>
                        <select
                            className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                            value={data.property_id}
                            onChange={e => setData('property_id', e.target.value)}
                        >
                            {properties.map(p => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                        {errors.property_id && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.property_id}</p>}
                    </div>
                    <div>
                        <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Name</label>
                        <input
                            type="text"
                            className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                        />
                        {errors.name && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.name}</p>}
                    </div>
                    <div>
                        <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Description</label>
                        <textarea
                            rows="2"
                            className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all resize-none"
                            value={data.description}
                            onChange={e => setData('description', e.target.value)}
                        />
                    </div>
                    <div className="host-amenities-modal-grid">
                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Price ($)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                                value={data.price}
                                onChange={e => setData('price', e.target.value)}
                            />
                            {errors.price && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.price}</p>}
                        </div>
                        <div className="flex items-end">
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={e => setData('is_active', e.target.checked)}
                                    className="w-5 h-5 rounded-lg border-black/20 text-[#FFD300] focus:ring-[#FFD300]"
                                />
                                <span className="text-sm font-bold">Active</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">Image</label>
                        <input
                            type="file"
                            accept="image/*"
                            className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-black file:text-white hover:file:bg-black/80"
                            onChange={e => setData('image', e.target.files[0])}
                        />
                        {errors.image && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.image}</p>}
                    </div>
                </div>
            )
        }
    ];

    return (
        <DashboardLayout title="Amenities">
            <Head title="Amenities" />

            <DashboardHero
                title="Property Amenities"
                breadcrumbs={[{ label: 'Amenities' }]}
                actions={[
                    { label: 'Add Amenity', variant: 'primary', icon: <Plus size={16} />, onClick: () => setShowModal(true) },
                ]}
                stats={[
                    { label: 'Total Amenities', value: amenities.total || 0 },
                ]}
            />

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={amenities.data || []}
                    columns={columns}
                    searchPlaceholder="Search amenities..."
                    serverPagination={amenities}
                    onSearch={handleSearch}
                />
            </GlassCard>

            <TabbedModal
                show={showModal}
                onClose={() => setShowModal(false)}
                title="Add Amenity"
                description="Create an upsell amenity for your property"
                tabs={modalTabs}
                onConfirm={submit}
                confirmLabel="Add Amenity"
                processing={processing}
            />
        </DashboardLayout>
    );
}
