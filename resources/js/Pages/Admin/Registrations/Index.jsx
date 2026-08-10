import React, { useState } from 'react';
import PageShell from '@/Layouts/PageShell';
import { Link, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import GlassCard from '@/Components/Dashboard/GlassCard';
import ResponsiveTable from '@/Components/Dashboard/ResponsiveTable';
import BulkActions from '@/Components/Dashboard/BulkActions';
import ServerPagination from '@/Components/Dashboard/ServerPagination';
import { CheckCircle2, XCircle, Trash2 } from 'lucide-react';
import './Index.css';

export default function RegistrationIndex({ registrations }) {
    const [selectedIds, setSelectedIds] = useState([]);

    const statusClass = (status) => {
        const map = {
            active: 'registrations-page__status-badge--active',
            inactive: 'registrations-page__status-badge--inactive',
            converted: 'registrations-page__status-badge--converted',
        };
        return map[status] || 'registrations-page__status-badge--default';
    };

    const handleStatusChange = (id, status) => {
        router.put(route('admin.registrations.update', id), { status }, {
            preserveScroll: true,
            onSuccess: () => notify.success(`Registration ${status}`),
            onError: () => notify.error('Failed to update registration'),
        });
    };

    const handleDelete = (id) => {
        router.delete(route('admin.registrations.destroy', id), {
            preserveScroll: true,
            onSuccess: () => notify.success('Registration deleted'),
            onError: () => notify.error('Failed to delete registration'),
        });
    };

    const columns = [
        {
            key: 'name',
            label: 'Name',
            render: (item) => (
                <span className="registrations-page__name">{item.first_name} {item.last_name}</span>
            ),
        },
        {
            key: 'email',
            label: 'Email',
            render: (item) => (
                <span className="registrations-page__email">{item.email}</span>
            ),
        },
        {
            key: 'property_type',
            label: 'Property Type',
            render: (item) => (
                <span className="registrations-page__property-type">{item.property_type}</span>
            ),
        },
        {
            key: 'units',
            label: 'Units',
            render: (item) => (
                <span className="registrations-page__units">{item.units}</span>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (item) => (
                <span className={`registrations-page__status-badge ${statusClass(item.status)}`}>
                    {item.status}
                </span>
            ),
        },
    ];

    const tableActions = [
        {
            key: 'convert',
            label: 'Convert',
            icon: <CheckCircle2 size={14} />,
            variant: 'convert',
            onClick: (item) => handleStatusChange(item.id, 'converted'),
        },
        {
            key: 'deactivate',
            label: 'Deactivate',
            icon: <XCircle size={14} />,
            variant: 'deactivate',
            onClick: (item) => handleStatusChange(item.id, 'inactive'),
        },
        {
            key: 'delete',
            label: 'Delete',
            icon: <Trash2 size={14} />,
            variant: 'delete',
            onClick: (item) => handleDelete(item.id),
        },
    ];

    const bulkActions = [
        {
            label: 'Convert',
            icon: <CheckCircle2 size={16} />,
            variant: 'success',
            onClick: () => {
                selectedIds.forEach(id => handleStatusChange(id, 'converted'));
                notify.success(`${selectedIds.length} registration(s) converted`);
                setSelectedIds([]);
            },
        },
        {
            label: 'Deactivate',
            icon: <XCircle size={16} />,
            variant: 'warning',
            onClick: () => {
                selectedIds.forEach(id => handleStatusChange(id, 'inactive'));
                notify.success(`${selectedIds.length} registration(s) deactivated`);
                setSelectedIds([]);
            },
        },
        {
            label: 'Delete',
            icon: <Trash2 size={16} />,
            variant: 'danger',
            onClick: () => {
                if (confirm(`Delete ${selectedIds.length} registration(s)?`)) {
                    selectedIds.forEach(id => handleDelete(id));
                    setSelectedIds([]);
                }
            },
        },
    ];

    return (
        <PageShell
            title="Waitlist Registrations"
            breadcrumbs={[{ label: 'Registrations' }]}
            rootRoute="admin.dashboard"
            stats={[
                { label: 'Total', value: registrations.total },
                { label: 'Active', value: registrations.data.filter(r => r.status === 'active').length },
                { label: 'Converted', value: registrations.data.filter(r => r.status === 'converted').length },
            ]}
        >
            <GlassCard padding="p-0 overflow-hidden">
                <ResponsiveTable
                    data={registrations.data}
                    columns={columns}
                    actions={tableActions}
                    primaryField={(item) => `${item.first_name} ${item.last_name}`}
                    subtitleField="email"
                    detailTitle="Registration Details"
                    emptyMessage="No registrations found"
                    enableSelection
                    selectedIds={selectedIds}
                    onSelectionChange={setSelectedIds}
                    searchPlaceholder="Search registrations..."
                />
            </GlassCard>

            <ServerPagination links={registrations.links} className="justify-center mt-6" />

            <BulkActions
                selectedCount={selectedIds.length}
                onClearSelection={() => setSelectedIds([])}
                actions={bulkActions}
            />
        </PageShell>
    );
}
