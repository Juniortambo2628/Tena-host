import React from 'react';
import { Head } from '@inertiajs/react';
import './Guidebook.css';

export default function Guidebook({ property, amenities = [] }) {
    const modules = [
        { name: 'Guidebook', icon: 'fa-book-open', desc: 'Everything you need to know about the home' },
        { name: 'Book Again', icon: 'fa-calendar-alt', desc: 'Schedule your future stay.' },
        { name: 'Visit our Website', icon: 'fa-globe', desc: 'Learn most about our brand, our mission and our properties.' },
        { name: 'Shop Amenities', icon: 'fa-shopping-cart', desc: 'Late-checkout Massage Local Chefs and more Add services curated by your host.' },
    ];

    return (
        <div className="guidebook-page">
            <Head title={`Guidebook - ${property.name}`} />

            <div className="guidebook-header">
                <img
                    src="/legacy/assets/Tena-logo-square.jpg"
                    alt="TENA Logo"
                    className="guidebook-logo"
                />
                <p className="guidebook-label">Property Hub</p>
            </div>

            <div className="guidebook-feature-card">
                <div className="guidebook-feature-image">
                    <img
                        src={property.splash_image_path || "https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=800&q=80"}
                        alt={property.name}
                    />
                    <div className="guidebook-feature-info">
                        <div className="guidebook-feature-info-box">
                            <h2 className="guidebook-feature-name">{property.name}</h2>
                            <p className="guidebook-feature-address">{property.address || 'Address hidden'}</p>
                        </div>
                    </div>
                </div>
            </div>

            {amenities.length > 0 && (
                <div className="guidebook-amenities">
                    <h3 className="guidebook-amenities-title">Amenities</h3>
                    <div className="guidebook-amenities-list">
                        {amenities.map((amenity) => (
                            <div key={amenity.id} className="guidebook-amenity-item">
                                <div className="guidebook-amenity-icon">
                                    <i className="fas fa-check text-sm"></i>
                                </div>
                                <span className="guidebook-amenity-name">{amenity.name}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="guidebook-modules">
                {modules.map((module) => (
                    <button key={module.name} className="guidebook-module-btn">
                        <div className="guidebook-module-icon">
                            <i className={`fas ${module.icon} text-lg`}></i>
                        </div>
                        <div>
                            <h3 className="guidebook-module-name">{module.name}</h3>
                            <p className="guidebook-module-desc">{module.desc}</p>
                        </div>
                    </button>
                ))}
            </div>

            <div className="guidebook-footer">
                Powered by Tena
            </div>
        </div>
    );
}
