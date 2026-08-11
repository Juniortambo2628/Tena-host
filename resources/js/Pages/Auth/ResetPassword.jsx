import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Lock, ArrowRight, Eye, EyeOff, ChevronLeft } from 'lucide-react';
import AuthHero from '@/Components/Auth/AuthHero';
import './ResetPassword.css';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = React.useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <div className="reset-root">
            <Head title="Reset Password" />

            {/* Return to Login */}
            <Link
                href={route('login')}
                className="reset-back-link"
            >
                <ChevronLeft size={14} />
                Back to Sign In
            </Link>

            {/* Left Column: Form */}
            <div className="reset-left">
                <div className="reset-left-inner">
                    {/* Logo & Header */}
                    <div className="reset-logo-section">
                        <img
                            src="/legacy/assets/Tena-logo-square.jpg"
                            alt="Tena Logo"
                            className="reset-logo-img"
                        />
                        <div className="reset-title-area">
                            <h1 className="reset-title">New Password</h1>
                            <p className="reset-subtitle">
                                Create a strong, unique password to secure your account and manage your empire.
                            </p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="reset-form">
                        {/* Email (Hidden or Read-only usually in Laravel Breeze, but we'll show it as read-only) */}
                        <div className="reset-field-group-readonly">
                            <label className="reset-label">Email Address</label>
                            <input
                                type="email"
                                value={data.email}
                                readOnly
                                className="reset-input-readonly"
                            />
                        </div>

                        <div className="reset-field-group">
                            <label className="reset-label">New Password</label>
                            <div className="reset-input-group">
                                <div className="reset-input-icon">
                                    <Lock size={16} />
                                </div>
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="reset-input"
                                    placeholder="••••••••"
                                    autoComplete="new-password"
                                    autoFocus
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="reset-password-toggle"
                                >
                                    {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                                </button>
                            </div>
                            {errors.password && <p className="reset-error">{errors.password}</p>}
                        </div>

                        <div className="reset-field-group">
                            <label className="reset-label">Confirm Password</label>
                            <div className="reset-input-group">
                                <div className="reset-input-icon">
                                    <Lock size={16} />
                                </div>
                                <input
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    className="reset-input-confirm"
                                    placeholder="••••••••"
                                    autoComplete="new-password"
                                />
                            </div>
                            {errors.password_confirmation && <p className="reset-error">{errors.password_confirmation}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="reset-submit"
                        >
                            <div className="reset-submit-inner">
                                Reset Password
                                <ArrowRight size={16} />
                            </div>
                        </button>
                    </form>
                </div>
            </div>

            {/* Right Column: Hero Image */}
            <AuthHero />
        </div>
    );
}
