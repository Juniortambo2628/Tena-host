import React, { Fragment } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { AlertTriangle, Info, CheckCircle2, X } from 'lucide-react';
import './ConfirmDialog.css';

const icons = {
    danger: <AlertTriangle size={20} className="confirm-dialog__icon--danger" />,
    warning: <AlertTriangle size={20} className="confirm-dialog__icon--warning" />,
    info: <Info size={20} className="confirm-dialog__icon--info" />,
    success: <CheckCircle2 size={20} className="confirm-dialog__icon--success" />,
};

const buttonVariants = {
    danger: 'confirm-dialog__btn--danger',
    warning: 'confirm-dialog__btn--warning',
    info: 'confirm-dialog__btn--info',
    success: 'confirm-dialog__btn--success',
};

export default function ConfirmDialog({
    isOpen,
    onClose,
    onConfirm,
    title = 'Confirm Action',
    message = 'Are you sure you want to proceed?',
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    variant = 'danger',
    loading = false,
}) {
    const handleConfirm = () => {
        if (!loading) onConfirm();
    };

    return (
        <Transition show={isOpen} as={Fragment}>
            <Dialog as="div" className="confirm-dialog" onClose={onClose}>
                <Transition.Child
                    as={Fragment}
                    enter="confirm-dialog__backdrop-enter"
                    enterFrom="confirm-dialog__backdrop-enter-from"
                    enterTo="confirm-dialog__backdrop-enter-to"
                    leave="confirm-dialog__backdrop-leave"
                    leaveFrom="confirm-dialog__backdrop-leave-from"
                    leaveTo="confirm-dialog__backdrop-leave-to"
                >
                    <div className="confirm-dialog__backdrop" />
                </Transition.Child>

                <div className="confirm-dialog__container">
                    <Transition.Child
                        as={Fragment}
                        enter="confirm-dialog__panel-enter"
                        enterFrom="confirm-dialog__panel-enter-from"
                        enterTo="confirm-dialog__panel-enter-to"
                        leave="confirm-dialog__panel-leave"
                        leaveFrom="confirm-dialog__panel-leave-from"
                        leaveTo="confirm-dialog__panel-leave-to"
                    >
                        <Dialog.Panel className="confirm-dialog__panel">
                            <div className="confirm-dialog__header">
                                <div className="confirm-dialog__icon-wrapper">
                                    {icons[variant] || icons.danger}
                                </div>
                                <button onClick={onClose} className="confirm-dialog__close">
                                    <X size={16} />
                                </button>
                            </div>

                            <Dialog.Title className="confirm-dialog__title">
                                {title}
                            </Dialog.Title>

                            <p className="confirm-dialog__message">
                                {message}
                            </p>

                            <div className="confirm-dialog__actions">
                                <button
                                    onClick={onClose}
                                    className="confirm-dialog__btn confirm-dialog__btn--cancel"
                                    disabled={loading}
                                >
                                    {cancelLabel}
                                </button>
                                <button
                                    onClick={handleConfirm}
                                    className={`confirm-dialog__btn confirm-dialog__btn--confirm ${buttonVariants[variant] || buttonVariants.danger}`}
                                    disabled={loading}
                                >
                                    {loading ? (
                                        <span className="confirm-dialog__spinner" />
                                    ) : (
                                        confirmLabel
                                    )}
                                </button>
                            </div>
                        </Dialog.Panel>
                    </Transition.Child>
                </div>
            </Dialog>
        </Transition>
    );
}
