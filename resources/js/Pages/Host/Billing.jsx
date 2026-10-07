import React, { useEffect, useRef, useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { CreditCard, Smartphone, CheckCircle2, AlertCircle, Loader2 } from 'lucide-react';
import './Billing.css';

const kes = (n) => `KES ${Number(n || 0).toLocaleString('en-KE')}`;

const formatDate = (value) => new Date(value).toLocaleDateString('en-KE', { day: 'numeric', month: 'short', year: 'numeric' });

/**
 * Plan picker + payment. Every price shown comes from the server's quote
 * (PlanPricing); changing the selection reloads just the quote.
 */
export default function Billing({ paystackPublicKey, billingLive, subscription, mpesaTransactions, plans, cycles, volumeDiscounts, extraDevicePrice, currentQuote, quote }) {
    const { auth } = usePage().props;
    const [selection, setSelection] = useState({
        plan: quote.plan,
        units: quote.units,
        extra_devices: quote.extra_devices,
        cycle: quote.cycle,
    });
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const timer = setTimeout(() => {
            router.reload({ only: ['quote'], data: selection, preserveState: true, preserveScroll: true, replace: true });
        }, 250);
        return () => clearTimeout(timer);
    }, [selection]);

    const update = (key, value) => setSelection((s) => ({ ...s, [key]: value }));
    const paidThrough = subscription?.ends_at;
    const isActive = paidThrough && new Date(paidThrough) > new Date();
    const discountTiers = Object.entries(volumeDiscounts).sort(([a], [b]) => a - b)
        .map(([units, percent]) => `${percent}% off from ${units} units`).join(', ');

    return (
        <DashboardLayout title="Billing & Subscription">
            <Head title="Billing" />

            <div className="host-billing-container">
                <div>
                    <h1 className="host-billing-heading">Billing &amp; Subscription</h1>
                    <p className="host-billing-subheading">One price per unit. Everything included. Pay by M-Pesa or card.</p>
                </div>

                <div className="host-billing-plan-card">
                    <div className="host-billing-plan-header">
                        <div>
                            <h2 className="host-billing-plan-title">Current plan</h2>
                            <p className="host-billing-plan-desc">
                                {currentQuote
                                    ? `${currentQuote.plan_name} · ${currentQuote.units} unit${currentQuote.units > 1 ? 's' : ''} · ${currentQuote.cycle_label}`
                                    : 'No plan yet'}
                                {paidThrough && (
                                    <span className={isActive ? 'host-billing-plan-through' : 'host-billing-plan-ending'}>
                                        {isActive ? `Paid through ${formatDate(paidThrough)}` : `Expired ${formatDate(paidThrough)}`}
                                    </span>
                                )}
                            </p>
                        </div>
                        <div className="host-billing-plan-badge">{isActive ? 'Active' : 'Inactive'}</div>
                    </div>
                </div>

                <div className="host-billing-plan-card">
                    <h2 className="host-billing-plan-title">{isActive ? 'Renew or change plan' : 'Choose your plan'}</h2>

                    <div className="host-billing-plans" role="radiogroup" aria-label="Plan">
                        {plans.map((plan) => (
                            <button
                                key={plan.id}
                                type="button"
                                role="radio"
                                aria-checked={selection.plan === plan.id}
                                onClick={() => update('plan', plan.id)}
                                className={`host-billing-plan-option ${selection.plan === plan.id ? 'is-selected' : ''}`}
                            >
                                <span className="host-billing-plan-option-name">{plan.name}</span>
                                <span className="host-billing-plan-option-price">{kes(plan.price)}</span>
                                <span className="host-billing-plan-option-unit">per unit / month</span>
                            </button>
                        ))}
                    </div>

                    <div className="host-billing-selection-grid">
                        <label>
                            <span className="host-billing-field-label">Units (rentals or locations)</span>
                            <input type="number" min="1" max="1000" className="host-billing-number" value={selection.units}
                                onChange={(e) => update('units', Math.max(1, parseInt(e.target.value, 10) || 1))} />
                            <small className="host-billing-hint">{discountTiers}</small>
                        </label>
                        <label>
                            <span className="host-billing-field-label">Extra devices at the same site</span>
                            <input type="number" min="0" max="100" className="host-billing-number" value={selection.extra_devices}
                                onChange={(e) => update('extra_devices', Math.max(0, parseInt(e.target.value, 10) || 0))} />
                            <small className="host-billing-hint">{kes(extraDevicePrice)} a month each</small>
                        </label>
                    </div>

                    <span className="host-billing-field-label">Pay</span>
                    <div className="host-billing-tabs">
                        {cycles.map((cycle) => (
                            <button key={cycle.id} type="button" onClick={() => update('cycle', cycle.id)}
                                className={`host-billing-tab ${selection.cycle === cycle.id ? 'host-billing-tab-active-card' : 'host-billing-tab-inactive'}`}>
                                {cycle.label}
                                {cycle.discount > 0 && ` · ${cycle.discount}% off`}
                                {cycle.free_months > 0 && ` · ${cycle.free_months} months free`}
                            </button>
                        ))}
                    </div>

                    <QuoteSummary quote={quote} />
                </div>

                <div className="host-billing-methods-grid">
                    <PaymentPanel quote={quote} selection={selection} user={auth.user} paystackPublicKey={paystackPublicKey} billingLive={billingLive} />
                    <Transactions transactions={mpesaTransactions} />
                </div>
            </div>
        </DashboardLayout>
    );
}

