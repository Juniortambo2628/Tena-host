import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Mail, ArrowRight, ChevronLeft } from 'lucide-react';
import AuthHero from '@/Components/Auth/AuthHero';
import './ForgotPassword.css';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <div className="forgot-root">
            <Head title="Forgot Password | Tena" />

            {/* Return to Login */}
            <Link
                href={route('login')}
                className="forgot-back-link"
            >
                <ChevronLeft size={14} />
                Back to Sign In
            </Link>

            {/* Left Column: Form */}
            <div className="forgot-left">
                <div className="forgot-left-inner">
                    {/* Logo & Header */}
                    <div className="forgot-logo-section">
                        <img
                            src="/legacy/assets/Tena-logo-square.jpg"
                            alt="Tena Logo"
                            className="forgot-logo-img"
                        />
                        <div className="forgot-title-area">
                            <h1 className="forgot-title">Recover Access</h1>
                            <p className="forgot-subtitle">
                                Forgot your password? No problem. Just let us know your email address and we'll send you a reset link.
                            </p>
                        </div>
                    </div>

                    {status && (
                        <div className="forgot-status">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="forgot-form">
                        <div className="forgot-field-group">
                            <label className="forgot-label">Email Address</label>
                            <div className="forgot-input-group">
                                <div className="forgot-input-icon">
                                    <Mail size={16} />
                                </div>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="forgot-input"
                                    placeholder="john@example.com"
                                    required
                                    autoFocus
                                />
                            </div>
                            {errors.email && <p className="forgot-error">{errors.email}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="forgot-submit"
                        >
                            <div className="forgot-submit-inner">
                                Send Reset Link
                                <ArrowRight size={16} />
                            </div>
                        </button>
                    </form>
                </div>
            </div>

            {/* Right Column: Hero Image (Same as Login for consistency) */}
            <AuthHero />
        </div>
    );
}
