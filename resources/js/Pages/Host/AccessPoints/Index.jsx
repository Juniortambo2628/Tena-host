import React, { useState, useMemo } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, useForm, Link, router } from '@inertiajs/react';
import './Index.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import TabbedModal from '@/Components/Dashboard/TabbedModal';
import DataTable from '@/Components/Dashboard/DataTable';
import { Plus, Wifi, WifiOff } from 'lucide-react';

export default function AccessPointIndex({ accessPoints, filters, properties }) {
    const [showModal, setShowModal] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        property_id: properties[0]?.id || '',
        name: '',
        mac_address: '',
        status: 'offline',
    });

    const handleSearch = (search) => {
        router.get(route('host.access-points.index'), { search }, { preserveState: true });
    };

    const submit = () => {
        post(route('host.access-points.store'), {
            onSuccess: () => {
                setShowModal(false);
                reset();
            },
        });
    };

    const columns = useMemo(() => [
        {
            header: 'Access Point',
            accessorKey: 'name',
            cell: info => (
                <div className="host-accesspoints-ap-cell">
                    <span className="host-accesspoints-ap-name">{info.getValue()}</span>
                    <span className="host-accesspoints-ap-mac">{info.row.original.mac_address}</span>
                </div>
            )
        },
        {
            header: 'Property',
            accessorKey: 'property.name',
            cell: info => (
                <span className="host-accesspoints-property">{info.getValue()}</span>
            )
        },
        {
            header: 'Status',
            accessorKey: 'status',
            cell: info => (
                <span className={info.getValue() === 'online' ? 'host-accesspoints-status-online' : 'host-accesspoints-status-offline'}>
                    {info.getValue() === 'online' ? <Wifi size={12} /> : <WifiOff size={12} />}
                    {info.getValue()}
                </span>
            )
        },
        {
            header: 'Clients',
            accessorKey: 'connected_clients_count',
            cell: info => (
                <span className="host-accesspoints-clients">{info.getValue()}</span>
            )
        },
        {
            header: 'Actions',
            id: 'actions',
            cell: info => {
                const ap = info.row.original;
                return (
                    <div className="flex items-center gap-1">
                        <Link
                            href={route('host.access-points.show', ap.id)}
                            className="host-accesspoints-view-link"
                        >
                            View
                        </Link>
                    </div>
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
                            placeholder="e.g. Lobby AP"
                        />
                        {errors.name && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.name}</p>}
                    </div>
                    <div>
                        <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 mb-3 ml-1">MAC Address</label>
                        <input
                            type="text"
                            className="w-full bg-black/5 border-none rounded-2xl px-6 py-4 outline-none font-bold focus:ring-4 focus:ring-black/5 transition-all"
                            value={data.mac_address}
                            onChange={e => setData('mac_address', e.target.value)}
                            placeholder="AA:BB:CC:DD:EE:FF"
                        />
                        {errors.mac_address && <p className="text-red-500 text-[10px] mt-2 font-bold">{errors.mac_address}</p>}
                    </div>
                </div>
            )
        }
    ];

    return (
        <DashboardLayout title="Access Points">
            <Head title="Access Points" />

            <DashboardHero
                title="WiFi Access Points"
                breadcrumbs={[{ label: 'Access Points' }]}
                actions={[
                    { label: 'Add Access Point', variant: 'primary', icon: <Plus size={16} />, onClick: () => setShowModal(true) },
                ]}
                stats={[
                    { label: 'Total APs', value: accessPoints.total || 0 },
                ]}
            />

            <GlassCard padding="p-0 overflow-hidden">
                <DataTable
                    data={accessPoints.data || []}
                    columns={columns}
                    searchPlaceholder="Search access points..."
                    serverPagination={accessPoints}
                    onSearch={handleSearch}
                />
            </GlassCard>

            <TabbedModal
                show={showModal}
                onClose={() => setShowModal(false)}
                title="Add Access Point"
                description="Register a new access point for one of your properties"
                tabs={modalTabs}
                onConfirm={submit}
                confirmLabel="Add Access Point"
                processing={processing}
            />
        </DashboardLayout>
    );
}
