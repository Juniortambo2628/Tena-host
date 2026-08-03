import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
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

export default function GuestPortal({ property, amenities, guidebook_link }) {
    const { post, processing } = useForm();

    const orderAmenity = (amenityId) => {
        post(route('guest.orders.store'), { data: { amenity_id: amenityId }, preserveScroll: true });
    };

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
                        <img
                            src="/legacy/assets/Tena-logo-square.jpg"
                            alt="Tena"
                        />
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
                            <p className="guest-portal-wifi-name">{property.access_points?.[0]?.ssid || 'Tena-Secure-WiFi'}</p>
                        </div>
                        <button
                            onClick={() => {
                                navigator.clipboard.writeText(property.access_points?.[0]?.ssid || property.wifi_ssid || 'Tena-Secure-WiFi');
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
                                            <span className="guest-portal-amenity-price">${parseFloat(amenity.price).toFixed(2)}</span>
                                        )}
                                    </div>
                                </div>
                                {amenity.price > 0 ? (
                                    <button
                                        onClick={() => orderAmenity(amenity.id)}
                                        disabled={processing}
                                        className="guest-portal-amenity-order-btn"
                                    >
                                        Order
                                    </button>
                                ) : (
                                    <ArrowRight size={14} className="text-black/10 group-hover:text-black/30" />
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                <div className="guest-portal-footer">
                    <div className="guest-portal-footer-badge">
                        <ShieldCheck size={14} className="text-[#FFD300]" />
                        <span className="guest-portal-footer-label">Secure Connection Powered by</span>
                    </div>
                    <div className="guest-portal-footer-brand">
                        <img src="/legacy/assets/Tena-logo-square.jpg" alt="Tena" />
                        <span className="guest-portal-footer-brand-name">TENA</span>
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
