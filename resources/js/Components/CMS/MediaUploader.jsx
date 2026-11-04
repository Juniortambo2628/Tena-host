import React, { useState, useEffect, useMemo, useRef } from 'react';
import { router } from '@inertiajs/react';
import { notify } from '@/Components/Toast';
import { Search, RefreshCw, Crop, Download, Trash2, AlertTriangle } from 'lucide-react';
import './MediaUploader.css';
import { csrfHeaders } from '@/Support/csrf';
import shrinkImage from './shrinkImage';

/** A readable message for a failed upload response. */
async function uploadErrorMessage(res) {
    if (res.status === 413) return 'This file is bigger than the server accepts. Try a smaller photo or a screenshot.';
    if (res.status === 419) return 'Your session expired. Refresh the page and try again.';
    const data = await res.json().catch(() => null);
    return data?.errors?.file?.[0] || data?.error || data?.message || `Upload failed (${res.status}). Please try again.`;
}

export default function MediaUploader({
    sectionId,
    mediaKey,
    onUpload,
    onDelete,
    onDownload,
    onCrop,
    existingMedia = null,
    maxSize = 20 * 1024 * 1024,
    accept = null,
    label = 'Drop file here or click to browse',
    // A plain name for the slot ("Main hero image"); the key shows underneath.
    title = '',
    // Set when the site doesn't show this slot right now (a drawing is shown instead).
    notice = '',
}) {
    const [isUploading, setIsUploading] = useState(false);
    const [uploadError, setUploadError] = useState('');
    const [isChanging, setIsChanging] = useState(false);
    const [mode, setMode] = useState('upload');
    const [libraryItems, setLibraryItems] = useState([]);
    const [libraryLoading, setLibraryLoading] = useState(false);
    const [librarySearch, setLibrarySearch] = useState('');
    const fileInputRef = useRef(null);

    const handleFileSelect = async (e) => {
        const picked = e.target.files?.[0];
        if (fileInputRef.current) fileInputRef.current.value = '';
        if (!picked) return;

        setIsUploading(true);
        setUploadError('');

        // Full-size phone photos are shrunk first, so they fit the server's upload limit.
        let file;
        try {
            file = await shrinkImage(picked);
        } catch (err) {
            setUploadError(err.message);
            setIsUploading(false);
            return;
        }

        if (file.size > maxSize) {
            setUploadError(`File exceeds ${(maxSize / 1024 / 1024).toFixed(0)}MB limit`);
            setIsUploading(false);
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('media_key', mediaKey);

        try {
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...csrfHeaders(),
            };

            const res = await fetch(route('admin.landing.media.upload', { section: sectionId }), {
                method: 'POST',
                credentials: 'same-origin',
                headers,
                body: formData,
            });

            if (!res.ok) {
                throw new Error(await uploadErrorMessage(res));
            }

            const data = await res.json();
            setIsUploading(false);
            setIsChanging(false);
            notify.success('Photo uploaded. It’s on the website now.');
            if (onUpload) onUpload(data.media);
        } catch (err) {
            setUploadError(err.message || 'Upload failed');
            setIsUploading(false);
        }
    };

    const loadLibrary = async () => {
        setLibraryLoading(true);
        try {
            const res = await fetch(route('admin.landing.media.list'), {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            setLibraryItems(data);
        } catch {
            setLibraryItems([]);
        } finally {
            setLibraryLoading(false);
        }
    };

    useEffect(() => {
        if (mode === 'library' && libraryItems.length === 0 && !libraryLoading) {
            loadLibrary();
        }
    }, [mode]);

    const filteredLibrary = useMemo(() => {
        if (!librarySearch.trim()) return libraryItems;
        const q = librarySearch.toLowerCase();
        return libraryItems.filter(item =>
            item.media_key?.toLowerCase().includes(q) ||
            item.original_path?.toLowerCase().includes(q) ||
            item.section_key?.toLowerCase().includes(q) ||
            item.section_title?.toLowerCase().includes(q)
        );
    }, [libraryItems, librarySearch]);

    const handleSelectFromLibrary = (item) => {
        router.post(route('admin.landing.media.assign', { section: sectionId }), {
            media_id: item.id,
            media_key: mediaKey,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                if (onUpload) onUpload(item);
            },
            onError: () => notify.error('Failed to assign media from library'),
        });
    };

    const handleCrop = () => {
        if (existingMedia && onCrop) {
            onCrop(existingMedia);
        }
    };

    const handleDownload = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const url = route('admin.landing.media.download', { media: existingMedia.id });
        window.open(url, '_blank');
    };

    const renderUploadInput = () => (
        <div className="cms-uploader__dropzone" onClick={() => fileInputRef.current?.click()}>
            <input
                ref={fileInputRef}
                type="file"
                accept={accept?.join(',') || 'image/*,video/*'}
                onChange={handleFileSelect}
                className="cms-uploader__input-hidden"
            />
            <div className="cms-uploader__dropzone-content">
                <svg className="cms-uploader__dropzone-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <span className="cms-uploader__dropzone-text">{label}</span>
                <span className="cms-uploader__dropzone-hint">Max {(maxSize / 1024 / 1024).toFixed(0)}MB</span>
            </div>
        </div>
    );

    if (existingMedia) {
        const isVideo = existingMedia.mime_type?.startsWith('video/');
        const thumbnailUrl = existingMedia.thumbnail_path || existingMedia.original_path;

        return (
            <div className="cms-media-card">
                <div className="cms-media-card__preview">
                    {isVideo ? (
                        <video
                            src={existingMedia.original_path}
                            className="cms-media-card__video"
                            controls
                            preload="metadata"
                        />
                    ) : (
                        <img
                            src={thumbnailUrl}
                            alt={mediaKey}
                            className="cms-media-card__image"
                        />
                    )}
                </div>
                <div className="cms-media-card__info">
                    <span className="cms-media-card__title">{title || mediaKey}</span>
                    {existingMedia.width && (
                        <span className="cms-media-card__dims">{existingMedia.width}×{existingMedia.height}</span>
                    )}
                </div>
                {notice && (
                    <p className="cms-media-card__notice"><AlertTriangle size={14} aria-hidden="true" /> {notice}</p>
                )}
                {/* Always visible, so it works on phones and tablets too (no hover needed). */}
                <div className="cms-media-card__actions">
                    <button type="button" onClick={() => setIsChanging(!isChanging)} className="cms-media-card__action cms-media-card__action--primary" aria-expanded={isChanging}>
                        <RefreshCw size={14} aria-hidden="true" /> Replace
                    </button>
                    {!isVideo && onCrop && (
                        <button type="button" onClick={handleCrop} className="cms-media-card__action">
                            <Crop size={14} aria-hidden="true" /> Crop
                        </button>
                    )}
                    <a
                        href={route('admin.landing.media.download', { media: existingMedia.id })}
                        className="cms-media-card__action"
                        onClick={handleDownload}
                    >
                        <Download size={14} aria-hidden="true" /> Download
                    </a>
                    <button type="button" onClick={() => onDelete(existingMedia)} className="cms-media-card__action cms-media-card__action--danger">
                        <Trash2 size={14} aria-hidden="true" /> Delete
                    </button>
                </div>

                {isChanging && (
                    <div className="cms-media-card__changer">
                        <div className="cms-media-slot__toggle">
                            <button
                                onClick={() => setMode('upload')}
                                className={`cms-media-slot__toggle-btn ${mode === 'upload' ? 'cms-media-slot__toggle-btn--active' : ''}`}
                            >
                                Upload New
                            </button>
                            <button
                                onClick={() => setMode('library')}
                                className={`cms-media-slot__toggle-btn ${mode === 'library' ? 'cms-media-slot__toggle-btn--active' : ''}`}
                            >
                                From Library
                            </button>
                        </div>

                        {mode === 'upload' ? (
                            <div className="cms-uploader cms-uploader--compact">
                                {renderUploadInput()}
                                {isUploading && (
                                    <div className="cms-uploader__progress">
                                        <div className="cms-uploader__spinner" />
                                        <span>Uploading...</span>
                                    </div>
                                )}
                                {uploadError && (
                                    <div className="cms-uploader__error">{uploadError}</div>
                                )}
                            </div>
                        ) : (
                            <div className="cms-library">
                                <div className="cms-library__search">
                                    <Search size={14} className="cms-library__search-icon" />
                                    <input
                                        type="text"
                                        value={librarySearch}
                                        onChange={(e) => setLibrarySearch(e.target.value)}
                                        placeholder="Search media library..."
                                        className="cms-library__search-input"
                                    />
                                </div>
                                <div className="cms-library__grid custom-scrollbar">
                                    {libraryLoading ? (
                                        <div className="cms-library__empty">
                                            <div className="cms-uploader__spinner" />
                                            <span>Loading library...</span>
                                        </div>
                                    ) : filteredLibrary.length === 0 ? (
                                        <div className="cms-library__empty">
                                            {librarySearch ? 'No media matches your search.' : 'No media in the library yet.'}
                                        </div>
                                    ) : (
                                        filteredLibrary.map(item => (
                                            <button
                                                key={item.id}
                                                onClick={() => { handleSelectFromLibrary(item); setIsChanging(false); }}
                                                className="cms-library__item"
                                            >
                                                <div className="cms-library__item-preview">
                                                    {item.mime_type?.startsWith('video/') ? (
                                                        <video src={item.original_path} className="cms-library__item-thumb" preload="metadata" />
                                                    ) : (
                                                        <img src={item.thumbnail_path || item.original_path} alt={item.media_key} className="cms-library__item-thumb" />
                                                    )}
                                                    <div className="cms-library__item-overlay">Use</div>
                                                </div>
                                                <div className="cms-library__item-info">
                                                    <span className="cms-library__item-key">{item.media_key}</span>
                                                    <span className="cms-library__item-section">{item.section_title || item.section_key}</span>
                                                </div>
                                            </button>
                                        ))
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        );
    }

    return (
        <div className="cms-media-slot">
            <div className="cms-media-slot__header">
                <span className="cms-media-slot__key">{title || mediaKey}</span>
                <div className="cms-media-slot__toggle">
                    <button
                        onClick={() => setMode('upload')}
                        className={`cms-media-slot__toggle-btn ${mode === 'upload' ? 'cms-media-slot__toggle-btn--active' : ''}`}
                    >
                        Upload New
                    </button>
                    <button
                        onClick={() => setMode('library')}
                        className={`cms-media-slot__toggle-btn ${mode === 'library' ? 'cms-media-slot__toggle-btn--active' : ''}`}
                    >
                        From Library
                    </button>
                </div>
            </div>

            {notice && (
                <p className="cms-media-card__notice"><AlertTriangle size={14} aria-hidden="true" /> {notice}</p>
            )}
            {mode === 'upload' ? (
                <div className="cms-uploader">
                    {renderUploadInput()}
                    {isUploading && (
                        <div className="cms-uploader__progress">
                            <div className="cms-uploader__spinner" />
                            <span>Uploading...</span>
                        </div>
                    )}
                    {uploadError && (
                        <div className="cms-uploader__error">{uploadError}</div>
                    )}
                </div>
            ) : (
                <div className="cms-library">
                    <div className="cms-library__search">
                        <Search size={14} className="cms-library__search-icon" />
                        <input
                            type="text"
                            value={librarySearch}
                            onChange={(e) => setLibrarySearch(e.target.value)}
                            placeholder="Search media library..."
                            className="cms-library__search-input"
                        />
                    </div>
                    <div className="cms-library__grid custom-scrollbar">
                        {libraryLoading ? (
                            <div className="cms-library__empty">
                                <div className="cms-uploader__spinner" />
                                <span>Loading library...</span>
                            </div>
                        ) : filteredLibrary.length === 0 ? (
                            <div className="cms-library__empty">
                                {librarySearch ? 'No media matches your search.' : 'No media in the library yet.'}
                            </div>
                        ) : (
                            filteredLibrary.map(item => (
                                <button
                                    key={item.id}
                                    onClick={() => handleSelectFromLibrary(item)}
                                    className="cms-library__item"
                                >
                                    <div className="cms-library__item-preview">
                                        {item.mime_type?.startsWith('video/') ? (
                                            <video src={item.original_path} className="cms-library__item-thumb" preload="metadata" />
                                        ) : (
                                            <img src={item.thumbnail_path || item.original_path} alt={item.media_key} className="cms-library__item-thumb" />
                                        )}
                                        <div className="cms-library__item-overlay">Use</div>
                                    </div>
                                    <div className="cms-library__item-info">
                                        <span className="cms-library__item-key">{item.media_key}</span>
                                        <span className="cms-library__item-section">{item.section_title || item.section_key}</span>
                                    </div>
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
