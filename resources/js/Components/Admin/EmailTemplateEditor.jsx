import React, { useRef, useCallback, useState } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';
import axios from 'axios';
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
    imageMaxWidth: initialMaxWidth = 600,
}) {
    const quillRef = useRef(null);
    const fileInputRef = useRef(null);
    const [uploading, setUploading] = useState(false);
    const [imageMaxWidth, setImageMaxWidth] = useState(initialMaxWidth);

    const insertVariable = useCallback((variable) => {
        const quill = quillRef.current?.getEditor();
        if (!quill) return;

        const range = quill.getSelection();
        const insertIndex = range ? range.index : quill.getLength() - 1;

        quill.insertText(insertIndex, `{{${variable}}}`, 'user');
        quill.setSelection(insertIndex + variable.length + 4);
        quill.focus();
    }, []);

    const resizeImage = useCallback((file) => {
        const maxWidth = parseInt(imageMaxWidth, 10) || 600;
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    if (img.width <= maxWidth) {
                        resolve(file);
                        return;
                    }
                    const canvas = document.createElement('canvas');
                    const ratio = maxWidth / img.width;
                    canvas.width = maxWidth;
                    canvas.height = img.height * ratio;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob((blob) => {
                        resolve(new File([blob], file.name, { type: file.type }));
                    }, file.type, 0.85);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }, [imageMaxWidth]);

    const handleImageUpload = useCallback(async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            notify.error('Please select an image file.');
            return;
        }

        setUploading(true);

        try {
            const resized = await resizeImage(file);
            const formData = new FormData();
            formData.append('image', resized);

            const { data } = await axios.post('/admin/email-images/upload', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (data?.url) {
                const quill = quillRef.current?.getEditor();
                if (quill) {
                    const range = quill.getSelection();
                    const insertIndex = range ? range.index : quill.getLength() - 1;
                    quill.insertEmbed(insertIndex, 'image', data.url);
                    quill.setSelection(insertIndex + 1);
                }
            }
        } catch (err) {
            notify.error(err.response?.data?.message || 'Upload failed. Please try again.');
        } finally {
            setUploading(false);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    }, [resizeImage]);

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

            <div className="email-template-editor__resize-row">
                <span className="email-template-editor__resize-label">Max image width:</span>
                <input
                    type="number"
                    min="100"
                    max="2000"
                    step="50"
                    value={imageMaxWidth}
                    onChange={(e) => setImageMaxWidth(e.target.value)}
                    className="email-template-editor__resize-input"
                />
                <span className="email-template-editor__resize-unit">px</span>
            </div>

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
