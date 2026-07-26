import React from 'react';
import { Shield, ShieldAlert, CheckCircle2 } from 'lucide-react';
import { router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import './TwoFactorAuthenticationForm.css';

export default function TwoFactorAuthenticationForm({ className = '', enabled = false, userEmail = 'user@example.com' }) {
    const [showConfirm, setShowConfirm] = React.useState(false);
    const [processing, setProcessing] = React.useState(false);

    const toggle2FA = () => {
        if (!enabled) {
            setShowConfirm(true);
        } else {
            // Disable 2FA via backend
            setProcessing(true);
            router.post(route('profile.two-factor.toggle'), {}, {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            });
        }
    };

    const confirmSetup = () => {
        // Enable 2FA via backend
        setProcessing(true);
        router.post(route('profile.two-factor.toggle'), {}, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setShowConfirm(false);
            },
        });
    };

    // Build the QR code URL with dynamic user email
    const qrCodeData = encodeURIComponent(`otpauth://totp/Tena:${userEmail}?secret=JBSWY3DPEHPK3PXP&issuer=Tena`);
    const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrCodeData}`;

    return (
        <section className={className}>
            <header className="twofa-header">
                <div className="twofa-header-title">
                    <Shield size={20} />
                    <h2>Two-Factor Authentication</h2>
                </div>
                <p className="twofa-header-desc">
                    Add an extra layer of security to your account by requiring more than just a password to log in.
                </p>
            </header>

            <div className="twofa-content">
                <div className="twofa-status-card">
                    <div className="twofa-status-info">
                        <div className={`twofa-status-icon ${enabled ? 'twofa-status-icon-enabled' : 'twofa-status-icon-disabled'}`}>
                            {enabled ? <CheckCircle2 size={24} /> : <ShieldAlert size={24} />}
                        </div>
                        <div>
                            <p className="twofa-status-label">
                                Status: {enabled ? 'Enabled' : 'Disabled'}
                            </p>
                            <p className="twofa-status-desc">
                                {enabled ? 'Your account is protected with 2FA.' : 'Enable 2FA for enhanced security.'}
                            </p>
                        </div>
                    </div>

                    <button
                        onClick={toggle2FA}
                        disabled={processing}
                        className={`twofa-toggle ${enabled ? 'twofa-toggle-enabled' : 'twofa-toggle-disabled'}`}
                    >
                        <span
                            className={`twofa-toggle-knob ${enabled ? 'twofa-toggle-knob-enabled' : 'twofa-toggle-knob-disabled'}`}
                        />
                    </button>
                </div>

                {showConfirm && (
                    <motion.div
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="twofa-setup-card"
                    >
                        <div className="twofa-setup-header">
                            <h3 className="twofa-setup-title">Setup 2FA</h3>
                            <p className="twofa-setup-desc">Scan this QR code with your authenticator app.</p>
                        </div>

                        <div className="twofa-qr-container">
                            <img
                                src={qrCodeUrl}
                                alt="QR Code"
                                className="w-40 h-40"
                            />
                        </div>

                        <div className="twofa-verification">
                            <div className="twofa-verification-field">
                                <label className="twofa-verification-label">Verification Code</label>
                                <input
                                    type="text"
                                    placeholder="000 000"
                                    className="twofa-verification-input"
                                />
                            </div>

                            <div className="twofa-setup-actions">
                                <button
                                    onClick={() => setShowConfirm(false)}
                                    className="twofa-cancel-btn"
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={confirmSetup}
                                    disabled={processing}
                                    className="twofa-confirm-btn"
                                >
                                    {processing ? 'Saving...' : 'Verify & Enable'}
                                </button>
                            </div>
                        </div>
                    </motion.div>
                )}
            </div>
        </section>
    );
}
