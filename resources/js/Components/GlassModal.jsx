import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import './GlassModal.css';

export default function GlassModal({ isOpen, onClose, title, children, maxWidth = "2xl" }) {
    const maxWidthClasses = {
        'sm': 'glass-modal__panel--sm',
        'md': 'glass-modal__panel--md',
        'lg': 'glass-modal__panel--lg',
        'xl': 'glass-modal__panel--xl',
        '2xl': 'glass-modal__panel--2xl',
        '3xl': 'glass-modal__panel--3xl',
        '4xl': 'glass-modal__panel--4xl',
        '5xl': 'glass-modal__panel--5xl',
        '6xl': 'glass-modal__panel--6xl',
        '7xl': 'glass-modal__panel--7xl',
    };

    return (
        <AnimatePresence>
            {isOpen && (
                <div className="glass-modal">
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={onClose}
                        className="glass-modal__overlay"
                    />
                    <motion.div
                        initial={{ opacity: 0, scale: 0.95, y: 10 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.95, y: 10 }}
                        className={`glass-modal__panel ${maxWidthClasses[maxWidth]}`}
                    >
                        <div className="glass-modal__header">
                            <h2 className="glass-modal__title">{title}</h2>
                            <button
                                onClick={onClose}
                                className="glass-modal__close"
                            >
                                <X size={20} />
                            </button>
                        </div>
                        <div className="glass-modal__body">
                            {children}
                        </div>
                    </motion.div>
                </div>
            )}
        </AnimatePresence>
    );
}

const X = ({ size }) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
    </svg>
);
