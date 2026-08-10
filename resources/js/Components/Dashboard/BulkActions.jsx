import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, Trash2, Edit2, Eye, CheckCircle2, XCircle, Download, Archive } from 'lucide-react';
import './BulkActions.css';

export default function BulkActions({
    selectedCount = 0,
    onClearSelection,
    actions = [],
    className = '',
}) {
    if (selectedCount === 0) return null;

    return (
        <AnimatePresence>
            <motion.div
                initial={{ y: 100, opacity: 0 }}
                animate={{ y: 0, opacity: 1 }}
                exit={{ y: 100, opacity: 0 }}
                transition={{ type: 'spring', damping: 25, stiffness: 300 }}
                className={`bulk-actions ${className}`}
            >
                <div className="bulk-actions__inner">
                    <div className="bulk-actions__info">
                        <div className="bulk-actions__count">
                            <CheckCircle2 size={16} />
                            <span className="bulk-actions__count-text">
                                <strong>{selectedCount}</strong> item{selectedCount !== 1 ? 's' : ''} selected
                            </span>
                        </div>
                        <button
                            onClick={onClearSelection}
                            className="bulk-actions__clear"
                        >
                            <X size={14} />
                            Clear
                        </button>
                    </div>

                    <div className="bulk-actions__divider" />

                    <div className="bulk-actions__buttons">
                        {actions.map((action, index) => (
                            <button
                                key={index}
                                onClick={action.onClick}
                                className={`bulk-actions__btn bulk-actions__btn--${action.variant || 'default'}`}
                                title={action.label}
                            >
                                {action.icon}
                                <span className="bulk-actions__btn-label">{action.label}</span>
                            </button>
                        ))}
                    </div>
                </div>
            </motion.div>
        </AnimatePresence>
    );
}

export function BulkActionIcon({ icon: Icon, label, onClick, variant = 'default' }) {
    return (
        <button
            onClick={onClick}
            className={`bulk-actions__btn bulk-actions__btn--${variant}`}
            title={label}
        >
            <Icon size={16} />
            <span className="bulk-actions__btn-label">{label}</span>
        </button>
    );
}
