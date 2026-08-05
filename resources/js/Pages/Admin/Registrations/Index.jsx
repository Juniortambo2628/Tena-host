import React from 'react';
import PageShell from '@/Layouts/PageShell';
import { Link, router } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import ResponsiveTable from '@/Components/Dashboard/ResponsiveTable';
import { CheckCircle2, XCircle, Trash2 } from 'lucide-react';
import './Index.css';

export default function RegistrationIndex({ registrations }) {
    const statusClass = (status) => {
        const map = {
            active: 'registrations-page__status-badge--active',
            inactive: 'registrations-page__status-badge--inactive',
            converted: 'registrations-page__status-badge--converted',
        };
        return map[status] || 'registrations-page__status-badge--default';
    };

    const handleStatusChange = (id, status) => {
        router.put(route('admin.registrations.update', id), { status }, { preserveScroll: true });
    };

    const handleDelete = (id) => {
        if (confirm('Delete this registration?')) {
            router.delete(route('admin.registrations.destroy', id), { preserveScroll: true });
        }
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
                <div className="registrations-page__table-wrapper">
                    <ResponsiveTable
                        data={registrations.data}
                        columns={columns}
                        actions={tableActions}
                        primaryField={(item) => `${item.first_name} ${item.last_name}`}
                        subtitleField="email"
                        detailTitle="Registration Details"
                        emptyMessage="No registrations found"
                    />
                </div>

                {/* Pagination */}
                {registrations.links && registrations.links.length > 3 && (
                    <div className="registrations-page__pagination">
                        {registrations.links.map((link, i) => (
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    className={`registrations-page__page-link ${link.active ? 'registrations-page__page-link--active' : 'registrations-page__page-link--inactive'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={i}
                                    className="registrations-page__page-link registrations-page__page-link--disabled"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            )
                        ))}
                    </div>
                )}
            </GlassCard>
        </PageShell>
    );
}
