import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Lock, ArrowRight } from 'lucide-react';
import './VerifyOtp.css';

export default function VerifyOtp({ email }) {
    const { data, setData, post, processing, errors } = useForm({
        email: email || '',
        code: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('guest.otp.verify'));
    };

    return (
        <div className="guest-verify-page">
            <Head title="Verify Code" />

            <div className="guest-verify-container">
                <div className="guest-verify-header">
                    <div className="guest-verify-icon">
                        <Lock size={32} className="text-[#FFD300]" />
                    </div>
                    <h1 className="guest-verify-title">Verify Code</h1>
                    <p className="guest-verify-subtitle">Enter the 6-digit code sent to {email}.</p>
                </div>

                <form onSubmit={submit} className="guest-verify-form">
                    <div className="guest-verify-fields">
                        <input type="hidden" value={data.email} name="email" />

                        <div>
                            <label className="guest-verify-label">Verification Code</label>
                            <input
                                type="text"
                                inputMode="numeric"
                                maxLength={6}
                                value={data.code}
                                onChange={e => setData('code', e.target.value.replace(/\D/g, '').slice(0, 6))}
                                className="guest-verify-input"
                                placeholder="000000"
                                required
                            />
                            {errors.code && <p className="guest-verify-error">{errors.code}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="guest-verify-submit"
                        >
                            {processing ? 'Verifying...' : 'Verify & Continue'}
                            <ArrowRight size={16} />
                        </button>
                    </div>
                </form>

                <div className="guest-verify-back">
                    <Link
                        href={route('guest.login')}
                        className="guest-verify-back-link"
                    >
                        Back to Login
                    </Link>
                </div>
            </div>
        </div>
    );
}
