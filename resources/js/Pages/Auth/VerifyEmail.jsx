import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, MailCheck } from 'lucide-react';
import AuthHero from '@/Components/Auth/AuthHero';
import PillButton from '@/Components/Dashboard/PillButton';
import './VerifyEmail.css';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <div className="verify-root">
            <Head title="Email Verification" />

            <Link href="/" className="verify-back-link">
                <ChevronLeft size={14} />
                Return to Home
            </Link>

            <div className="verify-left">
                <div className="verify-left-inner">
                    <div className="verify-logo-section">
                        <img
                            src="/legacy/assets/Tena-logo-square.jpg"
                            alt="Tena Logo"
                            className="verify-logo-img"
                        />
                    </div>

                    <div className="verify-icon-wrapper">
                        <MailCheck size={32} className="verify-icon" />
                    </div>

                    <div className="verify-title-area">
                        <h1 className="verify-title">Verify your email</h1>
                        <p className="verify-subtitle">
                            Thanks for signing up! We've sent a verification link to your email address.
                            Click the link to get started.
                        </p>
                    </div>

                    {status === 'verification-link-sent' && (
                        <div className="verify-status">
                            A new verification link has been sent to the email address you provided.
                        </div>
                    )}

                    <form onSubmit={submit} className="verify-form">
                        <PillButton variant="primary" processing={processing} className="w-full">
                            Resend Verification Email
                        </PillButton>

                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="verify-logout-link"
                        >
                            Sign out
                        </Link>
                    </form>
                </div>
            </div>

            <AuthHero />
        </div>
    );
}
