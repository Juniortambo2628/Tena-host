import React, { useRef, useCallback, useState } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';
import { notify } from '@/Components/Toast';
import './EmailTemplateEditor.css';

const QUILL_MODULES = {
    toolbar: [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'link'],
        ['image'],
        ['clean'],
    ],
};

const QUILL_FORMATS = [
    'header', 'bold', 'italic', 'underline', 'strike',
    'list', 'blockquote', 'link', 'image',
];

export default function EmailTemplateEditor({
    label,
    value,
    onChange,
    placeholder = 'Write your email content...',
    variables = [],
    hint,
}) {
    const quillRef = useRef(null);
    const fileInputRef = useRef(null);
    const [uploading, setUploading] = useState(false);

    const insertVariable = useCallback((variable) => {
        const quill = quillRef.current?.getEditor();
        if (!quill) return;

        const range = quill.getSelection();
        const insertIndex = range ? range.index : quill.getLength() - 1;

        quill.insertText(insertIndex, `{{${variable}}}`, 'user');
        quill.setSelection(insertIndex + variable.length + 4);
        quill.focus();
    }, []);

    const handleImageUpload = useCallback(async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            notify.error('Please select an image file.');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            notify.error('Image must be less than 5MB.');
            return;
        }

        setUploading(true);

        try {
            const formData = new FormData();
            formData.append('image', file);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/admin/email-images/upload', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok && data.url) {
                const quill = quillRef.current?.getEditor();
                if (quill) {
                    const range = quill.getSelection();
                    const insertIndex = range ? range.index : quill.getLength() - 1;
                    quill.insertEmbed(insertIndex, 'image', data.url);
                    quill.setSelection(insertIndex + 1);
                }
            } else {
                notify.error(data.message || 'Upload failed. Please try again.');
            }
        } catch (err) {
            notify.error('Upload failed. Please check your connection and try again.');
        } finally {
            setUploading(false);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    }, []);

    const handleQuillImageHandler = useCallback(() => {
        fileInputRef.current?.click();
    }, []);

    return (
        <div className="email-template-editor">
            {label && (
                <label className="email-template-editor__label">{label}</label>
            )}

            {variables.length > 0 && (
                <div className="email-template-editor__pills">
                    <span className="email-template-editor__pills-label">Insert:</span>
                    {variables.map((v) => (
                        <button
                            key={v.key}
                            type="button"
                            className="email-template-editor__pill"
                            onClick={() => insertVariable(v.key)}
                            title={v.description}
                        >
                            {v.label}
                        </button>
                    ))}
                </div>
            )}

            <div className="email-template-editor__quill-wrap">
                <ReactQuill
                    ref={quillRef}
                    theme="snow"
                    value={value || ''}
                    onChange={onChange}
                    modules={{
                        toolbar: {
                            container: QUILL_MODULES.toolbar,
                            handlers: {
                                image: handleQuillImageHandler,
                            },
                        },
                    }}
                    formats={QUILL_FORMATS}
                    placeholder={placeholder}
                />
            </div>

            <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                onChange={handleImageUpload}
                className="email-template-editor__file-input"
            />

            {uploading && (
                <div className="email-template-editor__uploading">
                    Uploading image...
                </div>
            )}

            {hint && <p className="email-template-editor__hint">{hint}</p>}
        </div>
    );
}
