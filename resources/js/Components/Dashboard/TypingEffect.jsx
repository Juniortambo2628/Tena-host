import React, { useState, useEffect } from 'react';

/**
 * Infinite typing + deleting animation.
 * Pure CSS cursor blink via Tailwind — no framer-motion dependency.
 */
export default function TypingEffect({
    text = 'Tena...na Tena',
    speed = 120,
    deleteSpeed = 80,
    pause = 1800,
    className = '',
}) {
    const [displayed, setDisplayed] = useState('');
    const [isDeleting, setIsDeleting] = useState(false);

    useEffect(() => {
        let timeout;

        const tick = () => {
            if (!isDeleting) {
                if (displayed.length < text.length) {
                    setDisplayed(text.slice(0, displayed.length + 1));
                    timeout = setTimeout(tick, speed);
                } else {
                    timeout = setTimeout(() => setIsDeleting(true), pause);
                }
            } else {
                if (displayed.length > 0) {
                    setDisplayed(text.slice(0, displayed.length - 1));
                    timeout = setTimeout(tick, deleteSpeed);
                } else {
                    setIsDeleting(false);
                    timeout = setTimeout(tick, speed);
                }
            }
        };

        timeout = setTimeout(tick, speed);
        return () => clearTimeout(timeout);
    }, [displayed, isDeleting, text, speed, deleteSpeed, pause]);

    return (
        <span className={className}>
            {displayed}
            <span className="typing-cursor" />
        </span>
    );
}
