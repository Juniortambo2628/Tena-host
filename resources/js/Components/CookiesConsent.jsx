import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Cookie, X } from 'lucide-react';
import PillButton from './Dashboard/PillButton';
import './CookiesConsent.css';

export default function CookiesConsent({ onOpenPrivacy, onOpenCookieDetails }) {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const consent = localStorage.getItem('cookies-consent');
        if (!consent) {
            const timer = setTimeout(() => setIsVisible(true), 1500);
            return () => clearTimeout(timer);
        }
    }, []);

    const handleAccept = () => {
        localStorage.setItem('cookies-consent', 'accepted');
        setIsVisible(false);
    };

    const handleDecline = () => {
        localStorage.setItem('cookies-consent', 'declined');
        setIsVisible(false);
    };

    return (
        <AnimatePresence>
            {isVisible && (
                <motion.div
                    initial={{ y: 100, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    exit={{ y: 100, opacity: 0 }}
                    className="cookies-consent"
                >
                    <div className="cookies-consent__card">
                        <div className="cookies-consent__header">
                            <div className="cookies-consent__icon">
                                <Cookie size={24} className="cookies-consent__cookie-icon" />
                            </div>
                            <div className="cookies-consent__content">
                                <h3 className="cookies-consent__title">Cookies & Privacy</h3>
                                <p className="cookies-consent__text">
                                    We use cookies to enhance your dashboard experience and analyze our traffic. Clicking accept helps us improve your experience.
                                </p>
                                <div className="cookies-consent__links">
                                    <button
                                        onClick={onOpenPrivacy}
                                        className="cookies-consent__link"
                                    >
                                        Privacy Policy
                                    </button>
                                    <button
                                        onClick={onOpenCookieDetails}
                                        className="cookies-consent__link"
                                    >
                                        Which cookies are collected?
                                    </button>
                                </div>
                            </div>
                            <button
                                onClick={() => setIsVisible(false)}
                                className="cookies-consent__close"
                            >
                                <X size={16} />
                            </button>
                        </div>

                        <div className="cookies-consent__actions">
                            <button
                                onClick={handleAccept}
                                className="cookies-consent__accept"
                            >
                                Accept All
                            </button>
                            <button
                                onClick={handleDecline}
                                className="cookies-consent__decline"
                            >
                                Deny
                            </button>
                        </div>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
