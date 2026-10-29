import React from 'react';
import { DialogTitle } from '@headlessui/react';
import { X } from 'lucide-react';
import Modal from '@/Components/Modal';
import './GlassModal.css';

// A titled dialog with a close button and a scrolling body, built on the shared
// Modal (focus trap, Escape to close, labelled by its title).
export default function GlassModal({ isOpen, onClose, title, children, maxWidth = '2xl' }) {
    return (
        <Modal show={isOpen} onClose={onClose} maxWidth={maxWidth}>
            <div className="glass-modal__header">
                <DialogTitle as="h2" className="glass-modal__title">{title}</DialogTitle>
                <button type="button" onClick={onClose} className="glass-modal__close" aria-label="Close">
                    <X size={20} strokeWidth={2.5} />
                </button>
            </div>
            <div className="glass-modal__body">
                {children}
            </div>
        </Modal>
    );
}
