import React, { useLayoutEffect, useRef } from 'react';

/**
 * Single-line text that shrinks its font (max → min px) until it fits the
 * width it's given, then ellipsizes as a last resort. Refits on resize.
 */
export default function FitText({ as: Tag = 'span', className = '', max = 14, min = 10, children }) {
    const ref = useRef(null);

    useLayoutEffect(() => {
        const el = ref.current;
        if (!el) return undefined;

        const fit = () => {
            let size = max;
            el.style.fontSize = `${size}px`;
            while (size > min && el.scrollWidth > el.clientWidth) {
                size -= 0.5;
                el.style.fontSize = `${size}px`;
            }
        };

        fit();
        const observer = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(fit) : null;
        observer?.observe(el.parentElement);
        document.fonts?.ready.then(fit);

        return () => observer?.disconnect();
    }, [children, max, min]);

    return (
        <Tag ref={ref} className={className} style={{ whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', maxWidth: '100%' }}>
            {children}
        </Tag>
    );
}
