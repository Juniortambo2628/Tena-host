import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import './Frame.css';

export default function Frame({ children, title, head, ...props }) {
    const [contentRef, setContentRef] = useState(null);
    const mountNode = contentRef?.contentWindow?.document?.body;

    useEffect(() => {
        if (!contentRef) return;
        const doc = contentRef.contentWindow.document;
        doc.open();
        doc.write('<!DOCTYPE html><html><head></head><body></body></html>');
        doc.close();
    }, [contentRef]);

    return (
        <iframe title={title} {...props} ref={setContentRef} className="frame">
            {mountNode && createPortal(children, mountNode)}
            {mountNode && head && createPortal(head, contentRef.contentWindow.document.head)}
        </iframe>
    );
}
