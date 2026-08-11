import PageShell from '@/Layouts/PageShell';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm';
import PasskeyManagementForm from './Partials/PasskeyManagementForm';
import GlassCard from '@/Components/Dashboard/GlassCard';
import './Edit.css';

export default function Edit({
    mustVerifyEmail,
    status,
    twoFactorEnabled,
    twoFactorSecret,
    twoFactorQrCodeUrl,
    twoFactorRecoveryCodes,
    userEmail,
    avatarUrl,
    phoneNumber,
}) {
    return (
        <PageShell
            title="Your Account"
            headTitle="Account Settings"
            breadcrumbs={[
                { label: 'Settings', href: '#' },
                { label: 'Profile' },
            ]}
        >
            <div className="profile-page">
                <GlassCard>
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        avatarUrl={avatarUrl}
                        phoneNumber={phoneNumber}
                        className="max-w-xl"
                    />
                </GlassCard>

                <GlassCard>
                    <TwoFactorAuthenticationForm
                        className="max-w-xl"
                        enabled={twoFactorEnabled}
                        secret={twoFactorSecret}
                        qrCodeUrl={twoFactorQrCodeUrl}
                        recoveryCodes={twoFactorRecoveryCodes}
                        userEmail={userEmail}
                    />
                </GlassCard>

                <GlassCard>
                    <PasskeyManagementForm className="max-w-xl" />
                </GlassCard>

                <GlassCard>
                    <UpdatePasswordForm className="max-w-xl" />
                </GlassCard>

                <GlassCard>
                    <DeleteUserForm className="max-w-xl" />
                </GlassCard>
            </div>
        </PageShell>
    );
}
