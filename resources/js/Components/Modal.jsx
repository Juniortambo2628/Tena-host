import React from 'react';
import {
    Dialog,
    DialogBackdrop,
    DialogPanel,
    Transition,
    TransitionChild,
} from '@headlessui/react';
import './Modal.css';


export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => { },
}) {
    const close = () => {
        if (closeable) {
            onClose();
        }
    };

    const maxWidthClass = {
        sm: 'modal__panel--sm',
        md: 'modal__panel--md',
        lg: 'modal__panel--lg',
        xl: 'modal__panel--xl',
        '2xl': 'modal__panel--2xl',
    }[maxWidth];

    return (
        <Transition show={show} as={React.Fragment}>
            <Dialog
                as="div"
                id="modal"
                className="modal"
                onClose={close}
            >
                <DialogBackdrop
                    transition
                    className="modal__backdrop"
                />

                <div className="modal__wrapper">
                    <div className="modal__container">
                        <TransitionChild
                            as={React.Fragment}
                            enter="modal__enter"
                            enterFrom="modal__enter-from"
                            enterTo="modal__enter-to"
                            leave="modal__leave"
                            leaveFrom="modal__leave-from"
                            leaveTo="modal__leave-to"
                        >
                            <DialogPanel
                                className={`modal__panel ${maxWidthClass}`}
                            >
                                {children}
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </Transition>
    );
}
