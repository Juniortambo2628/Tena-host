import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Mail, ArrowRight, ChevronLeft } from 'lucide-react';
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
            <div className="forgot-right">
                <div className="forgot-right-inner">
                    <img
                        src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&q=80&w=2000"
                        alt="Join the future"
                        className="forgot-right-img"
                    />
                    <div className="forgot-right-gradient" />

                    <div className="forgot-right-bottom">
                        <img src="/legacy/assets/Tena-logo-square.jpg" className="forgot-right-logo" />
                        <h2 className="forgot-right-title">Security first.</h2>
                        <p className="forgot-right-desc">
                            We take your data security seriously. Follow the instructions to safely recover your account.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
