import React from 'react';
import './Skeleton.css';

/**
 * Reusable skeleton loading primitives.
 * Compose these to build any loading state.
 *
 * Usage:
 *   <Skeleton variant="line" width="50%" />
 *   <Skeleton variant="circle" size={40} />
 *   <Skeleton variant="rect" height={200} />
 *   <SkeletonGroup type="card" count={3} />
 *   <SkeletonGroup type="table" rows={5} cols={4} />
 */

export function Skeleton({ variant = 'line', width, height, size, className = '', rounded }) {
    const style = {};
    if (width) style.width = width;
    if (height) style.height = height;
    if (size) { style.width = size; style.height = size; }

    const roundedClass = rounded || (
        variant === 'circle' ? 'rounded-full' :
        variant === 'rect' ? 'rounded-2xl' :
        'rounded-lg'
    );

    return (
        <div
            className={`sk sk--${variant} ${roundedClass} ${className}`}
            style={style}
        />
    );
}

export function SkeletonLine({ width = '100%', height = '1rem', className = '' }) {
    return <Skeleton variant="line" width={width} height={height} className={className} />;
}

export function SkeletonCircle({ size = 40, className = '' }) {
    return <Skeleton variant="circle" size={size} className={className} />;
}

export function SkeletonRect({ width = '100%', height = '10rem', rounded = 'rounded-2xl', className = '' }) {
    return <Skeleton variant="rect" width={width} height={height} rounded={rounded} className={className} />;
}

/**
 * Preset skeleton layouts for common dashboard patterns.
 * Just render <SkeletonGroup type="table" /> — zero config.
 */
export function SkeletonGroup({ type = 'card', count, rows, cols, className = '' }) {
    if (type === 'card') return <SkeletonCards count={count || 3} className={className} />;
    if (type === 'table') return <SkeletonTable rows={rows || 5} cols={cols || 4} className={className} />;
    if (type === 'stats') return <SkeletonStats count={count || 4} className={className} />;
    if (type === 'hero') return <SkeletonHeroDashboard className={className} />;
    if (type === 'form') return <SkeletonForm className={className} />;
    return null;
}

function SkeletonCards({ count = 3 }) {
    return (
        <div className="sk-grid sk-grid--cards">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="sk-card">
                    <SkeletonRect height="8rem" rounded="rounded-2xl" />
                    <div className="sk-card__body">
                        <SkeletonLine width="60%" height="0.875rem" />
                        <SkeletonLine width="40%" height="0.75rem" className="mt-2" />
                    </div>
                </div>
            ))}
        </div>
    );
}

function SkeletonTable({ rows = 5, cols = 4 }) {
    return (
        <div className="sk-table">
            <div className="sk-table__header">
                {Array.from({ length: cols }).map((_, i) => (
                    <SkeletonLine key={i} width="70%" height="0.75rem" />
                ))}
            </div>
            {Array.from({ length: rows }).map((_, ri) => (
                <div key={ri} className="sk-table__row">
                    {Array.from({ length: cols }).map((_, ci) => (
                        <SkeletonLine key={ci} width={ci === 0 ? '80%' : '60%'} height="0.875rem" />
                    ))}
                </div>
            ))}
        </div>
    );
}

function SkeletonStats({ count = 4 }) {
    return (
        <div className="sk-grid sk-grid--stats">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="sk-stat">
                    <SkeletonLine width="5rem" height="0.7rem" />
                    <SkeletonLine width="4rem" height="1.5rem" className="mt-2" />
                </div>
            ))}
        </div>
    );
}

function SkeletonHeroDashboard() {
    return (
        <div className="sk-hero">
            <SkeletonLine width="12rem" height="1.5rem" />
            <SkeletonLine width="8rem" height="0.75rem" className="mt-3" />
            <div className="flex gap-3 mt-5">
                <SkeletonRect width="8rem" height="2.25rem" rounded="rounded-xl" />
                <SkeletonRect width="8rem" height="2.25rem" rounded="rounded-xl" />
            </div>
        </div>
    );
}

function SkeletonForm() {
    return (
        <div className="sk-form">
            {Array.from({ length: 4 }).map((_, i) => (
                <div key={i} className="sk-form__field">
                    <SkeletonLine width="6rem" height="0.7rem" />
                    <SkeletonRect height="2.75rem" rounded="rounded-xl" className="mt-2" />
                </div>
            ))}
            <SkeletonRect width="10rem" height="2.5rem" rounded="rounded-xl" className="mt-4" />
        </div>
    );
}
