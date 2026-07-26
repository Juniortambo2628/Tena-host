import React from 'react';
import PageShell from '@/Layouts/PageShell';
import { Link, router } from '@inertiajs/react';
import GlassCard from '@/Components/Dashboard/GlassCard';
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
                    <table className="registrations-page__table">
                        <thead>
                            <tr className="registrations-page__head-row">
                                <th className="registrations-page__head-cell">Name</th>
                                <th className="registrations-page__head-cell">Email</th>
                                <th className="registrations-page__head-cell">Property Type</th>
                                <th className="registrations-page__head-cell">Properties</th>
                                <th className="registrations-page__head-cell">Location</th>
                                <th className="registrations-page__head-cell">Status</th>
                                <th className="registrations-page__head-cell">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="registrations-page__body">
                            {registrations.data.map((reg) => (
                                <tr key={reg.id} className="registrations-page__row group">
                                    <td className="registrations-page__cell">
                                        <span className="registrations-page__name">{reg.first_name} {reg.last_name}</span>
                                    </td>
                                    <td className="registrations-page__cell">
                                        <span className="registrations-page__email">{reg.email}</span>
                                    </td>
                                    <td className="registrations-page__cell">
                                        <span className="registrations-page__property-type">{reg.property_type}</span>
                                    </td>
                                    <td className="registrations-page__cell">
                                        <span className="registrations-page__property-count">{reg.property_count}</span>
                                    </td>
                                    <td className="registrations-page__cell">
                                        <span className="registrations-page__location">{reg.location || '-'}</span>
                                    </td>
                                    <td className="registrations-page__cell">
                                        <span className={`registrations-page__status-badge ${statusClass(reg.status)}`}>
                                            {reg.status}
                                        </span>
                                    </td>
                                    <td className="registrations-page__cell">
                                        <div className="registrations-page__actions">
                                            {reg.status !== 'converted' && (
                                                <button
                                                    onClick={() => handleStatusChange(reg.id, 'converted')}
                                                    className="registrations-page__action-btn registrations-page__action-btn--convert"
                                                    title="Convert"
                                                >
                                                    <CheckCircle2 size={14} />
                                                </button>
                                            )}
                                            {reg.status !== 'inactive' && (
                                                <button
                                                    onClick={() => handleStatusChange(reg.id, 'inactive')}
                                                    className="registrations-page__action-btn registrations-page__action-btn--deactivate"
                                                    title="Deactivate"
                                                >
                                                    <XCircle size={14} />
                                                </button>
                                            )}
                                            <button
                                                onClick={() => handleDelete(reg.id)}
                                                className="registrations-page__action-btn registrations-page__action-btn--delete"
                                                title="Delete"
                                            >
                                                <Trash2 size={14} />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {registrations.data.length === 0 && (
                                <tr>
                                    <td colSpan="7" className="registrations-page__empty">
                                        No registrations found
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
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
