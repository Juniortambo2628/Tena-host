import React, { useState, useCallback, useMemo, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { router } from '@inertiajs/react';
import { Dialog, Transition } from '@headlessui/react';
import { notify } from '@/Components/Toast';
import ContentField from './ContentField';
import MediaUploader from './MediaUploader';
import CropModal from './CropModal';
import JsonArrayEditor from './JsonArrayEditor';
import './SectionEditor.css';

function groupContentKeys(content) {
    const simple = {};
    const arrays = {};
    Object.entries(content).forEach(([key, value]) => {
        const match = key.match(/^([^.]+)\.(\d+)\.(.+)$/);
        if (match) {
            const [, arrName, idx, field] = match;
            if (!arrays[arrName]) arrays[arrName] = {};
            if (!arrays[arrName][idx]) arrays[arrName][idx] = {};
            arrays[arrName][idx][field] = { key, value };
        } else {
            simple[key] = value;
        }
    });
    return { simple, arrays };
}

function detectFieldType(key, value) {
    const lowerKey = key.toLowerCase();
    if (lowerKey.includes('icon')) return 'icon';
    if (lowerKey === 'media_type') return 'select';
    if (lowerKey === 'description' || lowerKey === 'text' || lowerKey === 'body') return 'richtext';
    if (lowerKey === 'cta_text' || lowerKey === 'badge' || lowerKey === 'subtitle') return 'richtext';
    if (typeof value === 'string' && value.startsWith('[')) return 'json_array';
    if (typeof value === 'string' && value.includes('<')) return 'html';
    return 'text';
}

function getTabLabel(tabId) {
    if (tabId === 'content') return 'Content';
    if (tabId === 'media') return 'Media';
    if (tabId.startsWith('array_')) {
        const name = tabId.replace('array_', '');
        return name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }
    return tabId;
}

export default function SectionEditor({ section, onUpdate, onMediaUpload, onMediaDelete, onMediaCrop }) {
    const [isOpen, setIsOpen] = useState(false);
    const [activeTab, setActiveTab] = useState(0);
    const [hasChanges, setHasChanges] = useState(false);
    const [localContent, setLocalContent] = useState(section.content || {});
    const [localMedia, setLocalMedia] = useState(section.media || {});
    const [cropMedia, setCropMedia] = useState(null);
    const [newMediaKey, setNewMediaKey] = useState('');

    const { simple, arrays } = useMemo(() => groupContentKeys(localContent), [localContent]);

    const tabs = useMemo(() => {
        const t = [];
        if (Object.keys(simple).length > 0) {
            t.push({ id: 'content', label: 'Content' });
        }
        Object.keys(arrays).forEach((arrName) => {
            t.push({ id: `array_${arrName}`, label: arrName.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) });
        });
        t.push({ id: 'media', label: 'Media' });
        if (t.length === 0) {
            t.push({ id: 'content', label: 'Content' });
        }
        return t;
    }, [simple, arrays]);

    useEffect(() => {
        if (isOpen && activeTab >= tabs.length) {
            setActiveTab(0);
        }
    }, [isOpen, activeTab, tabs.length]);

    const handleContentChange = useCallback((key, value) => {
        setLocalContent(prev => ({ ...prev, [key]: value }));
        setHasChanges(true);
    }, []);

    const handleSave = () => {
        const items = Object.entries(localContent).map(([content_key, value]) => ({
            content_key,
            value,
            type: value && typeof value === 'string' && value.startsWith('[') ? 'json' : 'text',
        }));
        router.put(route('admin.landing.content.update'), {
            section_id: section.id,
            items,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setHasChanges(false);
                notify.success('Content saved successfully');
                if (onUpdate) onUpdate();
            },
            onError: () => notify.error('Failed to save content'),
        });
    };

    const handleClose = () => {
        setIsOpen(false);
        setActiveTab(0);
    };

    const openModal = () => setIsOpen(true);

    const handleMediaUpload = (media) => {
        setLocalMedia(prev => ({ ...prev, [media.media_key]: media }));
        setHasChanges(true);
        if (onMediaUpload) onMediaUpload(media);
    };

    const handleMediaDelete = (media) => {
        router.delete(route('admin.landing.media.destroy', { media: media.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setLocalMedia(prev => {
                    const next = { ...prev };
                    delete next[media.media_key];
                    return next;
                });
                setHasChanges(true);
                notify.success('Media deleted');
                if (onMediaDelete) onMediaDelete(media);
            },
            onError: () => notify.error('Failed to delete media'),
        });
    };

    const handleCropOpen = (media) => {
        setCropMedia(media);
    };

    const handleAddMediaKey = () => {
        const key = newMediaKey.trim().replace(/\s+/g, '_').toLowerCase();
        if (!key) return;
        if (localMedia[key]) {
            notify.error('This media key already exists');
            return;
        }
        setLocalMedia(prev => ({ ...prev, [key]: null }));
        setNewMediaKey('');
        setHasChanges(true);
    };

    const suggestedMediaKeys = useMemo(() => {
        const existing = Object.keys(localMedia);
        const contentKeys = Object.keys(localContent);

        const arrayNames = new Set();
        contentKeys.forEach(k => {
            const match = k.match(/^([^.]+)\.\d+\..+$/);
            if (match) arrayNames.add(match[1]);
        });

        const suggestions = [];

        if (arrayNames.size > 0) {
            arrayNames.forEach(arrName => {
                const count = contentKeys.filter(k => k.startsWith(arrName + '.')).length;
                const uniqueIndices = new Set();
                contentKeys.forEach(k => {
                    const m = k.match(new RegExp(`^${arrName}\\.(\\d+)\\..+$`));
                    if (m) uniqueIndices.add(parseInt(m[1]));
                });
                for (const idx of [...uniqueIndices].sort((a, b) => a - b)) {
                    const key = `${arrName}_${idx}_image`;
                    if (!existing.includes(key)) {
                        suggestions.push({ key, label: `${arrName.replace(/_/g, ' ')} ${idx + 1} image` });
                    }
                }
            });
        }

        const commonPatterns = [
            'main_image', 'hero_image', 'background', 'banner', 'logo',
            'feature_image', 'cta_image', 'icon', 'thumbnail',
        ];
        commonPatterns.forEach(key => {
            if (!existing.includes(key)) {
                suggestions.push({ key, label: key.replace(/_/g, ' ') });
            }
        });

        return suggestions;
    }, [localMedia, localContent]);

    const renderContentTab = () => (
        <div className="editor-tab__content">
            <h3 className="editor-tab__heading">Text Fields</h3>
            <div className="editor-tab__fields">
                {Object.entries(simple).map(([key, value]) => (
                    <ContentField
                        key={key}
                        label={key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}
                        value={value}
                        onChange={(v) => handleContentChange(key, v)}
                        type={detectFieldType(key, value)}
                        rows={typeof value === 'string' && value.length > 100 ? 4 : 2}
                    />
                ))}
            </div>
        </div>
    );

    const renderArrayTab = (arrName) => {
        const items = arrays[arrName] || {};
        return (
            <div className="editor-tab__content">
                <h3 className="editor-tab__heading">{arrName.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}</h3>
                <div className="editor-tab__items">
                    {Object.entries(items).map(([idx, fields]) => {
                        const displayName = fields.title?.value || fields.label?.value || fields.step?.value || `Item ${Number(idx) + 1}`;
                        return (
                            <div key={`${arrName}-${idx}`} className="editor-tab__item">
                                <div className="editor-tab__item-header">
                                    <span className="editor-tab__item-number">{Number(idx) + 1}</span>
                                    <span className="editor-tab__item-title">{displayName}</span>
                                </div>
                                <div className="editor-tab__item-fields">
                                    {Object.entries(fields).map(([field, { key, value }]) => {
                                        const fieldType = detectFieldType(field, value);
                                        if (fieldType === 'json_array') {
                                            return (
                                                <JsonArrayEditor
                                                    key={key}
                                                    label={field.charAt(0).toUpperCase() + field.slice(1)}
                                                    value={value}
                                                    onChange={(v) => handleContentChange(key, v)}
                                                />
                                            );
                                        }
                                        return (
                                            <ContentField
                                                key={key}
                                                label={field.charAt(0).toUpperCase() + field.slice(1)}
                                                value={value}
                                                onChange={(v) => handleContentChange(key, v)}
                                                type={fieldType}
                                                rows={field === 'description' || field === 'desc' ? 3 : 2}
                                            />
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        );
    };

    const renderMediaTab = () => {
        const mediaKeys = Object.keys(localMedia);
        return (
            <div className="editor-tab__content">
                <h3 className="editor-tab__heading">Media Files</h3>
                {mediaKeys.length > 0 && (
                    <div className="editor-tab__media-grid">
                        {mediaKeys.map(key => (
                            <MediaUploader
                                key={`${key}-${localMedia[key]?.id || 'empty'}`}
                                sectionId={section.id}
                                mediaKey={key}
                                existingMedia={localMedia[key] ? { ...localMedia[key], id: localMedia[key].id || localMedia[key] } : null}
                                onUpload={handleMediaUpload}
                                onDelete={handleMediaDelete}
                                onCrop={handleCropOpen}
                            />
                        ))}
                    </div>
                )}
                <div className="editor-tab__add-media">
                    <div className="editor-tab__add-media-row">
                        <select
                            value={newMediaKey}
                            onChange={(e) => setNewMediaKey(e.target.value)}
                            className="editor-tab__add-media-select"
                        >
                            <option value="">Select a media key...</option>
                            {suggestedMediaKeys.map(({ key, label }) => (
                                <option key={key} value={key}>{label} ({key})</option>
                            ))}
                            <option value="__custom">Custom key...</option>
                        </select>
                        {newMediaKey === '__custom' && (
                            <input
                                type="text"
                                value=""
                                onChange={(e) => {
                                    const val = e.target.value.trim().replace(/\s+/g, '_').toLowerCase();
                                    setNewMediaKey(val);
                                }}
                                onKeyDown={(e) => e.key === 'Enter' && handleAddMediaKey()}
                                placeholder="Type custom key..."
                                className="editor-tab__add-media-input"
                                autoFocus
                            />
                        )}
                        <button onClick={handleAddMediaKey} className="editor-tab__add-media-btn" disabled={!newMediaKey || newMediaKey === '__custom'}>
                            Add
                        </button>
                    </div>
                </div>
            </div>
        );
    };

    const renderTab = (tab) => {
        if (tab.id === 'content') return renderContentTab();
        if (tab.id === 'media') return renderMediaTab();
        if (tab.id.startsWith('array_')) {
            const arrName = tab.id.replace('array_', '');
            return renderArrayTab(arrName);
        }
        return null;
    };

    return (
        <>
            <div className="cms-section-card" onClick={openModal}>
                <div className="cms-section-card__left">
                    <h3 className="cms-section-card__title">{section.title || section.section_key}</h3>
                    <p className="cms-section-card__meta">
                        {Object.keys(localContent).length} fields · {Object.keys(localMedia).length} media
                    </p>
                </div>
                <div className="cms-section-card__right" onClick={(e) => e.stopPropagation()}>
                    <label className="cms-section-card__toggle">
                        <input
                            type="checkbox"
                            checked={section.is_active}
                            onChange={(e) => {
                                router.put(route('admin.landing.sections.update', { section: section.id }), {
                                    is_active: e.target.checked,
                                }, {
                                    preserveScroll: true,
                                    onSuccess: () => notify.success(e.target.checked ? 'Section enabled' : 'Section disabled'),
                                });
                            }}
                        />
                        <span className="cms-section-card__toggle-slider" />
                    </label>
                    <button onClick={openModal} className="cms-section-card__edit-btn">
                        Edit
                    </button>
                </div>
            </div>

            <Transition appear show={isOpen} as={React.Fragment}>
                <Dialog as="div" className="editor-modal" onClose={handleClose}>
                    <Transition.Child
                        as={React.Fragment}
                        enter="editor-modal__backdrop-enter"
                        enterFrom="editor-modal__backdrop-enter-from"
                        enterTo="editor-modal__backdrop-enter-to"
                        leave="editor-modal__backdrop-leave"
                        leaveFrom="editor-modal__backdrop-leave-from"
                        leaveTo="editor-modal__backdrop-leave-to"
                    >
                        <div className="editor-modal__backdrop" />
                    </Transition.Child>

                    <div className="editor-modal__container">
                        <Transition.Child
                            as={React.Fragment}
                            enter="editor-modal__panel-enter"
                            enterFrom="editor-modal__panel-enter-from"
                            enterTo="editor-modal__panel-enter-to"
                            leave="editor-modal__panel-leave"
                            leaveFrom="editor-modal__panel-leave-from"
                            leaveTo="editor-modal__panel-leave-to"
                        >
                            <Dialog.Panel className="editor-modal__panel">
                                <button onClick={handleClose} className="editor-modal__close">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                <div className="editor-modal__layout">
                                    <div className="editor-modal__sidebar">
                                        <div className="editor-modal__sidebar-logo">
                                            <div className="editor-modal__sidebar-logo-icon">
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </div>
                                            <span className="editor-modal__sidebar-logo-text">
                                                {section.title || section.section_key}
                                            </span>
                                        </div>

                                        <div className="editor-modal__sidebar-tabs">
                                            {tabs.map((tab, index) => (
                                                <button
                                                    key={tab.id}
                                                    onClick={() => setActiveTab(index)}
                                                    className={`editor-modal__sidebar-tab ${activeTab === index ? 'editor-modal__sidebar-tab--active' : ''}`}
                                                >
                                                    <span className={`editor-modal__sidebar-tab-number ${activeTab === index ? 'editor-modal__sidebar-tab-number--active' : activeTab > index ? 'editor-modal__sidebar-tab-number--completed' : ''}`}>
                                                        {activeTab > index ? (
                                                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        ) : (
                                                            index + 1
                                                        )}
                                                    </span>
                                                    <span className={`editor-modal__sidebar-tab-label ${activeTab === index ? 'editor-modal__sidebar-tab-label--active' : ''}`}>
                                                        {tab.label}
                                                    </span>
                                                </button>
                                            ))}
                                        </div>

                                        <div className="editor-modal__sidebar-footer">
                                            Step {activeTab + 1} of {tabs.length}
                                        </div>
                                    </div>

                                    <div className="editor-modal__content">
                                        <div className="editor-modal__content-inner custom-scrollbar">
                                            {tabs.map((tab, index) => (
                                                <div key={tab.id} className={`editor-modal__tab-panel ${activeTab === index ? '' : 'editor-modal__tab-panel--hidden'}`}>
                                                    {activeTab === index && renderTab(tab)}
                                                </div>
                                            ))}
                                        </div>

                                        <div className="editor-modal__footer">
                                            <button onClick={handleClose} className="editor-modal__cancel">
                                                Cancel
                                            </button>
                                            <button
                                                onClick={handleSave}
                                                disabled={!hasChanges}
                                                className={`editor-modal__save ${hasChanges ? '' : 'editor-modal__save--disabled'}`}
                                            >
                                                {hasChanges ? 'Save Changes' : 'No Changes'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </Dialog.Panel>
                        </Transition.Child>
                    </div>
                </Dialog>
            </Transition>

            {createPortal(
                <CropModal
                    media={cropMedia}
                    isOpen={!!cropMedia}
                    onClose={() => setCropMedia(null)}
                    onCropped={() => {
                        setCropMedia(null);
                        if (onUpdate) onUpdate();
                    }}
                />,
                document.body
            )}
        </>
    );
}
