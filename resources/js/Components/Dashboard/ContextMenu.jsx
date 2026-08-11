import React, { useState, useEffect, useRef, useCallback } from 'react';
import './ContextMenu.css';

export default function ContextMenu({ children, items = [], onAction }) {
    const [isOpen, setIsOpen] = useState(false);
    const [position, setPosition] = useState({ x: 0, y: 0 });
    const menuRef = useRef(null);

    const handleContextMenu = useCallback((e) => {
        e.preventDefault();
        e.stopPropagation();

        const x = Math.min(e.clientX, window.innerWidth - 220);
        const y = Math.min(e.clientY, window.innerHeight - (items.length * 44 + 16));

        setPosition({ x, y });
        setIsOpen(true);
    }, [items.length]);

    const handleClick = useCallback(() => {
        setIsOpen(false);
    }, []);

    const handleItemClick = useCallback((item) => {
        if (item.disabled) return;
        if (onAction) onAction(item.key);
        if (item.onClick) item.onClick();
        setIsOpen(false);
    }, [onAction]);

    useEffect(() => {
        if (isOpen) {
            document.addEventListener('click', handleClick);
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setIsOpen(false); });
            return () => {
                document.removeEventListener('click', handleClick);
            };
        }
    }, [isOpen, handleClick]);

    return (
        <>
            <div onContextMenu={handleContextMenu} style={{ display: 'contents' }}>
                {children}
            </div>

            {isOpen && (
                <div
                    ref={menuRef}
                    className="context-menu"
                    style={{ left: position.x, top: position.y }}
                    onClick={(e) => e.stopPropagation()}
                >
                    <div className="context-menu__inner">
                        {items.map((item, index) => {
                            if (item.divider) {
                                return <div key={`div-${index}`} className="context-menu__divider" />;
                            }
                            return (
                                <button
                                    key={item.key || index}
                                    onClick={() => handleItemClick(item)}
                                    className={`context-menu__item ${item.variant ? `context-menu__item--${item.variant}` : ''} ${item.disabled ? 'context-menu__item--disabled' : ''}`}
                                    disabled={item.disabled}
                                >
                                    {item.icon && <span className="context-menu__item-icon">{item.icon}</span>}
                                    <span className="context-menu__item-label">{item.label}</span>
                                    {item.shortcut && <span className="context-menu__item-shortcut">{item.shortcut}</span>}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </>
    );
}

export function useContextMenu() {
    const [contextMenu, setContextMenu] = useState({ isOpen: false, x: 0, y: 0, items: [], target: null });

    const openContextMenu = useCallback((e, items, target = null) => {
        e.preventDefault();
        e.stopPropagation();
        const x = Math.min(e.clientX, window.innerWidth - 220);
        const y = Math.min(e.clientY, window.innerHeight - (items.length * 44 + 16));
        setContextMenu({ isOpen: true, x, y, items, target });
    }, []);

    const closeContextMenu = useCallback(() => {
        setContextMenu(prev => ({ ...prev, isOpen: false }));
    }, []);

    return { contextMenu, openContextMenu, closeContextMenu };
}
