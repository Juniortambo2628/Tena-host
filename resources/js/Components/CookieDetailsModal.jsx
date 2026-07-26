import React from 'react';
import GlassModal from './GlassModal';
import { Cookie, ShieldCheck, BarChart3, Settings } from 'lucide-react';
import './CookieDetailsModal.css';

export default function CookieDetailsModal({ isOpen, onClose }) {
    const cookieTypes = [
        {
            icon: <ShieldCheck size={18} />,
            title: "Essential Cookies",
            desc: "Necessary for the platform to function. These include session management, CSRF protection, and authentication tokens.",
            cookies: ["XSRF-TOKEN", "tena_session"]
        },
        {
            icon: <BarChart3 size={18} />,
            title: "Analytics Cookies",
            desc: "Help us understand how users interact with the platform so we can improve the experience.",
            cookies: ["_ga", "_gid"]
        },
        {
            icon: <Settings size={18} />,
            title: "Preference Cookies",
            desc: "Used to remember your settings, such as theme preferences or dashboard sidebar state.",
            cookies: ["sidebar_state", "theme_preference"]
        }
    ];

    return (
        <GlassModal
            isOpen={isOpen}
            onClose={onClose}
            title="Cookie Directory"
            maxWidth="2xl"
        >
            <div className="cookie-details-modal__content">
                <div className="cookie-details-modal__header">
                    <Cookie size={32} className="cookie-details-modal__header-icon" />
                    <div>
                        <h3 className="cookie-details-modal__heading">Cookie Usage</h3>
                        <p className="cookie-details-modal__subtitle">Transparency in our tracking</p>
                    </div>
                </div>

                <div className="cookie-details-modal__list">
                    {cookieTypes.map((type, idx) => (
                        <div key={idx} className="cookie-details-modal__item group">
                            <div className="cookie-details-modal__icon">
                                {type.icon}
                            </div>
                            <div className="cookie-details-modal__details">
                                <h4 className="cookie-details-modal__title">{type.title}</h4>
                                <p className="cookie-details-modal__description">{type.desc}</p>
                                <div className="cookie-details-modal__tags">
                                    {type.cookies.map(c => (
                                        <span key={c} className="cookie-details-modal__tag">
                                            {c}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="cookie-details-modal__footer">
                    <p className="cookie-details-modal__footer-text">
                        You can manage these cookies in your browser settings at any time.
                    </p>
                </div>
            </div>
        </GlassModal>
    );
}
