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
            await Passkeys.register({
                onSuccess: () => {
                    notify.success('Passkey registered successfully.');
                    loadPasskeys();
                },
                onError: (error) => {
                    notify.error(error?.message || 'Failed to register passkey.');
                },
            });
        } catch (e) {
            notify.error('Passkey registration failed.');
        } finally {
            setRegistering(false);
        }
    };

    const deletePasskey = async (passkeyId) => {
        if (!confirm('Delete this passkey? This cannot be undone.')) return;

        try {
            await Passkeys.delete(passkeyId);
            notify.success('Passkey deleted.');
            loadPasskeys();
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
