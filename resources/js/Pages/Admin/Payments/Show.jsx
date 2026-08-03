import React from 'react';
import { Head, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import { ArrowLeft, CreditCard, User, Phone, DollarSign, Calendar, Hash, FileText } from 'lucide-react';
import './Show.css';

const statusColors = {
    completed: 'payment-show__status--completed',
    pending: 'payment-show__status--pending',
    failed: 'payment-show__status--failed',
};

export default function PaymentShow({ transaction }) {
    return (
        <PageShell
            title="Transaction Details"
            breadcrumbs={[
                { label: 'Payments', href: route('admin.payments.index') },
                { label: transaction.MpesaReceiptNumber || transaction.id },
            ]}
            rootRoute="admin.dashboard"
        >
            <Head title={`Transaction ${transaction.MpesaReceiptNumber || transaction.id}`} />

            <div className="payment-show">
                <div className="payment-show__header">
                    <PillButton
                        variant="ghost"
                        onClick={() => router.visit(route('admin.payments.index'))}
                        icon={<ArrowLeft size={16} />}
                    >
                        Back to Payments
                    </PillButton>
                </div>

                <div className="payment-show__grid">
                    <GlassCard padding="p-6">
                        <div className="payment-show__info-card">
                            <div className="payment-show__info-header">
                                <CreditCard size={20} />
                                <h3>Transaction Info</h3>
                            </div>
                            <div className="payment-show__info-items">
                                <div className="payment-show__info-item">
                                    <span className="payment-show__info-label">Receipt Number</span>
                                    <span className="payment-show__info-value">{transaction.MpesaReceiptNumber || 'N/A'}</span>
                                </div>
                                <div className="payment-show__info-item">
                                    <span className="payment-show__info-label">Status</span>
                                    <span className={`payment-show__status-badge ${statusColors[transaction.Status] || ''}`}>
                                        {transaction.Status}
                                    </span>
                                </div>
                                <div className="payment-show__info-item">
                                    <span className="payment-show__info-label">Amount</span>
                                    <span className="payment-show__info-value payment-show__info-value--large">KES {transaction.Amount}</span>
                                </div>
                                <div className="payment-show__info-item">
                                    <span className="payment-show__info-label">Date & Time</span>
                                    <span className="payment-show__info-value">
                                        {new Date(transaction.created_at).toLocaleString()}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </GlassCard>

                    <GlassCard padding="p-6">
                        <div className="payment-show__info-card">
                            <div className="payment-show__info-header">
                                <User size={20} />
                                <h3>User Info</h3>
                            </div>
                            <div className="payment-show__info-items">
                                <div className="payment-show__info-item">
                                    <span className="payment-show__info-label">Name</span>
                                    <span className="payment-show__info-value">{transaction.user?.name || 'Unknown'}</span>
                                </div>
                                <div className="payment-show__info-item">
                                    <span className="payment-show__info-label">Email</span>
                                    <span className="payment-show__info-value">{transaction.user?.email}</span>
                                </div>
                                <div className="payment-show__info-item">
                                    <span className="payment-show__info-label">Phone</span>
                                    <span className="payment-show__info-value">{transaction.PhoneNumber}</span>
                                </div>
                                <div className="payment-show__info-item">
                                    <span className="payment-show__info-label">User ID</span>
                                    <span className="payment-show__info-value">#{transaction.user_id}</span>
                                </div>
                            </div>
                        </div>
                    </GlassCard>
                </div>

                <GlassCard padding="p-6">
                    <div className="payment-show__info-card">
                        <div className="payment-show__info-header">
                            <Hash size={20} />
                            <h3>M-Pesa Details</h3>
                        </div>
                        <div className="payment-show__info-grid">
                            <div className="payment-show__info-item">
                                <span className="payment-show__info-label">Merchant Request ID</span>
                                <span className="payment-show__info-value payment-show__info-value--mono">{transaction.MerchantRequestID}</span>
                            </div>
                            <div className="payment-show__info-item">
                                <span className="payment-show__info-label">Checkout Request ID</span>
                                <span className="payment-show__info-value payment-show__info-value--mono">{transaction.CheckoutRequestID}</span>
                            </div>
                            <div className="payment-show__info-item">
                                <span className="payment-show__info-label">Result Description</span>
                                <span className="payment-show__info-value">{transaction.ResultDesc || 'N/A'}</span>
                            </div>
                            <div className="payment-show__info-item">
                                <span className="payment-show__info-label">Transaction ID</span>
                                <span className="payment-show__info-value payment-show__info-value--mono">{transaction.id}</span>
                            </div>
                        </div>
                    </div>
                </GlassCard>
            </div>
        </PageShell>
    );
}
