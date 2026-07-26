import { useForm, usePage } from '@inertiajs/react';
import { Transition } from '@headlessui/react';
import PillButton from '@/Components/Dashboard/PillButton';
import { FormField, TextInput, FormActions, FormSuccess } from '@/Components/Forms/FormPrimitives';
import './UpdateProfileInformationForm.css';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, post, errors, processing, recentlySuccessful } =
        useForm({
            first_name: user.first_name,
            last_name: user.last_name,
            email: user.email,
        });

    const submit = (e) => {
        e.preventDefault();
        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header className="profile-form__header">
                <h2 className="profile-form__title">Profile Information</h2>
                <p className="profile-form__description">
                    Update your account's profile information and email address.
                </p>
            </header>

            <form onSubmit={submit} className="space-y-6">
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
