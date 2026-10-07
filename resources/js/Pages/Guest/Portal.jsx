import React, { useEffect, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import {
    Wifi,
    Smartphone,
    BookOpen,
    Coffee,
    ShieldCheck,
    MapPin,
    ArrowRight
} from 'lucide-react';
import './Portal.css';
import BrandLogo from '@/Components/BrandLogo';

const kes = (n) => `KES ${Number(n || 0).toLocaleString('en-KE')}`;

const PAYMENT_LABELS = {
    pending: 'Waiting for M-Pesa',
    paid: 'Paid',
    failed: 'Payment failed',
    unpaid: 'Pay your host',
    not_required: 'Ordered',
};

export default function GuestPortal({ property, amenities, guidebook_link, orders = [], guestPhone = '', mpesaEnabled = false }) {
    const { data, setData, post, processing, errors, transform } = useForm({ amenity_id: null, phone: guestPhone || '' });
    const [payingFor, setPayingFor] = useState(null);

    const order = (amenity) => {
        // Priced extras are paid by M-Pesa: ask for the number first.
        if (amenity.price > 0 && mpesaEnabled && payingFor?.id !== amenity.id) {
            setPayingFor(amenity);
            return;
        }
        transform((d) => ({ ...d, amenity_id: amenity.id }));
        post(route('guest.orders.store'), {
            preserveScroll: true,
            onSuccess: () => setPayingFor(null),
        });
    };

    // While a payment is waiting on the guest's PIN, refresh the order list.
    const waiting = orders.some((o) => o.payment_status === 'pending');
    useEffect(() => {
        if (!waiting) return undefined;
        const timer = setInterval(() => router.reload({ only: ['orders'] }), 5000);
        return () => clearInterval(timer);
    }, [waiting]);

    return (
        <div className="guest-portal-page">
            <Head title={`Welcome to ${property.name}`} />

            <div className="guest-portal-hero">
                <img
                    src={property.splash_image_path || "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80"}
                    alt={property.name}
                />
                <div className="guest-portal-hero-overlay"></div>

                <div className="guest-portal-logo-overlay">
                    <div className="guest-portal-logo-box">
                        <BrandLogo />
                    </div>
                </div>
            </div>

            <div className="guest-portal-content">
                <div className="guest-portal-property-card">
                    <span className="guest-portal-welcome-label">Welcome Guest</span>
                    <h1 className="guest-portal-property-name">{property.name}</h1>
                    <div className="guest-portal-property-address">
                        <MapPin size={14} />
                        <span>{property.address || 'Address hidden for privacy'}</span>
                    </div>

                    <div className="guest-portal-wifi-card">
                        <div className="guest-portal-wifi-icon">
                            <Wifi size={20} />
                        </div>
                        <div className="guest-portal-wifi-info">
                            <p className="guest-portal-wifi-label">WiFi Access</p>
                            <p className="guest-portal-wifi-name">{property.access_points?.[0]?.ssid || property.wifi_ssid || 'Guest WiFi'}</p>
                        </div>
                        <button
                            onClick={() => {
                                navigator.clipboard.writeText(property.access_points?.[0]?.ssid || property.wifi_ssid || 'Guest WiFi');
                                notify.success('WiFi network name copied to clipboard!');
                            }}
                            className="guest-portal-wifi-connect"
                        >
                            Connect
                        </button>
                    </div>
                </div>

                <div className="guest-portal-quick-info">
                    <a
                        href={guidebook_link}
                        className="guest-portal-info-card"
                    >
                        <div className="guest-portal-info-icon">
                            <BookOpen size={20} className="text-black/40 group-hover:text-black" />
                        </div>
                        <p className="guest-portal-info-label">Guidebook</p>
                        <p className="guest-portal-info-title">House Rules & Local Tips</p>
                    </a>

                    <div
                        onClick={() => {
                            const phone = property.host?.phone_number || property.host?.email;
                            if (phone) {
                                window.open(`https://wa.me/${phone.replace(/[^0-9]/g, '')}`, '_blank');
                            }
                        }}
                        className="guest-portal-info-card guest-portal-info-card-clickable"
                    >
                        <div className="guest-portal-info-icon">
                            <Smartphone size={20} className="text-black/40" />
                        </div>
                        <p className="guest-portal-info-label">Support</p>
                        <p className="guest-portal-info-title">Text Your Host Directly</p>
                    </div>
                </div>

                <div className="guest-portal-amenities">
                    <h3 className="guest-portal-amenities-title">Essential Amenities</h3>
                    <div className="guest-portal-amenities-list">
                        {amenities.map((amenity, idx) => (
                            <div key={idx} className="guest-portal-amenity-item">
                                <div className="flex items-center gap-4">
                                    <div className="guest-portal-amenity-icon">
                                        {amenity.icon === 'wifi' && <Wifi size={16} />}
                                        {amenity.icon === 'monitor' && <Smartphone size={16} />}
                                        {amenity.icon === 'coffee' && <Coffee size={16} />}
                                        {amenity.icon === 'droplet' && <ShieldCheck size={16} />}
                                    </div>
                                    <div>
                                        <span className="guest-portal-amenity-name">{amenity.name}</span>
                                        {amenity.price > 0 && (
                                            <span className="guest-portal-amenity-price">{kes(amenity.price)}</span>
                                        )}
                                    </div>
                                </div>
                                {amenity.price > 0 ? (
                                    <button
                                        onClick={() => order(amenity)}
                                        disabled={processing}
                                        className="guest-portal-amenity-order-btn"
                                    >
                                        {payingFor?.id === amenity.id ? `Pay ${kes(amenity.price)}` : 'Order'}
                                    </button>
                                ) : (
                                    <ArrowRight size={14} className="text-black/10 group-hover:text-black/30" />
                                )}
                            </div>
                        ))}
                    </div>

                    {payingFor && (
                        <div className="guest-portal-pay">
                            <label htmlFor="mpesa-phone">M-Pesa number for {payingFor.name}</label>
                            <input
                                id="mpesa-phone"
                                type="tel"
                                inputMode="tel"
                                placeholder="0712 345 678"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                            />
                            {errors.phone && <p className="guest-portal-pay-error">{errors.phone}</p>}
                            <p className="guest-portal-pay-hint">Tap “Pay {kes(payingFor.price)}” above, then enter your M-Pesa PIN on your phone.</p>
                        </div>
                    )}

                    {orders.length > 0 && (
                        <div className="guest-portal-orders">
                            <h4>Your orders</h4>
                            {orders.map((o) => (
                                <div key={o.id} className="guest-portal-order">
                                    <span>{o.amenity?.name} · {kes(o.total)}</span>
                                    <span className={`guest-portal-order-status is-${o.payment_status}`}>{PAYMENT_LABELS[o.payment_status] || o.payment_status}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="guest-portal-footer">
                    <div className="guest-portal-footer-badge">
                        <ShieldCheck size={14} className="text-[#FFD300]" />
                        <span className="guest-portal-footer-label">Secure Connection Powered by</span>
                    </div>
                    <div className="guest-portal-footer-brand">
                        <BrandLogo />
                    </div>
                </div>
            </div>

            <style dangerouslySetInnerHTML={{
                __html: `
                @keyframes slideUp {
                    from { transform: translateY(30px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                .animate-slide-up {
                    animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                }
            `}} />
        </div>
    );
}
