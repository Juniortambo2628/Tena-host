import React, { useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import { CreditCard, Smartphone, Receipt, CheckCircle2, Clock } from 'lucide-react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import { FormField, TextInput, TextArea } from '@/Components/Forms/FormPrimitives';
import { notify } from '@/Components/Toast';
import './EventPayment.css';

const kes = (n) => `KES ${Number(n || 0).toLocaleString()}`;

/**
 * Pay per event on an event's page: ask for a quote, wait for the price,
 * then pay it by M-Pesa or card (EventPaymentController). Until it's paid
 * the WiFi page shows the venue's usual page.
 */
export default function EventPayment({ event, payment }) {
    const { auth } = usePage().props;

    if (payment.status === 'paid') {
        return (
            <GlassCard padding="p-6">
                <div className="event-payment__head">
                    <CheckCircle2 size={20} className="text-green-700" aria-hidden="true" />
                    <div>
                        <h3>Event paid{payment.amount ? ` · ${kes(payment.amount)}` : ''}</h3>
                        <p>The event’s WiFi page is on for its dates.</p>
                    </div>
                </div>
            </GlassCard>
        );
    }

    return (
        <GlassCard padding="p-6">
            <div className="event-payment__head">
                <Receipt size={20} aria-hidden="true" />
                <div>
                    <h3>Pay for this event</h3>
                    <p>Without a monthly plan, each event is priced on its own. Until it’s paid, the WiFi page shows the venue’s usual page and nobody is added to this list.</p>
                </div>
            </div>
            {payment.status === 'quoted'
                ? <PayQuote event={event} payment={payment} user={auth.user} />
                : <RequestQuote event={event} payment={payment} />}
        </GlassCard>
    );
}

function RequestQuote({ event, payment }) {
    const form = useForm({ expected_attendees: payment.expected_attendees || '', request_note: payment.request_note || '' });
    const submit = (e) => {
        e.preventDefault();
        form.post(route('host.events.quote', event.id), { preserveScroll: true, onSuccess: () => notify.success('Quote requested') });
    };

    return (
        <form onSubmit={submit} className="event-payment__form">
            {payment.status === 'requested' && (
                <p className="event-payment__waiting"><Clock size={14} aria-hidden="true" /> Quote requested. We’ll send the price to your notifications shortly. You can update the details below.</p>
            )}
            <FormField label="Expected attendees" required error={form.errors.expected_attendees}>
                <TextInput id="quote-attendees" type="number" value={form.data.expected_attendees} onChange={(e) => form.setData('expected_attendees', e.target.value)} placeholder="e.g. 300" required />
            </FormField>
            <FormField label="Anything we should know (optional)" error={form.errors.request_note}>
                <TextArea id="quote-note" rows={2} value={form.data.request_note} onChange={(e) => form.setData('request_note', e.target.value)} placeholder="e.g. Two halls, sponsor on the WiFi page" />
            </FormField>
            <div>
                <PillButton variant="primary" type="submit" disabled={form.processing}>{payment.status === 'requested' ? 'Update request' : 'Request a quote'}</PillButton>
            </div>
        </form>
    );
}

function PayQuote({ event, payment, user }) {
    const [method, setMethod] = useState('mpesa');
    const [phone, setPhone] = useState(user.phone_number || '');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState({});

    const post = (url, data) => {
        setProcessing(true);
        router.post(url, data, { preserveScroll: true, onError: setErrors, onSuccess: () => setErrors({}), onFinish: () => setProcessing(false) });
    };

    const payCard = () => {
        if (!window.PaystackPop) {
            notify.error('Card payments are still loading. Please try again.');
            return;
        }
        setProcessing(true);
        window.PaystackPop.setup({
            key: payment.paystackPublicKey,
            email: user.email,
            amount: payment.amount * 100,
            currency: payment.currency,
            ref: `TENAFI_EVT_${event.id}_${Date.now()}`,
            metadata: { user_id: user.id, event_id: event.id },
            callback: (response) => post(route('host.events.pay.paystack', event.id), { reference: response.reference }),
            onClose: () => setProcessing(false),
        }).openIframe();
    };

    return (
        <div className="event-payment__pay">
            <div className="event-payment__price">
                <span>Price for {event.name}</span>
                <b>{kes(payment.amount)}</b>
                {payment.note && <small>{payment.note}</small>}
            </div>
            {!payment.canPay ? (
                <p className="event-payment__waiting">The account owner pays for events.</p>
            ) : (
                <div className="event-payment__methods">
                    {payment.paystackPublicKey && (
                        <div className="event-payment__tabs" role="tablist" aria-label="Payment method">
                            <button type="button" role="tab" aria-selected={method === 'mpesa'} className={method === 'mpesa' ? 'is-active' : ''} onClick={() => setMethod('mpesa')}>M-Pesa</button>
                            <button type="button" role="tab" aria-selected={method === 'card'} className={method === 'card' ? 'is-active' : ''} onClick={() => setMethod('card')}>Card</button>
                        </div>
                    )}
                    {method === 'mpesa' ? (
                        <form className="event-payment__form" onSubmit={(e) => { e.preventDefault(); post(route('host.events.pay.mpesa', event.id), { phone_number: phone }); }}>
                            <FormField label="M-Pesa number" required error={errors.phone_number}>
                                <TextInput id="event-mpesa-phone" type="tel" value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="0712 345 678" required />
                            </FormField>
                            <div>
                                <PillButton variant="primary" type="submit" disabled={processing} icon={<Smartphone size={16} />}>Pay {kes(payment.amount)}</PillButton>
                            </div>
                            <p className="event-payment__hint">You’ll get an M-Pesa prompt on your phone. This page updates once it’s paid; refresh if it hasn’t.</p>
                        </form>
                    ) : (
                        <div className="event-payment__form">
                            <div>
                                <PillButton variant="primary" onClick={payCard} disabled={processing} icon={<CreditCard size={16} />}>Pay {kes(payment.amount)} by card</PillButton>
                            </div>
                            <p className="event-payment__hint">Secured by Paystack.</p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
