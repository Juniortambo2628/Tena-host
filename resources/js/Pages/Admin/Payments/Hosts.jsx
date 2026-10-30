import React from 'react';
import { Head, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DataTable from '@/Components/Dashboard/DataTable';
import { ArrowLeft, Users, DollarSign, CreditCard, Calendar } from 'lucide-react';
import './Hosts.css';

export default function PaymentHosts({ hosts }) {
    const columns = [
        {
            id: 'name',
            accessorFn: (item) => `${item.name} ${item.email}`,
            header: 'Host',
            cell: ({ row: { original: item } }) => (
                <div className="payment-hosts__host">
                    <span className="payment-hosts__host-name">{item.name}</span>
                    <span className="payment-hosts__host-email">{item.email}</span>
                </div>
            ),
        },
        {
            id: 'subscription',
            accessorFn: (item) => item.subscribed ? 'Subscribed' : 'None',
            header: 'Subscription',
            cell: ({ row: { original: item } }) => (
                <span className={`payment-hosts__sub-badge ${item.subscribed ? 'payment-hosts__sub-badge--active' : 'payment-hosts__sub-badge--inactive'}`}>
                    {item.subscribed ? 'Active' : 'Inactive'}
                </span>
            ),
        },
        {
            id: 'total_paid',
            accessorFn: (item) => item.total_paid,
            header: 'Total Paid',
            cell: ({ row: { original: item } }) => (
                <span className="payment-hosts__amount">KES {item.total_paid.toLocaleString()}</span>
            ),
        },
        {
            id: 'transactions',
            accessorFn: (item) => item.transaction_count,
            header: 'Transactions',
            cell: ({ row: { original: item } }) => (
                <span className="payment-hosts__count">{item.transaction_count}</span>
            ),
        },
        {
            id: 'last_payment',
            accessorFn: (item) => item.last_payment,
            header: 'Last Payment',
            cell: ({ row: { original: item } }) => (
                <span className="payment-hosts__date">
                    {item.last_payment ? new Date(item.last_payment).toLocaleDateString() : 'Never'}
                </span>
            ),
        },
    ];

    const tableActions = [
        {
            key: 'view',
            label: 'View Details',
            icon: <CreditCard size={14} />,
            onClick: (item) => router.visit(route('admin.hosts.show', item.id)),
        },
    ];

    return (
        <PageShell
            title="Host Payments"
            breadcrumbs={[
                { label: 'Payments', href: route('admin.payments.index') },
                { label: 'Hosts' },
            ]}
            rootRoute="admin.dashboard"
            stats={[
                { label: 'Total Hosts', value: hosts.length },
                { label: 'Subscribed', value: hosts.filter(h => h.subscribed).length },
                { label: 'Total Revenue', value: `KES ${hosts.reduce((sum, h) => sum + h.total_paid, 0).toLocaleString()}` },
            ]}
        >
            <Head title="Host Payments" />

            <div className="payment-hosts">
                <div className="payment-hosts__header">
                    <PillButton
                        variant="ghost"
                        onClick={() => router.visit(route('admin.payments.index'))}
                        icon={<ArrowLeft size={16} />}
                    >
                        Back to Transactions
                    </PillButton>
                </div>

                <GlassCard padding="p-0 overflow-hidden">
                    <DataTable
                        data={hosts}
                        columns={columns}
                        rowActions={tableActions}
                        emptyMessage="No hosts found"
                        searchPlaceholder="Search hosts..."
                    />
                </GlassCard>
            </div>
        </PageShell>
    );
}
