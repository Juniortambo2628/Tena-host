import React, { useState } from 'react';
import IconPicker from './IconPicker';
import './JsonArrayEditor.css';

export default function JsonArrayEditor({ label, value, onChange }) {
    let items = [];
    try {
        items = typeof value === 'string' ? JSON.parse(value) : (Array.isArray(value) ? value : []);
    } catch {
        items = [];
    }

    const [localItems, setLocalItems] = useState(items);

    const updateItem = (index, field, newValue) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: newValue };
        setLocalItems(updated);
        onChange(JSON.stringify(updated));
    };

    const addItem = () => {
        const updated = [...localItems, { icon: 'fas fa-star', title: 'New Feature', desc: 'Description here' }];
        setLocalItems(updated);
        onChange(JSON.stringify(updated));
    };

    const removeItem = (index) => {
        const updated = localItems.filter((_, i) => i !== index);
        setLocalItems(updated);
        onChange(JSON.stringify(updated));
    };

    return (
        <div className="json-array-editor">
            <div className="json-array-editor__header">
                <label className="json-array-editor__label">{label}</label>
                <button type="button" onClick={addItem} className="json-array-editor__add">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                    Add Item
                </button>
            </div>
            <div className="json-array-editor__items">
                {localItems.map((item, idx) => (
                    <div key={idx} className="json-array-editor__item">
                        <div className="json-array-editor__item-header">
                            <span className="json-array-editor__item-num">{idx + 1}</span>
                            <span className="json-array-editor__item-title">{item.title || 'Untitled'}</span>
                            <button type="button" onClick={() => removeItem(idx)} className="json-array-editor__item-remove" title="Remove">
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                        <div className="json-array-editor__item-fields">
                            <div className="json-array-editor__field">
                                <label className="json-array-editor__field-label">Icon</label>
                                <IconPicker
                                    value={item.icon || ''}
                                    onChange={(v) => updateItem(idx, 'icon', v)}
                                />
                            </div>
                            <div className="json-array-editor__field">
                                <label className="json-array-editor__field-label">Title</label>
                                <input
                                    type="text"
                                    value={item.title || ''}
                                    onChange={(e) => updateItem(idx, 'title', e.target.value)}
                                    className="json-array-editor__input"
                                />
                            </div>
                            <div className="json-array-editor__field">
                                <label className="json-array-editor__field-label">Description</label>
                                <textarea
                                    value={item.desc || ''}
                                    onChange={(e) => updateItem(idx, 'desc', e.target.value)}
                                    rows={2}
                                    className="json-array-editor__textarea"
                                />
                            </div>
                        </div>
                    </div>
                ))}
                {localItems.length === 0 && (
                    <button type="button" onClick={addItem} className="json-array-editor__empty-add">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                        Add First Item
                    </button>
                )}
            </div>
        </div>
    );
}
