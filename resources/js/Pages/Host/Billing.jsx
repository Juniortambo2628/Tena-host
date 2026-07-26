import React, { useState, useEffect } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { CreditCard, Smartphone, CheckCircle2, AlertCircle, Loader2 } from 'lucide-react';
import './Billing.css';

export default function Billing({ stripeKey, subscription, mpesaTransactions }) {
    const { auth } = usePage().props;
    const [activeTab, setActiveTab] = useState('card');
    const [stripe, setStripe] = useState(null);
    const [elements, setElements] = useState(null);
    const [cardError, setCardError] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Initialize Stripe
    useEffect(() => {
        if (window.Stripe && stripeKey) {
            const stripeInstance = window.Stripe(stripeKey);
            setStripe(stripeInstance);

            const elementsInstance = stripeInstance.elements();
            setElements(elementsInstance);

            const card = elementsInstance.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#000000',
                        '::placeholder': {
                            color: '#aab7c4',
                        },
                    },
                    invalid: {
                        color: '#ef4444',
                    },
                },
            });

            // Mount only if container exists (React render timing)
            // Using a timeout to ensure DOM is ready or checking refs would be better
            // But for now we'll check inside the tab render or re-init when tab changes
        }
    }, [stripeKey]);

    useEffect(() => {
        if (activeTab === 'card' && elements) {
            const card = elements.create('card');
            try {
                card.mount('#card-element');
            } catch (e) { /* already mounted or container missing */ }
            return () => {
                try { card.destroy(); } catch (e) { }
            };
        }
    }, [activeTab, elements]);


    const { data: mpesaData, setData: setMpesaData, post: postMpesa, processing: mpesaProcessing, errors: mpesaErrors } = useForm({
        phone_number: auth.user.phone_number || '',
        amount: 6500, // KES equivalent approx
    });

    const handleCardPayment = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setCardError(null);

        if (!stripe || !elements) {
            setProcessing(false);
            return;
        }

        const cardElement = elements.getElement('card');

        const { error, paymentMethod } = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: `${auth.user.first_name} ${auth.user.last_name}`,
                email: auth.user.email,
            },
        });

        if (error) {
            setCardError(error.message);
            setProcessing(false);
        } else {
            // Send paymentMethod.id to backend
            router.post(route('host.billing.stripe'), {
                payment_method: paymentMethod.id,
                plan_id: 'pro-host-monthly',
            }, {
                onFinish: () => setProcessing(false),
            });
        }
    };

    const submitMpesa = (e) => {
        e.preventDefault();
        postMpesa(route('host.billing.mpesa'));
    };

    return (
        <DashboardLayout title="Billing & Subscription">
            <Head title="Billing" />

            <div className="host-billing-container">
                {/* Header */}
                <div>
                    <h1 className="host-billing-heading">Billing & Subscription</h1>
                    <p className="host-billing-subheading">Manage your subscription and payment methods.</p>
                </div>

                {/* Subscription Status Card */}
                <div className="host-billing-plan-card">
                    <div className="host-billing-plan-header">
                        <div>
                            <h2 className="host-billing-plan-title">Current Plan</h2>
                            <p className="host-billing-plan-desc">
                                {subscription ? 'Pro Host Plan' : 'Free Trial'}
                                {subscription && subscription.ends_at && <span className="host-billing-plan-ending">(Ending soon)</span>}
                            </p>
                        </div>
                        <div className="host-billing-plan-badge">
                            {subscription ? 'Active' : 'Inactive'}
                        </div>
                    </div>
                </div>

                {/* Payment Methods */}
                <div className="host-billing-methods-grid">
                    {/* Payment Form */}
                    <div className="host-billing-payment-card">
                        <h3 className="host-billing-payment-title">Payment Method</h3>

                        {/* Tabs */}
                        <div className="host-billing-tabs">
                            <button
                                onClick={() => setActiveTab('card')}
                                className={`host-billing-tab ${activeTab === 'card' ? 'host-billing-tab-active-card' : 'host-billing-tab-inactive'}`}
                            >
                                Credit Card
                            </button>
                            <button
                                onClick={() => setActiveTab('mpesa')}
                                className={`host-billing-tab ${activeTab === 'mpesa' ? 'host-billing-tab-active-mpesa' : 'host-billing-tab-inactive'}`}
                            >
                                M-Pesa
                            </button>
                        </div>

                        {activeTab === 'card' ? (
                            <form onSubmit={handleCardPayment} className="host-billing-form">
                                <div className="host-billing-card-element">
                                    <div id="card-element" className="host-billing-card-element-inner" />
                                </div>
                                {cardError && <p className="host-billing-error">{cardError}</p>}

                                <button
                                    type="submit"
                                    disabled={!stripe || processing}
                                    className="host-billing-submit-btn"
                                >
                                    {processing ? <Loader2 className="animate-spin" size={20} /> : <CreditCard size={20} />}
                                    Subscribe ($49.99/mo)
                                </button>
                                <p className="host-billing-secured-text">Secured by Stripe</p>
                            </form>
                        ) : (
                            <form onSubmit={submitMpesa} className="host-billing-form">
                                <div>
                                    <label className="host-billing-field-label">Phone Number</label>
                                    <div className="host-billing-input-wrapper">
                                        <Smartphone className="host-billing-input-icon" size={20} />
                                        <input
                                            type="text"
                                            value={mpesaData.phone_number}
                                            onChange={e => setMpesaData('phone_number', e.target.value)}
                                            className="host-billing-input"
                                            placeholder="0712345678"
                                        />
                                    </div>
                                    {mpesaErrors.phone_number && <p className="host-billing-field-error">{mpesaErrors.phone_number}</p>}
                                </div>

                                <button
                                    type="submit"
                                    disabled={mpesaProcessing}
                                    className="host-billing-mpesa-btn"
                                >
                                    {mpesaProcessing ? <Loader2 className="animate-spin" size={20} /> : <Smartphone size={20} />}
                                    Pay KES {mpesaData.amount}
                                </button>
                                <p className="host-billing-mpesa-text">Lipa na M-Pesa Online</p>
                            </form>
                        )}

                        {/* Simulation Button (Dev Only) */}
                        <div className="host-billing-simulate-section">
                            <button
                                type="button"
                                onClick={() => router.post(route('host.billing.simulate'))}
                                className="host-billing-simulate-btn"
                            >
                                (Dev) Simulate M-Pesa Payment Success
                            </button>
                        </div>

                    </div>

                    {/* Transaction History (M-Pesa) */}
                    <div className="host-billing-tx-section">
                        <h3 className="host-billing-tx-title">Recent M-Pesa Transactions</h3>
                        <div className="host-billing-tx-list">
                            {mpesaTransactions.length > 0 ? mpesaTransactions.map((tx) => (
                                <div key={tx.id} className="host-billing-tx-card">
                                    <div className="host-billing-tx-info">
                                        <div className={`host-billing-tx-status-icon ${tx.Status === 'completed' ? 'host-billing-tx-status-completed' :
                                            tx.Status === 'pending' ? 'host-billing-tx-status-pending' : 'host-billing-tx-status-failed'
                                            }`}>
                                            {tx.Status === 'completed' ? <CheckCircle2 size={20} /> :
                                                tx.Status === 'pending' ? <Loader2 size={20} className="animate-spin" /> : <AlertCircle size={20} />}
                                        </div>
                                        <div>
                                            <p className="host-billing-tx-amount">KES {tx.Amount}</p>
                                            <p className="host-billing-tx-date">{new Date(tx.created_at).toLocaleDateString()}</p>
                                        </div>
                                    </div>
                                    <span className={`host-billing-tx-status-badge ${tx.Status === 'completed' ? 'host-billing-tx-status-completed' :
                                        tx.Status === 'pending' ? 'host-billing-tx-status-pending' : 'host-billing-tx-status-failed'
                                        }`}>
                                        {tx.Status}
                                    </span>
                                </div>
                            )) : (
                                <div className="host-billing-empty">
                                    <p className="host-billing-empty-text">No recent transactions</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
