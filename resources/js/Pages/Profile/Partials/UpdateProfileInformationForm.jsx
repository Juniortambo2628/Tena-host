import React, { useRef, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { Transition } from '@headlessui/react';
import { Camera } from 'lucide-react';
import PillButton from '@/Components/Dashboard/PillButton';
import { notify } from '@/Components/Toast';
import { FormField, TextInput, FormActions, FormSuccess } from '@/Components/Forms/FormPrimitives';
import './UpdateProfileInformationForm.css';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    avatarUrl,
    phoneNumber,
    className = '',
}) {
    const user = usePage().props.auth.user;
    const fileInputRef = useRef(null);

    const { data, setData, patch, post, errors, processing, recentlySuccessful } =
        useForm({
            first_name: user.first_name,
            last_name: user.last_name,
            email: user.email,
            phone_number: phoneNumber || '',
            avatar: null,
        });

    const [previewUrl, setPreviewUrl] = useState(avatarUrl);

    const handleAvatarChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('avatar', file);
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        patch(route('profile.update'), {
            forceFormData: true,
            onSuccess: () => {
                notify.success('Profile updated successfully.');
            },
            onError: (errors) => {
                const first = Object.values(errors)[0];
                const msg = Array.isArray(first) ? first[0] : (typeof first === 'string' ? first : 'Failed to update profile.');
                notify.error(msg);
            },
        });
    };

    const initials = ((data.first_name?.[0] || '') + (data.last_name?.[0] || '')).toUpperCase();

    return (
        <section className={className}>
            <header className="profile-form__header">
                <h2 className="profile-form__title">Profile Information</h2>
                <p className="profile-form__description">
                    Update your account's profile information and email address.
                </p>
            </header>

            <form onSubmit={submit} className="space-y-6">
                <div className="profile-avatar-section">
                    <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        className="profile-avatar-btn"
                    >
                        {previewUrl ? (
                            <img src={previewUrl} alt="Avatar" className="profile-avatar-img" />
                        ) : (
                            <span className="profile-avatar-initials">{initials}</span>
                        )}
                        <span className="profile-avatar-overlay">
                            <Camera size={18} />
                        </span>
                    </button>
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={handleAvatarChange}
                        className="hidden"
                    />
                    {errors.avatar && <p className="text-red-500 text-xs mt-1">{errors.avatar}</p>}
                </div>

                <div className="profile-form__grid">
                    <FormField label="First Name" error={errors.first_name} required>
                        <TextInput
                            id="first_name"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            required
                            autoComplete="given-name"
                        />
                    </FormField>

                    <FormField label="Last Name" error={errors.last_name} required>
                        <TextInput
                            id="last_name"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            required
                            autoComplete="family-name"
                        />
                    </FormField>
                </div>

                <FormField label="Phone Number" error={errors.phone_number}>
                    <TextInput
                        id="phone_number"
                        type="tel"
                        value={data.phone_number}
                        onChange={(e) => setData('phone_number', e.target.value)}
                        placeholder="+1 (555) 000-0000"
                        autoComplete="tel"
                    />
                </FormField>

                <FormField label="Email Address" error={errors.email} required>
                    <TextInput
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />
                </FormField>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div className="profile-form__notice">
                        <p className="profile-form__notice-text">
                            Your email address is unverified.
                            <button
                                onClick={() => post(route('verification.send'))}
                                className="profile-form__notice-link"
                            >
                                Click here to re-send the verification email.
                            </button>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="profile-form__notice-success">
                                Verification link sent.
                            </div>
                        )}
                    </div>
                )}

                <FormActions>
                    <PillButton variant="primary" processing={processing}>
                        Update Profile
                    </PillButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <FormSuccess />
                    </Transition>
                </FormActions>
            </form>
        </section>
    );
}
