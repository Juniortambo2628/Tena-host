import React from 'react';
import { Head, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import ResponsiveTable from '@/Components/Dashboard/ResponsiveTable';
import { ArrowLeft, Users, DollarSign, CreditCard, Calendar } from 'lucide-react';
import './Hosts.css';

export default function PaymentHosts({ hosts }) {
    const columns = [
        {
            key: 'name',
            label: 'Host',
            render: (item) => (
                <div className="payment-hosts__host">
                    <span className="payment-hosts__host-name">{item.name}</span>
                    <span className="payment-hosts__host-email">{item.email}</span>
                </div>
            ),
        },
        {
            key: 'subscription',
            label: 'Subscription',
            render: (item) => (
                <span className={`payment-hosts__sub-badge ${item.subscribed ? 'payment-hosts__sub-badge--active' : 'payment-hosts__sub-badge--inactive'}`}>
                    {item.subscribed ? 'Active' : 'Inactive'}
                </span>
            ),
        },
        {
            key: 'total_paid',
            label: 'Total Paid',
            render: (item) => (
                <span className="payment-hosts__amount">KES {item.total_paid.toLocaleString()}</span>
            ),
        },
        {
            key: 'transactions',
            label: 'Transactions',
            render: (item) => (
                <span className="payment-hosts__count">{item.transaction_count}</span>
            ),
        },
        {
            key: 'last_payment',
            label: 'Last Payment',
            render: (item) => (
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
                    <ResponsiveTable
                        data={hosts}
                        columns={columns}
                        actions={tableActions}
                        primaryField="name"
                        subtitleField="email"
                        detailTitle="Host Payment Details"
                        emptyMessage="No hosts found"
                    />
                </GlassCard>
            </div>
        </PageShell>
    );
}
