import React from 'react';
import InputError from '@/Components/InputError';
import './FormPrimitives.css';

export function FormField({ label, error, children, hint, required }) {
    return (
        <div className="form-field">
            {label && (
                <label className="form-field__label">
                    {label}
                    {required && <span className="form-field__label-required">*</span>}
                </label>
            )}
            {children}
            {hint && !error && <p className="form-field__hint">{hint}</p>}
            <InputError className="mt-2" message={error} />
        </div>
    );
}

export function TextInput({ id, value, onChange, type = 'text', required, autoComplete, placeholder, className = '' }) {
    return (
        <input
            id={id}
            type={type}
            value={value}
            onChange={onChange}
            required={required}
            autoComplete={autoComplete}
            placeholder={placeholder}
            className={`form-input ${className}`}
        />
    );
}

export function TextArea({ id, value, onChange, required, placeholder, rows = 4, className = '' }) {
    return (
        <textarea
            id={id}
            value={value}
            onChange={onChange}
            required={required}
            placeholder={placeholder}
            rows={rows}
            className={`form-textarea ${className}`}
        />
    );
}

export function Select({ id, value, onChange, required, children, className = '' }) {
    return (
        <select
            id={id}
            value={value}
            onChange={onChange}
            required={required}
            className={`form-select ${className}`}
        >
            {children}
        </select>
    );
}

export function ToggleField({ label, checked, onChange, children }) {
    return (
        <label className="form-toggle">
            <div className="form-toggle__label">
                {label && <h4 className="form-toggle__title">{label}</h4>}
                {children && <p className="form-toggle__description">{children}</p>}
            </div>
            <div
                onClick={onChange}
                className={`form-toggle__switch ${checked ? 'form-toggle__switch--checked' : 'form-toggle__switch--unchecked'}`}
            >
                <span
                    className={`form-toggle__knob ${checked ? 'form-toggle__knob--checked' : 'form-toggle__knob--unchecked'}`}
                />
            </div>
        </label>
    );
}

export function CheckboxField({ label, checked, onChange }) {
    return (
        <label className="form-checkbox">
            <div
                onClick={onChange}
                className={`form-checkbox__box ${checked ? 'form-checkbox__box--checked' : 'form-checkbox__box--unchecked'}`}
            >
                {checked && (
                    <svg className="form-checkbox__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="4" d="M5 13l4 4L19 7" />
                    </svg>
                )}
            </div>
            <span className="form-checkbox__label">{label}</span>
        </label>
    );
}

export function FormActions({ children }) {
    return (
        <div className="form-actions">
            {children}
        </div>
    );
}

export function FormSuccess({ show, message = 'Saved successfully.' }) {
    return (
        <span className={`form-success ${show ? 'form-success--visible' : ''}`}>
            {message}
        </span>
    );
}
