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
        '3xl': 'tena-modal__panel--3xl',
        '4xl': 'tena-modal__panel--4xl',
        '5xl': 'tena-modal__panel--5xl',
        '6xl': 'tena-modal__panel--6xl',
        '7xl': 'tena-modal__panel--7xl',
    }[maxWidth];

    // Headless UI closes a dialog whose panel has no size, so the panel always
    // gets one. Its parts animate through their own `transition` prop (Headless UI v2).
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
