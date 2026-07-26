import React from 'react';
import './PillButton.css';

export default function PillButton({
    children,
    onClick,
    variant = 'primary',
    className = '',
    disabled = false,
    type = 'button',
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
            disabled={disabled}
            className={`${baseStyles} ${variants[variant]} ${className}`}
        >
            {icon && <span className="pill-button__icon">{icon}</span>}
            {children}
        </button>
    );
}
