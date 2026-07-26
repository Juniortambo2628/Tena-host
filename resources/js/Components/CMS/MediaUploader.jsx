import React, { useState, useEffect, useMemo } from 'react';
import { FilePond, registerPlugin } from 'react-filepond';
import { router } from '@inertiajs/react';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import { Search } from 'lucide-react';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';
import './MediaUploader.css';

registerPlugin(FilePondPluginImagePreview);

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
}) {
    const [isUploading, setIsUploading] = useState(false);
    const [mode, setMode] = useState('upload');
    const [libraryItems, setLibraryItems] = useState([]);
    const [libraryLoading, setLibraryLoading] = useState(false);
    const [librarySearch, setLibrarySearch] = useState('');

    const serverConfig = {
        process: {
            url: route('admin.landing.media.upload', { section: sectionId }),
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            formData: (file) => {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('media_key', mediaKey);
                fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
                return fd;
            },
            onload: (response) => {
                setIsUploading(false);
                const data = JSON.parse(response);
                if (onUpload) onUpload(data.media);
                return data;
            },
            onerror: () => {
                setIsUploading(false);
            },
        },
        revert: null,
        restore: null,
        load: null,
        fetch: null,
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
                    <div className="cms-media-card__overlay">
                        <button onClick={handleCrop} className="cms-media-card__action" title="Crop & Reposition">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <a
                            href={route('admin.landing.media.download', { media: existingMedia.id })}
                            className="cms-media-card__action"
                            title="Download"
                            onClick={handleDownload}
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                        <button onClick={() => onDelete(existingMedia)} className="cms-media-card__action cms-media-card__action--danger" title="Delete">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div className="cms-media-card__info">
                    <span className="cms-media-card__key">{mediaKey}</span>
                    {existingMedia.width && (
                        <span className="cms-media-card__dims">{existingMedia.width}x{existingMedia.height}</span>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div className="cms-media-slot">
            <div className="cms-media-slot__header">
                <span className="cms-media-slot__key">{mediaKey}</span>
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

            {mode === 'upload' ? (
                <div className="cms-uploader">
                    <FilePond
                        server={serverConfig}
                        labelIdle={label}
                        acceptedFileTypes={accept || ['image/*', 'video/*']}
                        maxFileSize={maxSize}
                        maxFiles={1}
                        imagePreviewHeight={170}
                        imageCropAspectRatio="16:10"
                        imageResizeTargetWidth={1200}
                        imageResizeTargetHeight={675}
                        stylePanelLayout="compact"
                        styleButtonRemoveItemPosition="right"
                        onaddfilestart={() => setIsUploading(true)}
                        onprocessfile={() => setIsUploading(false)}
                        onerror={() => setIsUploading(false)}
                    />
                    {isUploading && (
                        <div className="cms-uploader__progress">
                            <div className="cms-uploader__spinner" />
                            <span>Optimizing...</span>
                        </div>
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
