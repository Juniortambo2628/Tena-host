import React, { useState } from 'react';
import { Passkeys } from '@laravel/passkeys';
import { router } from '@inertiajs/react';
import { Key, Trash2, Plus, Shield } from 'lucide-react';
import { notify } from '@/Components/Toast';
import './PasskeyManagementForm.css';
import { csrfHeaders } from '@/Support/csrf';
import { useConfirm } from '@/hooks/useConfirm';

export default function PasskeyManagementForm({ className = '', passkeys = [] }) {
    const { confirm, ConfirmDialogEl } = useConfirm();
    const [registering, setRegistering] = useState(false);

    const registerPasskey = async () => {
        setRegistering(true);
        try {
            const name = prompt('Enter a name for this passkey (e.g. "My Laptop", "Phone"):');
            if (!name) {
                setRegistering(false);
                return;
            }

            await Passkeys.register({ name });
            notify.success('Passkey registered successfully.');
            router.reload({ only: ['passkeys'] });
        } catch (e) {
            console.error('Passkey registration error:', e);
            notify.error(e?.message || 'Failed to register passkey.');
        } finally {
            setRegistering(false);
        }
    };

    const deletePasskey = async (passkeyId) => {
        const ok = await confirm({
            title: 'Delete Passkey',
            message: 'Delete this passkey? This cannot be undone.',
            confirmLabel: 'Delete',
            variant: 'danger',
        });
        if (!ok) return;

        try {
            const response = await fetch(route('passkey.destroy', { passkey: passkeyId }), {
                method: 'DELETE',
                headers: {
                    ...csrfHeaders(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                notify.success('Passkey deleted.');
                router.reload({ only: ['passkeys'] });
            } else {
                notify.error('Failed to delete passkey.');
            }
        } catch (e) {
            notify.error('Failed to delete passkey.');
        }
    };

    return (
        <section className={className}>
            <header className="passkey-header">
                <div className="passkey-header-title">
                    <Key size={20} />
                    <h2>Passkeys</h2>
                </div>
                <p className="passkey-header-desc">
                    Sign in without a password using biometrics, a security key, or your device's screen lock.
                </p>
            </header>

            <div className="passkey-content">
                <div className="passkey-register-row">
                    <button
                        onClick={registerPasskey}
                        disabled={registering}
                        className="passkey-register-btn"
                    >
                        <Plus size={16} />
                        {registering ? 'Registering...' : 'Add New Passkey'}
                    </button>
                </div>

                {passkeys.length === 0 ? (
                    <div className="passkey-empty">
                        <Shield size={24} className="passkey-empty-icon" />
                        <p className="passkey-empty-text">No passkeys registered yet.</p>
                        <p className="passkey-empty-desc">Add a passkey for faster, more secure sign-in.</p>
                    </div>
                ) : (
                    <div className="passkey-list">
                        {passkeys.map((pk) => (
                            <div key={pk.id} className="passkey-item">
                                <div className="passkey-item-info">
                                    <div className="passkey-item-icon">
                                        <Key size={16} />
                                    </div>
                                    <div>
                                        <p className="passkey-item-name">{pk.name || 'Passkey'}</p>
                                        <p className="passkey-item-date">
                                            Added {new Date(pk.created_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    onClick={() => deletePasskey(pk.id)}
                                    className="passkey-delete-btn"
                                    title="Delete passkey"
                                >
                                    <Trash2 size={14} />
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
            {ConfirmDialogEl}
        </section>
    );
}
