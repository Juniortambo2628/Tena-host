import './InputError.css';

export default function InputError({
    message,
    className = '',
    ...props
}) {
    return message ? (
        <p
            {...props}
            className={'input-error ' + className}
        >
            {message}
        </p>
    ) : null;
}
