import React, { useRef, useCallback } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';
import './EmailTemplateEditor.css';

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

export default function EmailTemplateEditor({
    label,
    value,
    onChange,
    placeholder = 'Write your email content...',
    variables = [],
    hint,
}) {
    const quillRef = useRef(null);

    const insertVariable = useCallback((variable) => {
        const quill = quillRef.current?.getEditor();
        if (!quill) return;

        const range = quill.getSelection();
        const insertIndex = range ? range.index : quill.getLength() - 1;

        quill.insertText(insertIndex, `{{${variable}}}`, 'user');
        quill.setSelection(insertIndex + variable.length + 4);
        quill.focus();
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
                    modules={QUILL_MODULES}
                    formats={QUILL_FORMATS}
                    placeholder={placeholder}
                />
            </div>

            {hint && <p className="email-template-editor__hint">{hint}</p>}
        </div>
    );
}
