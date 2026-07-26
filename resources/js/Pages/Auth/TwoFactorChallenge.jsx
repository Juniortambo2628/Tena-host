import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Shield, ArrowRight, ChevronLeft } from 'lucide-react';
import './TwoFactorChallenge.css';

export default function TwoFactorChallenge({ email }) {
    const { data, setData, post, processing, errors } = useForm({
        code: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('two-factor.verify'));
    };

    return (
        <div className="twofa-root">
            <Head title="Two-Factor Authentication | Tena" />

            {/* Return to Login */}
            <Link
                href={route('login')}
                className="twofa-back-link"
            >
                <ChevronLeft size={14} />
                Back to Sign In
            </Link>

            {/* Left Column: 2FA Form */}
            <div className="twofa-left">
                <div className="twofa-left-inner">
                    {/* Logo & Header */}
                    <div className="twofa-logo-section">
                        <div className="twofa-logo-icon">
                            <Shield size={24} className="text-white" />
                        </div>
                        <div className="twofa-title-area">
                            <h1 className="twofa-title">Verify it's you</h1>
                            <p className="twofa-subtitle">
                                Enter the 6-digit code from your authenticator app to complete sign in.
                            </p>
                        </div>
                    </div>

                    {email && (
                        <div className="twofa-email-box">
                            <p className="twofa-email-label">Signing in as</p>
                            <p className="twofa-email-value">{email}</p>
                        </div>
                    )}

                    <form onSubmit={submit} className="twofa-form">
                        <div className="twofa-field-group">
                            <label className="twofa-label">Verification Code</label>
                            <input
                                type="text"
                                inputMode="numeric"
                                pattern="[0-9]*"
                                maxLength={6}
                                value={data.code}
                                onChange={(e) => setData('code', e.target.value.replace(/\D/g, '').slice(0, 6))}
                                className="twofa-input"
                                placeholder="000000"
                                autoFocus
                                autoComplete="one-time-code"
                            />
                            {errors.code && <p className="twofa-error">{errors.code}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={processing || data.code.length !== 6}
                            className="twofa-submit"
                        >
                            <div className="twofa-submit-inner">
                                {processing ? 'Verifying...' : 'Verify & Continue'}
                                <ArrowRight size={16} />
                            </div>
                        </button>

                        <p className="twofa-help-text">
                            Lost access to your authenticator? <button type="button" className="twofa-help-link">Get help</button>
                        </p>
                    </form>
                </div>
            </div>

            {/* Right Column: Hero Image */}
            <div className="twofa-right">
                <div className="twofa-right-inner">
                    <img
                        src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&q=80&w=2000"
                        alt="Security First"
                        className="twofa-right-img"
                    />
                    <div className="twofa-right-gradient" />

                    <div className="twofa-right-bottom">
                        <img src="/legacy/assets/Tena-logo-square.jpg" className="twofa-right-logo" />
                        <h2 className="twofa-right-title">Extra secure.</h2>
                        <p className="twofa-right-desc">
                            Two-factor authentication adds an extra layer of protection to ensure only you can access your account.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
