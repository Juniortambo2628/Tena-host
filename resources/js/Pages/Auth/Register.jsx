import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    User,
    Mail,
    Lock,
    ArrowRight,
    Eye,
    EyeOff,
    ChevronLeft,
    Shield
} from 'lucide-react';
import CookiesConsent from '@/Components/CookiesConsent';
import TermsModal from '@/Components/TermsModal';
import AuthHero from '@/Components/Auth/AuthHero';
import './Register.css';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showTerms, setShowTerms] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const toggleTerms = () => setShowTerms(true);

    return (
        <div className="register-root">
            <Head title="Create Account | Tena" />

            {/* Return to Home */}
            <Link
                href="/"
                className="register-back-link"
            >
                <ChevronLeft size={14} />
                Return to Home
            </Link>

            {/* Left Column: Register Form */}
            <div className="register-left">
                <div className="register-left-inner">
                    {/* Logo & Header */}
                    <div className="register-logo-section">
                        <img
                            src="/legacy/assets/Tena-logo-square.jpg"
                            alt="Tena Logo"
                            className="register-logo-img"
                        />
                        <div className="register-title-area">
                            <h1 className="register-title">Join the empire</h1>
                            <p className="register-subtitle">Create your account to start managing your property with ease.</p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="register-form">
                        <div className="register-name-grid">
                            <div className="register-field-group">
                                <label className="register-label">First Name</label>
                                <div className="register-input-group">
                                    <div className="register-input-icon">
                                        <User size={16} />
                                    </div>
                                    <input
                                        type="text"
                                        value={data.first_name}
                                        onChange={(e) => setData('first_name', e.target.value)}
                                        className="register-input"
                                        placeholder="Jane"
                                        required
                                    />
                                </div>
                                {errors.first_name && <p className="register-error">{errors.first_name}</p>}
                            </div>
                            <div className="register-field-group">
                                <label className="register-label">Last Name</label>
                                <input
                                    type="text"
                                    value={data.last_name}
                                    onChange={(e) => setData('last_name', e.target.value)}
                                    className="register-input-no-icon"
                                    placeholder="Doe"
                                    required
                                />
                                {errors.last_name && <p className="register-error">{errors.last_name}</p>}
                            </div>
                        </div>

                        <div className="register-field-group">
                            <label className="register-label">Email Address</label>
                            <div className="register-input-group">
                                <div className="register-input-icon">
                                    <Mail size={16} />
                                </div>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="register-input"
                                    placeholder="jane@example.com"
                                    required
                                />
                            </div>
                            {errors.email && <p className="register-error">{errors.email}</p>}
                        </div>

                        <div className="register-field-group">
                            <label className="register-label">Password</label>
                            <div className="register-input-group">
                                <div className="register-input-icon">
                                    <Lock size={16} />
                                </div>
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="register-input-password"
                                    placeholder="••••••••"
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="register-password-toggle"
                                >
                                    {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                                </button>
                            </div>
                        </div>

                        <div className="register-field-group">
                            <label className="register-label">Confirm Password</label>
                            <input
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="register-input-confirm"
                                placeholder="••••••••"
                                required
                            />
                        </div>
                        {errors.password && <p className="register-error">{errors.password}</p>}

                        <button
                            type="submit"
                            disabled={processing}
                            className="register-submit"
                        >
                            <div className="register-submit-inner">
                                Create Account
                                <ArrowRight size={16} />
                            </div>
                        </button>

                        <div className="register-bottom">
                            <p className="register-bottom-text">
                                Already a member? <Link href={route('login')} className="register-bottom-link">Sign in</Link>
                            </p>
                            <button
                                onClick={toggleTerms}
                                className="register-terms-btn"
                            >
                                <Shield size={10} />
                                Privacy & Terms
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {/* Right Column: Hero Image (Same as Login for consistency) */}
            <AuthHero />

            <TermsModal isOpen={showTerms} onClose={() => setShowTerms(false)} />
            <CookiesConsent />
        </div>
    );
}
