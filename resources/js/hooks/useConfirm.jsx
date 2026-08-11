import React, { useState, useCallback, useRef } from 'react';
import ConfirmDialog from '@/Components/Dashboard/ConfirmDialog';

export function useConfirm() {
    const [state, setState] = useState({
        isOpen: false,
        title: '',
        message: '',
        confirmLabel: 'Confirm',
        variant: 'danger',
        loading: false,
    });
    const resolveRef = useRef(null);

    const confirm = useCallback(({ title, message, confirmLabel = 'Confirm', variant = 'danger' } = {}) => {
        return new Promise((resolve) => {
            resolveRef.current = resolve;
            setState({
                isOpen: true,
                title,
                message,
                confirmLabel,
                variant,
                loading: false,
            });
        });
    }, []);

    const handleConfirm = useCallback(() => {
        setState(s => ({ ...s, loading: true }));
        if (resolveRef.current) resolveRef.current(true);
        setState(s => ({ ...s, isOpen: false, loading: false }));
    }, []);

    const handleCancel = useCallback(() => {
        if (resolveRef.current) resolveRef.current(false);
        setState(s => ({ ...s, isOpen: false, loading: false }));
    }, []);

    const ConfirmDialogEl = (
        <ConfirmDialog
            isOpen={state.isOpen}
            onClose={handleCancel}
            onConfirm={handleConfirm}
            title={state.title}
            message={state.message}
            confirmLabel={state.confirmLabel}
            variant={state.variant}
            loading={state.loading}
        />
    );

    return { confirm, ConfirmDialogEl };
}
