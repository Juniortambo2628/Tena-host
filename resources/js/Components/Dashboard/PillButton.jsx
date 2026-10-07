import React from 'react';
import './PillButton.css';

export default function PillButton({
    children,
    onClick,
    variant = 'primary',
    className = '',
    disabled = false,
    processing = false,
    // Without a click handler a pill button can only be a form submit, so
    // default to that; an explicit `type` still wins.
    type = onClick ? 'button' : 'submit',
    icon = null
}) {
    const baseStyles = "tena-btn";

    const variants = {
        primary: "tena-btn-primary",
        secondary: "tena-btn-secondary",
        ghost: "tena-btn-ghost",
        white: "tena-btn-white",
        danger: "tena-btn-danger",
    };

    return (
        <button
            type={type}
            onClick={onClick}
            disabled={disabled || processing}
            aria-busy={processing || undefined}
            className={`${baseStyles} ${variants[variant]} ${className}`}
        >
            {icon && <span className="pill-button__icon">{icon}</span>}
            {children}
        </button>
    );
}
