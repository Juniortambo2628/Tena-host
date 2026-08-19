import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, Lock } from 'lucide-react';
import AuthHero from '@/Components/Auth/AuthHero';
import PillButton from '@/Components/Dashboard/PillButton';
import { FormField, TextInput } from '@/Components/Forms/FormPrimitives';
import './ConfirmPassword.css';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="confirm-root">
            <Head title="Confirm Password" />

            <Link href="/" className="confirm-back-link">
                <ChevronLeft size={14} />
                Return to Home
            </Link>

            <div className="confirm-left">
                <div className="confirm-left-inner">
                    <div className="confirm-logo-section">
                        <img
                            src="/legacy/assets/Tena-logo-square.jpg"
                            alt="Tena Logo"
                            className="confirm-logo-img"
                        />
                    </div>

                    <div className="confirm-icon-wrapper">
                        <Lock size={24} className="confirm-icon" />
                    </div>

                    <div className="confirm-title-area">
                        <h1 className="confirm-title">Confirm your password</h1>
                        <p className="confirm-subtitle">
                            This is a secure area. Please confirm your password before continuing.
                        </p>
                    </div>

                    <form onSubmit={submit} className="confirm-form">
                        <FormField label="Password" error={errors.password} required>
                            <TextInput
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoFocus
                                autoComplete="current-password"
                            />
                        </FormField>

                        <PillButton variant="primary" processing={processing} className="w-full">
                            Confirm
                        </PillButton>
                    </form>
                </div>
            </div>

            <AuthHero />
        </div>
    );
}
