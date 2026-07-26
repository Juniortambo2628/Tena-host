import React from 'react';
import './Skeleton.css';

export function SkeletonBlock({ className = '', width, height, rounded = 'rounded-xl' }) {
    return (
        <div
            className={`skeleton ${rounded} ${className}`}
            style={{ width, height }}
        />
    );
}

export function SkeletonText({ lines = 3, className = '' }) {
    return (
        <div className={`space-y-2 ${className}`}>
            {Array.from({ length: lines }).map((_, i) => (
                <div
                    key={i}
                    className="skeleton rounded-md"
                    style={{ width: i === lines - 1 ? '60%' : '100%', height: '1rem' }}
                />
            ))}
        </div>
    );
}

export function SkeletonHero() {
    return (
        <div className="skeleton-hero">
            <div className="skeleton-hero__container">
                <div className="skeleton-hero__content">
                    <div className="skeleton-hero__text">
                        <SkeletonBlock width="8rem" height="2rem" rounded="rounded-full" />
                        <SkeletonBlock className="mt-4" height="3rem" />
                        <SkeletonBlock className="mt-2" height="3rem" width="80%" />
                        <SkeletonText lines={2} className="mt-4" />
                        <div className="flex gap-3 mt-6">
                            <SkeletonBlock width="10rem" height="3rem" rounded="rounded-xl" />
                            <SkeletonBlock width="10rem" height="3rem" rounded="rounded-xl" />
                        </div>
                    </div>
                    <div className="skeleton-hero__image">
                        <SkeletonBlock className="w-full h-full" rounded="rounded-2xl" />
                    </div>
                </div>
                <div className="grid grid-cols-4 gap-4 mt-8">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <div key={i} className="space-y-3">
                            <SkeletonBlock className="w-full h-32" rounded="rounded-xl" />
                            <SkeletonBlock width="70%" height="1rem" />
                            <SkeletonText lines={2} />
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export function SkeletonSectionHeader() {
    return (
        <div className="flex flex-col items-center mb-12">
            <SkeletonBlock width="10rem" height="2rem" rounded="rounded-full" />
            <SkeletonBlock className="mt-4" width="20rem" height="2.5rem" />
            <SkeletonBlock className="mt-2" width="30rem" height="1rem" />
        </div>
    );
}

export function SkeletonFeatureGrid({ count = 6 }) {
    return (
        <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8`}>
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="p-6 space-y-4">
                    <SkeletonBlock className="w-16 h-16 mx-auto" rounded="rounded-xl" />
                    <SkeletonBlock className="mx-auto" width="60%" height="1.25rem" />
                    <SkeletonText lines={2} />
                </div>
            ))}
        </div>
    );
}

export function SkeletonTwoColumn() {
    return (
        <div className="flex flex-col lg:flex-row items-center gap-12">
            <div className="w-full lg:w-1/2">
                <SkeletonBlock className="w-full aspect-[4/3]" rounded="rounded-3xl" />
            </div>
            <div className="w-full lg:w-1/2 space-y-4">
                <SkeletonBlock width="8rem" height="2rem" rounded="rounded-full" />
                <SkeletonBlock height="2rem" />
                <SkeletonText lines={3} />
                <div className="space-y-3 mt-4">
                    {Array.from({ length: 3 }).map((_, i) => (
                        <div key={i} className="flex items-start gap-3">
                            <SkeletonBlock className="w-12 h-12 flex-shrink-0" rounded="rounded-xl" />
                            <div className="flex-1 space-y-1">
                                <SkeletonBlock width="50%" height="1rem" />
                                <SkeletonText lines={1} />
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export function SkeletonStepGrid({ count = 4 }) {
    return (
        <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-${count} gap-6`}>
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="space-y-3">
                    <SkeletonBlock className="w-full h-40" rounded="rounded-xl" />
                    <SkeletonBlock width="5rem" height="1.5rem" rounded="rounded-full" />
                    <SkeletonBlock width="70%" height="1rem" />
                    <SkeletonText lines={2} />
                </div>
            ))}
        </div>
    );
}

export function SkeletonPricingGrid() {
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {Array.from({ length: 3 }).map((_, i) => (
                <div key={i} className="p-8 space-y-4 text-center">
                    <SkeletonBlock className="mx-auto" width="10rem" height="2rem" rounded="rounded-full" />
                    <SkeletonBlock className="mx-auto" width="6rem" height="3rem" />
                    <SkeletonBlock className="mx-auto" width="4rem" height="1rem" />
                    <SkeletonText lines={3} />
                    <SkeletonBlock className="mx-auto w-full" height="3rem" rounded="rounded-xl" />
                </div>
            ))}
        </div>
    );
}
