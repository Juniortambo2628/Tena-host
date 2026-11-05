import React from 'react';
import { usePage } from '@inertiajs/react';
import { ImageUp } from 'lucide-react';
import { usePublic } from '@/Components/Public/PublicContext';
import './EditablePicture.css';

/**
 * The admin's way into a picture on the public site: while an admin is
 * signed in, the picture gets a "Replace picture" button that opens Admin →
 * Public Pages on this page, with the section's Media tab open at `slot`.
 * Everyone else gets the children unchanged.
 */
export function pictureEditUrl(pageSlug, section, slot) {
    const query = new URLSearchParams({ page: pageSlug || 'home', section: String(section.id), slot });
    return `/admin/landing?${query}`;
}

export function useCanEditPictures() {
    const { auth } = usePage().props;
    return auth?.user?.role === 'admin';
}

export default function EditablePicture({ section, slot, className = '', children }) {
    const canEdit = useCanEditPictures();
    const { page } = usePublic();
    if (!canEdit || !section?.id || !slot) return children;

    return (
        <div className={`editable-picture ${className}`}>
            {children}
            <a className="editable-picture__link" href={pictureEditUrl(page?.slug, section, slot)}>
                <ImageUp size={14} aria-hidden="true" /> Replace picture
            </a>
        </div>
    );
}
