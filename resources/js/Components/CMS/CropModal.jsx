import React, { useState, useRef, useCallback, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import './CropModal.css';

export default function CropModal({ media, isOpen, onClose, onCropped }) {
    const imgRef = useRef(null);
    const containerRef = useRef(null);
    const [crop, setCrop] = useState({ x: 0, y: 0, width: 100, height: 100 });
    const [isDragging, setIsDragging] = useState(false);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
    const [imgNatural, setImgNatural] = useState({ width: 0, height: 0 });
    const [saving, setSaving] = useState(false);

    const imageUrl = media?.thumbnail_path || media?.original_path;

    useEffect(() => {
        if (isOpen && imgRef.current) {
            const img = imgRef.current;
            const onLoad = () => {
                setImgNatural({ width: img.naturalWidth, height: img.naturalHeight });
                const container = containerRef.current;
                if (!container) return;
                const cw = container.offsetWidth;
                const ch = container.offsetHeight;
                const scale = Math.min(cw / img.naturalWidth, ch / img.naturalHeight);
                const displayW = img.naturalWidth * scale;
                const displayH = img.naturalHeight * scale;
                const cropW = displayW * 0.8;
                const cropH = displayW * 0.8 * (media?.height && media?.width ? media.height / media.width : 0.5625);
                setCrop({
                    x: (displayW - cropW) / 2,
                    y: (displayH - cropH) / 2,
                    width: Math.min(cropW, displayW),
                    height: Math.min(cropH, displayH),
                });
            };
            img.onload = onLoad;
            if (img.complete) onLoad();
        }
    }, [isOpen, media]);

    const getDisplayScale = useCallback(() => {
        const img = imgRef.current;
        const container = containerRef.current;
        if (!img || !container) return 1;
        return Math.min(container.offsetWidth / img.naturalWidth, container.offsetHeight / img.naturalHeight);
    }, []);

    const handleMouseDown = useCallback((e) => {
        e.preventDefault();
        setIsDragging(true);
        setDragStart({ x: e.clientX - crop.x, y: e.clientY - crop.y });
    }, [crop]);

    const handleMouseMove = useCallback((e) => {
        if (!isDragging) return;
        const container = containerRef.current;
        const img = imgRef.current;
        if (!container || !img) return;
        const scale = getDisplayScale();
        const displayW = img.naturalWidth * scale;
        const displayH = img.naturalHeight * scale;
        const newX = e.clientX - dragStart.x;
        const newY = e.clientY - dragStart.y;
        setCrop(prev => ({
            ...prev,
            x: Math.max(0, Math.min(newX, displayW - prev.width)),
            y: Math.max(0, Math.min(newY, displayH - prev.height)),
        }));
    }, [isDragging, dragStart, getDisplayScale]);

    const handleMouseUp = useCallback(() => {
        setIsDragging(false);
    }, []);

    useEffect(() => {
        if (isDragging) {
            window.addEventListener('mousemove', handleMouseMove);
            window.addEventListener('mouseup', handleMouseUp);
            return () => {
                window.removeEventListener('mousemove', handleMouseMove);
                window.removeEventListener('mouseup', handleMouseUp);
            };
        }
    }, [isDragging, handleMouseMove, handleMouseUp]);

    const handleSave = async () => {
        if (!media?.id) return;
        const scale = getDisplayScale();
        const cropData = {
            x: Math.round(crop.x / scale),
            y: Math.round(crop.y / scale),
            width: Math.round(crop.width / scale),
            height: Math.round(crop.height / scale),
        };
        setSaving(true);
        router.put(route('admin.landing.media.crop', { media: media.id }), {
            crop_data: cropData,
        }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
            onSuccess: () => {
                notify.success('Image cropped');
                if (onCropped) onCropped();
                onClose();
            },
            onError: () => notify.error('Failed to crop image'),
        });
    };

    if (!isOpen) return null;

    return (
        <div className="crop-overlay">
            <div className="crop-backdrop" onClick={onClose} />
            <div className="crop-modal">
                <div className="crop-modal__header">
                    <h3 className="crop-modal__title">Crop & Reposition</h3>
                    <button onClick={onClose} className="crop-modal__close">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div className="crop-modal__body">
                    <div
                        ref={containerRef}
                        className="crop-canvas"
                        onMouseMove={handleMouseMove}
                        onMouseUp={handleMouseUp}
                    >
                        <img
                            ref={imgRef}
                            src={imageUrl}
                            alt="Crop preview"
                            className="crop-image"
                            draggable={false}
                        />
                        {imgRef.current && containerRef.current && (
                            <>
                                <div className="crop-dim-overlay crop-dim-top" style={{ height: crop.y, left: 0, right: 0 }} />
                                <div className="crop-dim-overlay crop-dim-bottom" style={{ top: crop.y + crop.height, left: 0, right: 0, bottom: 0 }} />
                                <div className="crop-dim-overlay crop-dim-left" style={{ top: crop.y, height: crop.height, left: 0, width: crop.x }} />
                                <div className="crop-dim-overlay crop-dim-right" style={{ top: crop.y, height: crop.height, left: crop.x + crop.width, right: 0 }} />
                                <div
                                    className="crop-box"
                                    style={{ left: crop.x, top: crop.y, width: crop.width, height: crop.height }}
                                    onMouseDown={handleMouseDown}
                                >
                                    <div className="crop-box__corner crop-box__corner--tl" />
                                    <div className="crop-box__corner crop-box__corner--tr" />
                                    <div className="crop-box__corner crop-box__corner--bl" />
                                    <div className="crop-box__corner crop-box__corner--br" />
                                    <div className="crop-box__crosshair crop-box__crosshair--h" />
                                    <div className="crop-box__crosshair crop-box__crosshair--v" />
                                </div>
                            </>
                        )}
                    </div>
                    <p className="crop-modal__hint">Drag the crop area to reposition. The highlighted area will be visible.</p>
                </div>
                <div className="crop-modal__footer">
                    <button onClick={onClose} className="crop-modal__cancel">Cancel</button>
                    <button onClick={handleSave} disabled={saving} className="crop-modal__save">
                        {saving ? 'Saving...' : 'Apply Crop'}
                    </button>
                </div>
            </div>
        </div>
    );
}
