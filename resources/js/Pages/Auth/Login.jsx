import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Passkeys } from '@laravel/passkeys';
import {
    Mail,
    Lock,
    ArrowRight,
    Eye,
    EyeOff,
    ChevronLeft,
    Chrome,
    Twitter,
    Shield,
    Fingerprint,
} from 'lucide-react';
import CookiesConsent from '@/Components/CookiesConsent';
import TermsModal from '@/Components/TermsModal';
import AuthHero from '@/Components/Auth/AuthHero';
import './Login.css';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showTerms, setShowTerms] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const toggleTerms = () => setShowTerms(true);

    return (
        <div className="login-root">
            <Head title="Sign In | Tena" />

            {/* Return to Home */}
            <Link
                href="/"
                className="login-back-link"
            >
                <ChevronLeft size={14} />
                Return to Home
            </Link>

            {/* Left Column: Login Form */}
            <div className="login-left">
                <div className="login-left-inner">
                    {/* Logo & Header */}
                    <div className="login-logo-section">
                        <img
                            src="/legacy/assets/Tena-logo-square.jpg"
                            alt="Tena Logo"
                            className="login-logo-img"
                        />
                        <div className="login-title-area">
                            <h1 className="login-title">Sign in</h1>
                            <p className="login-subtitle">Enter your credentials to manage your empire.</p>
                        </div>
                    </div>

                    {status && (
                        <div className="login-status">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="login-form">
                        <div className="login-field-group">
                            <label className="login-label">Email Address</label>
                            <div className="login-input-group">
                                <div className="login-input-icon">
                                    <Mail size={16} />
                                </div>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="login-input-email"
                                    placeholder="john@example.com"
                                />
                            </div>
                            {errors.email && <p className="login-error">{errors.email}</p>}
                        </div>

                        <div className="login-field-group">
                            <div className="login-password-header">
                                <label className="login-label">Password</label>
                                {canResetPassword && (
                                    <Link href={route('password.request')} className="login-forgot-link">Forgot?</Link>
                                )}
                            </div>
                            <div className="login-input-group">
                                <div className="login-input-icon">
                                    <Lock size={16} />
                                </div>
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="login-input-password"
                                    placeholder="••••••••"
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="login-password-toggle"
                                >
                                    {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                                </button>
                            </div>
                            {errors.password && <p className="login-error">{errors.password}</p>}
                        </div>

                        <div className="login-remember">
                            <label className="login-remember-label">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="login-remember-hidden"
                                />
                                <div className={data.remember ? 'login-checkbox-checked' : 'login-checkbox'}>
                                    {data.remember && <div className="login-checkbox-dot" />}
                                </div>
                                <span className="login-remember-text">Remember me</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="login-submit"
                        >
                            <div className="login-submit-inner">
                                Sign In
                                <ArrowRight size={16} />
                            </div>
                        </button>
                    </form>

                    {/* Passkey Login */}
                    <div className="login-passkey-section">
                        <button
                            type="button"
                            onClick={async () => {
                                try {
                                    await Passkeys.login({
                                        onSuccess: () => {
                                            window.location.href = route('dashboard');
                                        },
                                    });
                                } catch (e) {
                                    console.error('Passkey login failed', e);
                                }
                            }}
                            className="login-passkey-btn"
                        >
                            <Fingerprint size={16} />
                            Sign in with Passkey
                        </button>
                    </div>

                    {/* Social Login */}
                    <div className="login-social-section">
                        <p className="login-social-text">Or continue with</p>
                        <div className="login-social-buttons">
                            <SocialBtn icon={<Chrome size={20} />} label="Google" />
                            <SocialBtn icon={<Twitter size={20} />} label="Twitter" />
                        </div>
                        <div className="login-bottom">
                            <p className="login-bottom-text">
                                Don't have an account? <Link href={route('register')} className="login-bottom-link">Sign up</Link>
                            </p>
                            <button
                                onClick={toggleTerms}
                                className="login-terms-btn"
                            >
                                <Shield size={10} />
                                Privacy & Terms
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Right Column: Hero Image */}
            <AuthHero />

            <TermsModal isOpen={showTerms} onClose={() => setShowTerms(false)} />
            <CookiesConsent />
        </div>
    );
}

const TypingEffect = ({ text = "Tena...na Tena", speed = 150, pause = 2000 }) => {
    const [displayedText, setDisplayedText] = useState("");
    const [isDeleting, setIsDeleting] = useState(false);

    React.useEffect(() => {
        let timeout;

        const handleType = () => {
            const currentLength = displayedText.length;

            if (!isDeleting && currentLength < text.length) {
                // Typing
                setDisplayedText(text.substring(0, currentLength + 1));
                timeout = setTimeout(handleType, speed);
            } else if (!isDeleting && currentLength === text.length) {
                // Pause at end
                timeout = setTimeout(() => setIsDeleting(true), pause);
            } else if (isDeleting && currentLength > 0) {
                // Deleting
                setDisplayedText(text.substring(0, currentLength - 1));
                timeout = setTimeout(handleType, speed / 2);
            } else {
                // Restart
                setIsDeleting(false);
                timeout = setTimeout(handleType, speed);
            }
        };

        timeout = setTimeout(handleType, speed);
        return () => clearTimeout(timeout);
    }, [displayedText, isDeleting, text, speed, pause]);

    return (
        <span className="login-typing">
            {displayedText}
            <motion.span
                animate={{ opacity: [1, 0] }}
                transition={{ duration: 0.5, repeat: Infinity, repeatType: "reverse" }}
                className="login-typing-cursor"
            />
        </span>
    );
};

function SocialBtn({ icon, label }) {
    return (
        <button
            title={label}
            className="login-social-btn"
        >
            {icon}
        </button>
    );
}

// ... existing Login component export ...
