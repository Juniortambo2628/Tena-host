import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import ResponsiveTable from '@/Components/Dashboard/ResponsiveTable';
import ServerPagination from '@/Components/Dashboard/ServerPagination';
import { CreditCard, DollarSign, Clock, CheckCircle2, AlertCircle, Search, Users } from 'lucide-react';
import './Index.css';

const statusColors = {
    completed: 'payments-page__status--completed',
    pending: 'payments-page__status--pending',
    failed: 'payments-page__status--failed',
};

export default function PaymentIndex({ transactions, stats, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');

    const handleSearch = (value) => {
        setSearch(value);
        router.get(route('admin.payments.index'), { search: value, status: statusFilter }, { preserveState: true, replace: true });
    };

    const handleStatusFilter = (status) => {
        setStatusFilter(status);
        router.get(route('admin.payments.index'), { search, status }, { preserveState: true, replace: true });
    };

    const columns = [
        {
            key: 'receipt',
            label: 'Receipt',
            render: (item) => (
                <span className="payments-page__receipt">{item.MpesaReceiptNumber || '-'}</span>
            ),
        },
        {
            key: 'user',
            label: 'User',
            render: (item) => (
                <div className="payments-page__user">
                    <span className="payments-page__user-name">{item.user?.name || 'Unknown'}</span>
                    <span className="payments-page__user-email">{item.user?.email}</span>
                </div>
            ),
        },
        {
            key: 'amount',
            label: 'Amount',
            render: (item) => (
                <span className="payments-page__amount">KES {item.Amount}</span>
            ),
        },
        {
            key: 'phone',
            label: 'Phone',
            render: (item) => (
                <span className="payments-page__phone">{item.PhoneNumber}</span>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (item) => (
                <span className={`payments-page__status-badge ${statusColors[item.Status] || ''}`}>
                    {item.Status}
                </span>
            ),
        },
        {
            key: 'date',
            label: 'Date',
            render: (item) => (
                <span className="payments-page__date">
                    {new Date(item.created_at).toLocaleDateString()}
                </span>
            ),
        },
    ];

    const tableActions = [
        {
            key: 'view',
            label: 'View Details',
            icon: <CreditCard size={14} />,
            onClick: (item) => router.visit(route('admin.payments.show', item.id)),
        },
    ];

    return (
        <PageShell
            title="Payments"
            breadcrumbs={[{ label: 'Payments' }]}
            rootRoute="admin.dashboard"
            stats={[
                { label: 'Total Revenue', value: `KES ${stats.total_revenue.toLocaleString()}` },
                { label: 'Completed', value: stats.completed },
                { label: 'Pending', value: stats.pending },
                { label: 'Failed', value: stats.failed },
            ]}
            actions={[
                { label: 'Hosts', onClick: () => router.visit(route('admin.payments.hosts')), variant: 'secondary', icon: <Users size={16} /> },
            ]}
        >
            <Head title="Payments" />

            {/* Filters */}
            <GlassCard padding="p-4">
                <div className="payments-page__filters">
                    <div className="payments-page__search">
                        <Search size={16} className="payments-page__search-icon" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => handleSearch(e.target.value)}
                            placeholder="Search by receipt, phone, or user..."
                            className="payments-page__search-input"
                        />
                    </div>
                    <div className="payments-page__status-filters">
                        {['all', 'completed', 'pending', 'failed'].map((status) => (
                            <button
                                key={status}
                                onClick={() => handleStatusFilter(status)}
                                className={`payments-page__filter-btn ${statusFilter === status ? 'payments-page__filter-btn--active' : ''}`}
                            >
                                {status.charAt(0).toUpperCase() + status.slice(1)}
                            </button>
                        ))}
                    </div>
                </div>
            </GlassCard>

            {/* Transactions Table */}
            <GlassCard padding="p-0 overflow-hidden">
                <ResponsiveTable
                    data={transactions.data}
                    columns={columns}
                    actions={tableActions}
                    primaryField="MpesaReceiptNumber"
                    subtitleField={(item) => item.user?.name || 'Unknown'}
                    detailTitle="Transaction Details"
                    emptyMessage="No transactions found"
                />
            </GlassCard>

            {/* Pagination */}
            <ServerPagination links={transactions.links} className="justify-center mt-6" />
        </PageShell>
    );
}
