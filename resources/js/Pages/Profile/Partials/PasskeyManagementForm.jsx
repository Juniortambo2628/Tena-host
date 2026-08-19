import React, { useState, useEffect } from 'react';
import { Passkeys } from '@laravel/passkeys';
import { router } from '@inertiajs/react';
import { Key, Trash2, Plus, Shield } from 'lucide-react';
import { notify } from '@/Components/Toast';
import './PasskeyManagementForm.css';

export default function PasskeyManagementForm({ className = '' }) {
    const [passkeys, setPasskeys] = useState([]);
    const [loading, setLoading] = useState(true);
    const [registering, setRegistering] = useState(false);

    const loadPasskeys = async () => {
        try {
            const response = await fetch(route('passkey.registration-options'));
            if (response.ok) {
                const data = await response.json();
                setPasskeys(data.passkeys || []);
            }
        } catch (e) {
            console.error('Failed to load passkeys', e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadPasskeys();
    }, []);

    const registerPasskey = async () => {
        setRegistering(true);
        try {
            const name = prompt('Enter a name for this passkey (e.g. "My Laptop", "Phone"):');
            if (!name) {
                setRegistering(false);
                return;
            }

            const result = await Passkeys.register({ name });

            if (result && result.credential) {
                notify.success('Passkey registered successfully.');
                loadPasskeys();
            }
        } catch (e) {
            console.error('Passkey registration error:', e);
            notify.error(e?.message || 'Failed to register passkey.');
        } finally {
            setRegistering(false);
        }
    };

    const deletePasskey = async (passkeyId) => {
        if (!confirm('Delete this passkey? This cannot be undone.')) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(route('passkey.destroy', { passkey: passkeyId }), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                notify.success('Passkey deleted.');
                loadPasskeys();
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

                {loading ? (
                    <div className="passkey-loading">Loading passkeys...</div>
                ) : passkeys.length === 0 ? (
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
        </section>
    );
}
