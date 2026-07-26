import React from 'react';
import { cn } from '@/lib/utils';
import './LayoutPrimitives.css';

const spanClasses = {
    2: 'layout-main-column--span-2',
    3: 'layout-main-column--span-3',
    4: 'layout-main-column--span-4',
    5: 'layout-main-column--span-5',
    6: 'layout-main-column--span-6',
    7: 'layout-main-column--span-7',
    8: 'layout-main-column--span-8',
    9: 'layout-main-column--span-9',
    10: 'layout-main-column--span-10',
    12: 'layout-main-column--span-12',
};

const sidebarSpanClasses = {
    2: 'layout-sidebar-column--span-2',
    3: 'layout-sidebar-column--span-3',
    4: 'layout-sidebar-column--span-4',
    5: 'layout-sidebar-column--span-5',
    7: 'layout-sidebar-column--span-7',
    8: 'layout-sidebar-column--span-8',
    9: 'layout-sidebar-column--span-9',
};

const gridColsClasses = {
    2: 'page-grid--cols-2',
    3: 'page-grid--cols-3',
    4: 'page-grid--cols-4',
    12: 'page-grid--cols-12',
};

export const TwoColumnLayout = React.memo(function TwoColumnLayout({
    children,
    className = '',
    gap = 'gap-8',
    align = 'items-start',
}) {
    return (
        <div className={cn('layout-two-column', gap, align === 'items-center' && 'layout-two-column--align-center', className)}>
            {children}
        </div>
    );
});

export const MainColumn = React.memo(function MainColumn({
    children,
    span = 8,
    className = '',
    spacing = 'space-y-8',
}) {
    return (
        <div className={cn('layout-main-column', spanClasses[span] || spanClasses[8], spacing, className)}>
            {children}
        </div>
    );
});

export const SidebarColumn = React.memo(function SidebarColumn({
    children,
    span = 4,
    className = '',
    spacing = 'space-y-6',
}) {
    return (
        <div className={cn('layout-sidebar-column', sidebarSpanClasses[span] || sidebarSpanClasses[4], spacing, className)}>
            {children}
        </div>
    );
});

export const StickyColumn = React.memo(function StickyColumn({
    children,
    span = 4,
    className = '',
    top = 'top-28',
}) {
    return (
        <div className={cn('layout-sticky-column', sidebarSpanClasses[span] || sidebarSpanClasses[4], top, className)}>
            {children}
        </div>
    );
});

export const ThreeColumnLayout = React.memo(function ThreeColumnLayout({
    children,
    className = '',
    gap = 'gap-8',
}) {
    return (
        <div className={cn('layout-three-column', gap, className)}>
            {children}
        </div>
    );
});

export const PageGrid = React.memo(function PageGrid({
    children,
    className = '',
    cols = 3,
    gap = 'gap-8',
}) {
    return (
        <div className={cn('page-grid', gridColsClasses[cols] || gridColsClasses[3], gap, className)}>
            {children}
        </div>
    );
});
