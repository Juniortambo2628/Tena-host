import React, { useState } from 'react';
import { Shield, ShieldAlert, CheckCircle2, Copy, Check } from 'lucide-react';
import { router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { notify } from '@/Components/Toast';
import './TwoFactorAuthenticationForm.css';

export default function TwoFactorAuthenticationForm({
    className = '',
    enabled = false,
    secret = null,
    qrCodeUrl = null,
    recoveryCodes = null,
    userEmail = 'user@example.com',
}) {
    const [showConfirm, setShowConfirm] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [code, setCode] = useState('');
    const [showRecovery, setShowRecovery] = useState(false);
    const [copied, setCopied] = useState(false);

    const toggle2FA = () => {
        if (!enabled) {
            setShowConfirm(true);
        } else {
            setProcessing(true);
            router.post(route('profile.two-factor.disable'), {}, {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
                onSuccess: () => notify.success('Two-factor authentication disabled'),
                onError: () => notify.error('Failed to disable two-factor authentication'),
            });
        }
    };

    const confirmSetup = () => {
        if (code.length !== 6) {
            notify.error('Please enter a 6-digit verification code.');
            return;
        }
        setProcessing(true);
        router.post(route('profile.two-factor.confirm'), {
            code,
            secret,
        }, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setShowConfirm(false);
                setCode('');
            },
            onSuccess: () => notify.success('Two-factor authentication enabled'),
            onError: () => notify.error('Invalid verification code. Please try again.'),
        });
    };

    const copyRecoveryCodes = () => {
        if (recoveryCodes) {
            navigator.clipboard.writeText(recoveryCodes.join('\n'));
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const displayQrUrl = qrCodeUrl || (() => {
        const data = encodeURIComponent(`otpauth://totp/Tena:${userEmail}?secret=${secret || 'JBSWY3DPEHPK3PXP'}&issuer=Tena`);
        return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${data}`;
    })();

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
                                src={displayQrUrl}
                                alt="QR Code"
                                className="w-40 h-40"
                            />
                        </div>

                        <div className="twofa-manual-secret">
                            <p className="twofa-manual-label">Or enter this key manually:</p>
                            <code className="twofa-manual-code">{secret || 'JBSWY3DPEHPK3PXP'}</code>
                        </div>

                        <div className="twofa-verification">
                            <div className="twofa-verification-field">
                                <label className="twofa-verification-label">Verification Code</label>
                                <input
                                    type="text"
                                    placeholder="000 000"
                                    value={code}
                                    onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                                    className="twofa-verification-input"
                                    autoFocus
                                />
                            </div>

                            <div className="twofa-setup-actions">
                                <button
                                    onClick={() => { setShowConfirm(false); setCode(''); }}
                                    className="twofa-cancel-btn"
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={confirmSetup}
                                    disabled={processing || code.length !== 6}
                                    className="twofa-confirm-btn"
                                >
                                    {processing ? 'Saving...' : 'Verify & Enable'}
                                </button>
                            </div>
                        </div>
                    </motion.div>
                )}

                {enabled && recoveryCodes && (
                    <div className="twofa-recovery-section">
                        <button
                            onClick={() => setShowRecovery(!showRecovery)}
                            className="twofa-recovery-toggle"
                        >
                            {showRecovery ? 'Hide' : 'Show'} Recovery Codes
                        </button>
                        {showRecovery && (
                            <motion.div
                                initial={{ opacity: 0, height: 0 }}
                                animate={{ opacity: 1, height: 'auto' }}
                                className="twofa-recovery-card"
                            >
                                <div className="twofa-recovery-header">
                                    <p className="twofa-recovery-desc">
                                        Save these recovery codes in a safe place. Each code can only be used once.
                                    </p>
                                    <button onClick={copyRecoveryCodes} className="twofa-recovery-copy">
                                        {copied ? <Check size={14} /> : <Copy size={14} />}
                                        {copied ? 'Copied' : 'Copy'}
                                    </button>
                                </div>
                                <div className="twofa-recovery-grid">
                                    {recoveryCodes.map((rc, i) => (
                                        <code key={i} className="twofa-recovery-code">{rc}</code>
                                    ))}
                                </div>
                            </motion.div>
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}
