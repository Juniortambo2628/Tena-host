import React from 'react';
import { Link } from '@inertiajs/react';
import './PillButton.css';

export default function PillButton({
    children,
    onClick,
    // With href the pill is a link (prefetched on hover) instead of a button.
    href,
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

    const classes = `${baseStyles} ${variants[variant]} ${className}`;
    const content = (
        <>
            {icon && <span className="pill-button__icon">{icon}</span>}
            {children}
        </>
    );

    if (href) {
        return (
            <Link href={href} prefetch onClick={onClick} className={classes}>
                {content}
            </Link>
        );
    }

    return (
        <button
            type={type}
            onClick={onClick}
            disabled={disabled || processing}
            aria-busy={processing || undefined}
            className={classes}
        >
            {content}
        </button>
    );
}
