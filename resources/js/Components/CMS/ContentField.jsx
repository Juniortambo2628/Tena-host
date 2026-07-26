import React, { useState } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';
import IconPicker from './IconPicker';
import './ContentField.css';

const QUILL_MODULES = {
    toolbar: [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'link'],
        ['clean'],
    ],
};

const QUILL_FORMATS = [
    'header', 'bold', 'italic', 'underline', 'strike',
    'list', 'blockquote', 'link',
];

export default function ContentField({
    label,
    value,
    onChange,
    type = 'text',
    placeholder = '',
    rows = 3,
    hint,
    required = false,
    options = null,
}) {
    const [showPreview, setShowPreview] = useState(false);

    const isIconField = type === 'icon' || (label && label.toLowerCase().includes('icon'));
    const isSelect = type === 'select' || (label && label.toLowerCase() === 'media type');
    const isRichText = type === 'richtext';
    const isHtml = type === 'html';

    if (isSelect) {
        const selectOptions = options || [
            { value: 'image', label: 'Image' },
            { value: 'video', label: 'Video' },
        ];
        return (
            <div className="cms-field">
                {label && (
                    <label className="cms-field__label">
                        {label}
                        {required && <span className="cms-field__required">*</span>}
                    </label>
                )}
                <select
                    value={value || ''}
                    onChange={(e) => onChange(e.target.value)}
                    className="cms-field__select"
                >
                    {selectOptions.map(opt => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                </select>
                {hint && <p className="cms-field__hint">{hint}</p>}
            </div>
        );
    }

    if (isIconField) {
        return (
            <div className="cms-field">
                {label && (
                    <label className="cms-field__label">
                        {label}
                        {required && <span className="cms-field__required">*</span>}
                    </label>
                )}
                <IconPicker
                    value={value || ''}
                    onChange={onChange}
                />
                {hint && <p className="cms-field__hint">{hint}</p>}
            </div>
        );
    }

    if (isRichText) {
        return (
            <div className="cms-field">
                {label && (
                    <label className="cms-field__label">
                        {label}
                        {required && <span className="cms-field__required">*</span>}
                    </label>
                )}
                <div className="cms-field__quill-wrap">
                    <ReactQuill
                        theme="snow"
                        value={value || ''}
                        onChange={onChange}
                        modules={QUILL_MODULES}
                        formats={QUILL_FORMATS}
                        placeholder={placeholder}
                    />
                </div>
                {hint && <p className="cms-field__hint">{hint}</p>}
            </div>
        );
    }

    if (isHtml) {
        return (
            <div className="cms-field">
                {label && (
                    <label className="cms-field__label">
                        {label}
                        {required && <span className="cms-field__required">*</span>}
                    </label>
                )}
                <div className="cms-field__html-wrap">
                    <div className="cms-field__html-tabs">
                        <button
                            type="button"
                            className={`cms-field__html-tab ${!showPreview ? 'cms-field__html-tab--active' : ''}`}
                            onClick={() => setShowPreview(false)}
                        >
                            Code
                        </button>
                        <button
                            type="button"
                            className={`cms-field__html-tab ${showPreview ? 'cms-field__html-tab--active' : ''}`}
                            onClick={() => setShowPreview(true)}
                        >
                            Preview
                        </button>
                    </div>
                    {!showPreview ? (
                        <textarea
                            value={value || ''}
                            onChange={(e) => onChange(e.target.value)}
                            placeholder={placeholder}
                            rows={rows}
                            className="cms-field__textarea cms-field__textarea--code"
                        />
                    ) : (
                        <div
                            className="cms-field__html-preview"
                            dangerouslySetInnerHTML={{ __html: value || '<span class="text-gray-400">Nothing to preview</span>' }}
                        />
                    )}
                </div>
                {hint && <p className="cms-field__hint">{hint}</p>}
            </div>
        );
    }

    return (
        <div className="cms-field">
            {label && (
                <label className="cms-field__label">
                    {label}
                    {required && <span className="cms-field__required">*</span>}
                </label>
            )}
            {type === 'textarea' ? (
                <textarea
                    value={value || ''}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={placeholder}
                    rows={rows}
                    className="cms-field__textarea"
                />
            ) : (
                <input
                    type={type}
                    value={value || ''}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={placeholder}
                    className="cms-field__input"
                />
            )}
            {hint && <p className="cms-field__hint">{hint}</p>}
        </div>
    );
}
