import React from 'react';
import { Dialog, DialogBackdrop, DialogPanel } from '@headlessui/react';
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
        sm: 'tena-modal__panel--sm',
        md: 'tena-modal__panel--md',
        lg: 'tena-modal__panel--lg',
        xl: 'tena-modal__panel--xl',
        '2xl': 'tena-modal__panel--2xl',
    }[maxWidth];

    // Classes are namespaced: the legacy Bootstrap stylesheet in app.css hides
    // any `.modal`, and Headless UI closes a dialog whose panel has no size.
    // Its parts animate through their own `transition` prop (Headless UI v2).
    return (
        <Dialog open={show} onClose={close} className="tena-modal">
            <DialogBackdrop transition className="tena-modal__backdrop" />

            <div className="tena-modal__wrapper">
                <div className="tena-modal__container">
                    <DialogPanel transition className={`tena-modal__panel ${maxWidthClass}`}>
                        {children}
                    </DialogPanel>
                </div>
            </div>
        </Dialog>
    );
}
