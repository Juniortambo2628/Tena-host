import React, { useState, useCallback } from 'react';
import { Head, router } from '@inertiajs/react';
import PageShell from '@/Layouts/PageShell';
import SectionEditor from '@/Components/CMS/SectionEditor';
import { notify } from '@/Components/Toast';
import { GripVertical, Eye } from 'lucide-react';
import './Index.css';

export default function Index({ sections: initialSections, mediaConfig }) {
    const [sections, setSections] = useState(initialSections);
    const [draggedIndex, setDraggedIndex] = useState(null);
    const [isSaving, setIsSaving] = useState(false);

    const handleDragStart = (e, index) => {
        setDraggedIndex(index);
        e.dataTransfer.effectAllowed = 'move';
    };

    const handleDragOver = (e, index) => {
        e.preventDefault();
        if (draggedIndex === null || draggedIndex === index) return;

        const newSections = [...sections];
        const dragged = newSections[draggedIndex];
        newSections.splice(draggedIndex, 1);
        newSections.splice(index, 0, dragged);

        setDraggedIndex(index);
        setSections(newSections);
    };

    const handleDragEnd = () => {
        setDraggedIndex(null);
        saveOrder();
    };

    const saveOrder = () => {
        setIsSaving(true);
        const order = sections.map(s => s.id);

        router.post(route('admin.landing.sections.reorder'), { order }, {
            preserveScroll: true,
            onFinish: () => setIsSaving(false),
            onSuccess: () => notify.success('Section order updated'),
            onError: () => notify.error('Failed to reorder sections'),
        });
    };

    const handleSectionUpdate = useCallback(() => {
        router.reload({ only: ['sections'], preserveScroll: true });
    }, []);

    return (
        <PageShell
            title="Landing Page CMS"
            subtitle="Manage all public landing page content, images, and section ordering"
            headTitle="Landing Page CMS"
            breadcrumbs={[{ label: 'Landing Page', href: route('admin.landing.index') }]}
            rootRoute="admin.dashboard"
            actions={[
                {
                    label: 'View Live Page',
                    onClick: () => window.open('/', '_blank'),
                    variant: 'ghost',
                    icon: <Eye size={14} />,
                },
            ]}
        >
            <Head title="Landing Page CMS" />

            <div className="landing-cms">
                <div className="landing-cms__header">
                    <div className="landing-cms__header-info">
                        <p className="landing-cms__header-text">
                            {sections.filter(s => s.is_active).length} of {sections.length} sections active
                        </p>
                        <p className="landing-cms__header-text">
                            Drag sections to reorder. Click a section to edit.
                        </p>
                    </div>
                    <div className="landing-cms__header-actions">
                        {isSaving && (
                            <span className="landing-cms__saving">
                                <div className="landing-cms__saving-dot" />
                                Saving...
                            </span>
                        )}
                    </div>
                </div>

                <div className="landing-cms__sections">
                    {sections.map((section, index) => (
                        <div
                            key={section.id}
                            className={`landing-cms__section-wrapper ${draggedIndex === index ? 'landing-cms__section-wrapper--dragging' : ''}`}
                            draggable
                            onDragStart={(e) => handleDragStart(e, index)}
                            onDragOver={(e) => handleDragOver(e, index)}
                            onDragEnd={handleDragEnd}
                        >
                            <div className="landing-cms__drag-handle">
                                <GripVertical size={16} />
                            </div>
                            <div className="landing-cms__section-editor">
                                <SectionEditor
                                    section={section}
                                    onUpdate={handleSectionUpdate}
                                    onMediaUpload={handleSectionUpdate}
                                    onMediaDelete={handleSectionUpdate}
                                />
                            </div>
                        </div>
                    ))}
                </div>

                <div className="landing-cms__footer">
                    <p className="landing-cms__footer-text">
                        Changes are saved automatically. Public page cache clears on save.
                    </p>
                </div>
            </div>
        </PageShell>
    );
}
