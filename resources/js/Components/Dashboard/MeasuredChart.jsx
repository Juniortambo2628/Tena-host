import React, { useEffect, useRef, useState } from 'react';

// Mount charts only after layout has a real size. This also handles hidden tabs,
// lazy loading and sidebar animations without Recharts' initial -1 dimensions.
export default function MeasuredChart({ children }) {
    const container = useRef(null);
    const [size, setSize] = useState({ width: 0, height: 0 });
    useEffect(() => {
        const observer = new ResizeObserver(([entry]) => {
            const width = Math.floor(entry.contentRect.width);
            const height = Math.floor(entry.contentRect.height);
            setSize(previous => previous.width === width && previous.height === height
                ? previous : { width, height });
        });
        observer.observe(container.current);
        return () => observer.disconnect();
    }, []);
    return (
        <div ref={container} style={{ width: '100%', height: '100%', minWidth: 0 }}>
            {size.width > 0 && size.height > 0 && React.cloneElement(children, size)}
        </div>
    );
}
