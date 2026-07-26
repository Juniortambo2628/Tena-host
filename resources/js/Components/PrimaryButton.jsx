import './PrimaryButton.css';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `primary-button ${disabled ? 'primary-button--disabled' : ''} ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
