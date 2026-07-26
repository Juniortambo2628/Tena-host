import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import StatsCard from '@/Components/StatsCard';
import GlassModal from '@/Components/GlassModal';
import './Dashboard.css';

export default function Dashboard({ auth, registrationCount, registrations, analytics }) {
    const [selectedRegistration, setSelectedRegistration] = useState(null);

    const stats = [
        { title: 'Total Members', value: registrationCount, icon: <svg className="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>, trend: 'up', trendValue: '12%' },
        { title: 'Today', value: analytics.today_registrations, icon: <svg className="w-6 h-6 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>, trend: 'up', trendValue: analytics.today_registrations > 0 ? '+New' : '0' },
        { title: 'Vacation Rentals', value: analytics.vacation_rentals, icon: <svg className="w-6 h-6 text-[#FFD300]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg> },
        { title: 'Hotels/B&B', value: analytics.hotels + analytics.bnb, icon: <svg className="w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg> },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-2xl text-white leading-tight">Property Management Console</h2>}
        >
            <Head title="Dashboard" />

            <div className="dashboard">
                <div className="dashboard-container">
                    <div className="stats-grid">
                        {stats.map((stat, i) => (
                            <StatsCard key={i} {...stat} />
                        ))}
                    </div>

                    <div className="table-section">
                        <div className="table-section-bg" />
                        <div className="table-section-content">
                            <div className="table-header">
                                <h3 className="table-header-title">Recent Activity</h3>
                                <button className="view-all-btn">View All Registrations</button>
                            </div>
                            <div className="table-scroll">
                                <table className="data-table">
                                    <thead>
                                        <tr>
                                            <th>Registrant</th>
                                            <th>Email Address</th>
                                            <th>Property Info</th>
                                            <th className="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {registrations.map((reg) => (
                                            <tr key={reg.id}>
                                                <td className="table-name">{reg.first_name} {reg.last_name}</td>
                                                <td className="table-email">{reg.email}</td>
                                                <td>
                                                    <div className="table-property-info">
                                                        <span className="table-property-type">{reg.property_type.replace('_', ' ')}</span>
                                                        <span className="table-location">{reg.location}</span>
                                                    </div>
                                                </td>
                                                <td className="text-right">
                                                    <button
                                                        onClick={() => setSelectedRegistration(reg)}
                                                        className="table-action-btn"
                                                    >
                                                        Details
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <GlassModal
                isOpen={!!selectedRegistration}
                onClose={() => setSelectedRegistration(null)}
                title="Registration Details"
            >
                {selectedRegistration && (
                    <div className="modal-content">
                        <div className="modal-grid">
                            <div>
                                <label className="modal-label">Full Name</label>
                                <p className="modal-value">{selectedRegistration.first_name} {selectedRegistration.last_name}</p>
                            </div>
                            <div>
                                <label className="modal-label">Email</label>
                                <p className="modal-value">{selectedRegistration.email}</p>
                            </div>
                            <div>
                                <label className="modal-label">Property Type</label>
                                <p className="modal-value capitalize">{selectedRegistration.property_type.replace('_', ' ')}</p>
                            </div>
                            <div>
                                <label className="modal-label">Units</label>
                                <p className="modal-value">{selectedRegistration.property_count}</p>
                            </div>
                        </div>
                        <div>
                            <label className="modal-label">Message</label>
                            <p className="modal-message">
                                {selectedRegistration.message || 'No additional message provided.'}
                            </p>
                        </div>
                        <div className="modal-footer">
                            <button
                                onClick={() => setSelectedRegistration(null)}
                                className="modal-close-btn"
                            >
                                Close View
                            </button>
                        </div>
                    </div>
                )}
            </GlassModal>
        </AuthenticatedLayout>
    );
}
