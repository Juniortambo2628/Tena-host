import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Mail, ArrowRight } from 'lucide-react';
import './Login.css';

export default function GuestLogin() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('guest.otp.send'));
    };

    return (
        <div className="guest-login-page">
            <Head title="Guest Login" />

            <div className="guest-login-container">
                <div className="guest-login-header">
                    <div className="guest-login-icon">
                        <Mail size={32} className="text-[#FFD300]" />
                    </div>
                    <h1 className="guest-login-title">Guest Portal</h1>
                    <p className="guest-login-subtitle">Enter your email to receive a one-time verification code.</p>
                </div>

                <form onSubmit={submit} className="guest-login-form">
                    <div className="guest-login-fields">
                        <div>
                            <label className="guest-login-label">Email Address</label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={e => setData('email', e.target.value)}
                                className="guest-login-input"
                                placeholder="your@email.com"
                                required
                            />
                            {errors.email && <p className="guest-login-error">{errors.email}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="guest-login-submit"
                        >
                            {processing ? 'Sending...' : 'Send Code'}
                            <ArrowRight size={16} />
                        </button>
                    </div>
                </form>

                <p className="guest-login-footer">
                    Powered by TENA
                </p>
            </div>
        </div>
    );
}
