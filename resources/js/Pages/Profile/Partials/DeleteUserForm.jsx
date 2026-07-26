import PillButton from '@/Components/Dashboard/PillButton';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import './DeleteUserForm.css';

export default function DeleteUserForm({ className = '' }) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef(null);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);
        clearErrors();
        reset();
    };

    return (
        <section className={`delete-form-section ${className}`}>
            <header className="delete-form-header">
                <h2 className="delete-form-title">
                    Danger Zone
                </h2>
                <p className="delete-form-desc">
                    Once your account is deleted, all of its resources and data will be permanently deleted.
                </p>
            </header>

            <PillButton variant="secondary" className="text-red-500 bg-red-500/5 border-red-500/10 hover:bg-red-500 hover:text-white" onClick={confirmUserDeletion}>
                Delete Account
            </PillButton>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="delete-modal-form">
                    <h2 className="delete-modal-title">
                        Are you sure?
                    </h2>

                    <p className="delete-modal-desc">
                        Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm.
                    </p>

                    <div className="delete-modal-fields">
                        <label className="delete-modal-label">Confirm Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="delete-modal-input"
                            isFocused
                            placeholder="Type your password..."
                        />
                        <InputError message={errors.password} className="mt-2" />
                    </div>

                    <div className="delete-modal-actions">
                        <PillButton variant="white" className="flex-1" onClick={closeModal}>
                            Cancel
                        </PillButton>
                        <PillButton variant="primary" className="flex-1 bg-red-500 hover:bg-red-600 shadow-red-500/20" disabled={processing}>
                            Confirm Delete
                        </PillButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