function QuoteSummary({ quote }) {
    const units = `${quote.units} unit${quote.units > 1 ? 's' : ''}`;

    return (
        <dl className="host-billing-summary">
            <div><dt>{quote.plan_name} × {units}</dt><dd>{kes(quote.unit_price * quote.units)}/mo</dd></div>
            {quote.volume_discount > 0 && <div><dt>Multi-unit discount</dt><dd>−{quote.volume_discount}%</dd></div>}
            {quote.extra_devices > 0 && <div><dt>Extra devices × {quote.extra_devices}</dt><dd>{kes(quote.extra_devices_total)}/mo</dd></div>}
            <div><dt>Monthly</dt><dd>{kes(quote.monthly_total)}</dd></div>
            {quote.cycle_discount > 0 && <div><dt>{quote.cycle_label} discount</dt><dd>−{quote.cycle_discount}%</dd></div>}
            {quote.free_months > 0 && <div><dt>Months free</dt><dd>{quote.free_months}</dd></div>}
            <div className="host-billing-summary-total">
                <dt>Due now for {quote.months} month{quote.months > 1 ? 's' : ''}</dt>
                <dd>{kes(quote.total)}</dd>
            </div>
        </dl>
    );
}

function PaymentPanel({ quote, selection, user, paystackPublicKey, billingLive }) {
    const [method, setMethod] = useState('mpesa');
    const [phone, setPhone] = useState(user.phone_number || '');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState({});
    const cardAvailable = !!paystackPublicKey;

    const post = (url, data, onSuccess) => {
        setProcessing(true);
        router.post(url, { ...selection, ...data }, {
            preserveScroll: true,
            onError: setErrors,
            onSuccess: () => { setErrors({}); onSuccess?.(); },
            onFinish: () => setProcessing(false),
        });
    };

    const payCard = () => {
        if (!window.PaystackPop) {
            notify.error('Card payments are still loading. Please try again.');
            return;
        }
        setProcessing(true);
        window.PaystackPop.setup({
            key: paystackPublicKey,
            email: user.email,
            amount: quote.total * 100,
            currency: quote.currency,
            ref: `TENAFI_${Date.now()}_${Math.random().toString(36).slice(2, 11)}`,
            metadata: { user_id: user.id, plan: quote.plan, units: quote.units, cycle: quote.cycle },
            callback: (response) => post(route('host.billing.paystack'), { reference: response.reference }),
            onClose: () => setProcessing(false),
        }).openIframe();
    };

    return (
        <div className="host-billing-payment-card">
            <h3 className="host-billing-payment-title">Payment method</h3>

            {cardAvailable && (
                <div className="host-billing-tabs">
                    <button type="button" onClick={() => setMethod('mpesa')}
                        className={`host-billing-tab ${method === 'mpesa' ? 'host-billing-tab-active-mpesa' : 'host-billing-tab-inactive'}`}>
                        M-Pesa
                    </button>
                    <button type="button" onClick={() => setMethod('card')}
                        className={`host-billing-tab ${method === 'card' ? 'host-billing-tab-active-card' : 'host-billing-tab-inactive'}`}>
                        Card
                    </button>
                </div>
            )}

            {method === 'mpesa' ? (
                <form className="host-billing-form" onSubmit={(e) => { e.preventDefault(); post(route('host.billing.mpesa'), { phone_number: phone }); }}>
                    <div>
                        <label className="host-billing-field-label" htmlFor="mpesa-phone">M-Pesa number</label>
                        <div className="host-billing-input-wrapper">
                            <Smartphone className="host-billing-input-icon" size={20} />
                            <input id="mpesa-phone" type="tel" inputMode="tel" value={phone} onChange={(e) => setPhone(e.target.value)}
                                className="host-billing-input" placeholder="0712 345 678" />
                        </div>
                        {errors.phone_number && <p className="host-billing-field-error">{errors.phone_number}</p>}
                    </div>
                    <button type="submit" disabled={processing} className="host-billing-mpesa-btn">
                        {processing ? <Loader2 className="animate-spin" size={20} /> : <Smartphone size={20} />}
                        Pay {kes(quote.total)}
                    </button>
                    <p className="host-billing-mpesa-text">You'll get an M-Pesa prompt on your phone</p>
                </form>
            ) : (
                <div className="host-billing-form">
                    <div className="host-billing-paystack-info">
                        <CreditCard size={32} className="host-billing-paystack-icon" />
                        <p className="host-billing-paystack-text">Pay securely by debit or credit card</p>
                    </div>
                    <button type="button" disabled={processing} onClick={payCard} className="host-billing-submit-btn">
                        {processing ? <Loader2 className="animate-spin" size={20} /> : <CreditCard size={20} />}
                        Pay {kes(quote.total)}
                    </button>
                    <p className="host-billing-secured-text">Secured by Paystack</p>
                </div>
            )}

            {!billingLive && (
                <div className="host-billing-simulate-section">
                    <button type="button" disabled={processing} onClick={() => post(route('host.billing.simulate'), {})} className="host-billing-simulate-btn">
                        Simulate a successful payment (billing is off)
                    </button>
                </div>
            )}
        </div>
    );
}

function Transactions({ transactions }) {
    const status = (tx) => (tx.Status === 'completed' ? 'completed' : tx.Status === 'pending' ? 'pending' : 'failed');
    const icons = { completed: <CheckCircle2 size={20} />, pending: <Loader2 size={20} className="animate-spin" />, failed: <AlertCircle size={20} /> };

    return (
        <div className="host-billing-tx-section">
            <h3 className="host-billing-tx-title">Recent payments</h3>
            <div className="host-billing-tx-list">
                {transactions.length > 0 ? transactions.map((tx) => (
                    <div key={tx.id} className="host-billing-tx-card">
                        <div className="host-billing-tx-info">
                            <div className={`host-billing-tx-status-icon host-billing-tx-status-${status(tx)}`}>{icons[status(tx)]}</div>
                            <div>
                                <p className="host-billing-tx-amount">{kes(tx.Amount)}</p>
                                <p className="host-billing-tx-date">
                                    {formatDate(tx.created_at)}
                                    {tx.meta && ` · ${tx.meta.plan_name}, ${tx.meta.months} mo`}
                                </p>
                            </div>
                        </div>
                        <span className={`host-billing-tx-status-badge host-billing-tx-status-${status(tx)}`}>{tx.Status}</span>
                    </div>
                )) : (
                    <div className="host-billing-empty">
                        <p className="host-billing-empty-text">No payments yet</p>
                    </div>
                )}
            </div>
        </div>
    );
}
