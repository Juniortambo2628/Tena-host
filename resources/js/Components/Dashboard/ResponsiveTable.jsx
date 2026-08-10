import React, { useState } from 'react';
import { MoreHorizontal, Eye, Edit3, Trash2, CheckCircle2, XCircle } from 'lucide-react';
import GlassModal from '@/Components/GlassModal';
import './ResponsiveTable.css';

export default function ResponsiveTable({
    data = [],
    columns = [],
    actions = [],
    primaryField = 'name',
    subtitleField = null,
    detailTitle = 'Details',
    emptyMessage = 'No records found',
    onAction = null,
    enableSelection = false,
    selectedIds = [],
    onSelectionChange = null,
    searchPlaceholder = 'Search...',
    onSearch = null,
}) {
    const [menuOpen, setMenuOpen] = useState(null);
    const [detailItem, setDetailItem] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');

    const toggleMenu = (id) => {
        setMenuOpen(menuOpen === id ? null : id);
    };

    const closeMenu = () => {
        setMenuOpen(null);
    };

    const isSelected = (item) => selectedIds.includes(item.id);

    const toggleRowSelection = (item) => {
        if (!onSelectionChange) return;
        if (isSelected(item)) {
            onSelectionChange(selectedIds.filter(id => id !== item.id));
        } else {
            onSelectionChange([...selectedIds, item.id]);
        }
    };

    const toggleAll = () => {
        if (!onSelectionChange) return;
        if (selectedIds.length === data.length) {
            onSelectionChange([]);
        } else {
            onSelectionChange(data.map(item => item.id));
        }
    };

    const handleSearch = (value) => {
        setSearchQuery(value);
        if (onSearch) onSearch(value);
    };

    const totalCols = columns.length + (actions.length > 0 ? 1 : 0) + (enableSelection ? 1 : 0);

    return (
        <>
            {/* Search Bar */}
            {(onSearch || true) && (
                <div className="responsive-table__search-bar">
                    <div className="responsive-table__search">
                        <svg className="responsive-table__search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => handleSearch(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="responsive-table__search-input"
                        />
                    </div>
                </div>
            )}

            {/* Desktop Table */}
            <div className="responsive-table__desktop">
                <table className="responsive-table__table">
                    <thead>
                        <tr className="responsive-table__head-row">
                            {enableSelection && (
                                <th className="responsive-table__head-cell responsive-table__head-cell--checkbox">
                                    <input
                                        type="checkbox"
                                        checked={data.length > 0 && selectedIds.length === data.length}
                                        onChange={toggleAll}
                                        className="responsive-table__checkbox"
                                    />
                                </th>
                            )}
                            {columns.map((col) => (
                                <th key={col.key} className="responsive-table__head-cell">
                                    {col.label}
                                </th>
                            ))}
                            {actions.length > 0 && (
                                <th className="responsive-table__head-cell">Actions</th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="responsive-table__body">
                        {data.length > 0 ? data.map((item) => (
                            <tr key={item.id} className={`responsive-table__row ${isSelected(item) ? 'responsive-table__row--selected' : ''}`}>
                                {enableSelection && (
                                    <td className="responsive-table__cell responsive-table__cell--checkbox">
                                        <input
                                            type="checkbox"
                                            checked={isSelected(item)}
                                            onChange={() => toggleRowSelection(item)}
                                            className="responsive-table__checkbox"
                                        />
                                    </td>
                                )}
                                {columns.map((col) => (
                                    <td key={col.key} className="responsive-table__cell">
                                        {col.render ? col.render(item) : item[col.key]}
                                    </td>
                                ))}
                                {actions.length > 0 && (
                                    <td className="responsive-table__cell">
                                        <div className="responsive-table__actions">
                                            {actions.map((action) => (
                                                <button
                                                    key={action.key}
                                                    onClick={() => action.onClick(item)}
                                                    className={`responsive-table__action-btn responsive-table__action-btn--${action.variant || 'default'}`}
                                                    title={action.label}
                                                >
                                                    {action.icon}
                                                </button>
                                            ))}
                                        </div>
                                    </td>
                                )}
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan={totalCols} className="responsive-table__empty">
                                    {emptyMessage}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Mobile Cards */}
            <div className="responsive-table__mobile">
                {data.length > 0 ? data.map((item) => (
                    <div key={item.id} className={`responsive-table__card ${isSelected(item) ? 'responsive-table__card--selected' : ''}`}>
                        <div className="responsive-table__card-header">
                            <div className="responsive-table__card-primary">
                                {enableSelection && (
                                    <input
                                        type="checkbox"
                                        checked={isSelected(item)}
                                        onChange={() => toggleRowSelection(item)}
                                        className="responsive-table__checkbox"
                                    />
                                )}
                                <span className="responsive-table__card-id">#{item.id}</span>
                                <span className="responsive-table__card-name">
                                    {typeof primaryField === 'function' ? primaryField(item) : item[primaryField]}
                                </span>
                                {subtitleField && (
                                    <span className="responsive-table__card-subtitle">
                                        {typeof subtitleField === 'function' ? subtitleField(item) : item[subtitleField]}
                                    </span>
                                )}
                            </div>
                            <div className="responsive-table__card-menu-wrapper">
                                <button
                                    onClick={() => toggleMenu(item.id)}
                                    className="responsive-table__card-menu-btn"
                                >
                                    <MoreHorizontal size={20} />
                                </button>
                                {menuOpen === item.id && (
                                    <>
                                        <div className="responsive-table__card-menu-overlay" onClick={closeMenu} />
                                        <div className="responsive-table__card-menu">
                                            <button
                                                onClick={() => { setDetailItem(item); closeMenu(); }}
                                                className="responsive-table__card-menu-item"
                                            >
                                                <Eye size={14} /> View Details
                                            </button>
                                            {actions.map((action) => (
                                                <button
                                                    key={action.key}
                                                    onClick={() => { action.onClick(item); closeMenu(); }}
                                                    className={`responsive-table__card-menu-item responsive-table__card-menu-item--${action.variant || 'default'}`}
                                                >
                                                    {action.icon} {action.label}
                                                </button>
                                            ))}
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                        {columns.slice(0, 2).map((col) => (
                            <div key={col.key} className="responsive-table__card-field">
                                <span className="responsive-table__card-label">{col.label}</span>
                                <span className="responsive-table__card-value">
                                    {col.render ? col.render(item) : item[col.key]}
                                </span>
                            </div>
                        ))}
                    </div>
                )) : (
                    <div className="responsive-table__empty-mobile">
                        {emptyMessage}
                    </div>
                )}
            </div>

            {/* Detail Modal */}
            <GlassModal
                isOpen={!!detailItem}
                onClose={() => setDetailItem(null)}
                title={detailTitle}
                maxWidth="lg"
            >
                {detailItem && (
                    <div className="responsive-table__detail">
                        {columns.map((col) => (
                            <div key={col.key} className="responsive-table__detail-row">
                                <span className="responsive-table__detail-label">{col.label}</span>
                                <span className="responsive-table__detail-value">
                                    {col.render ? col.render(detailItem) : detailItem[col.key] || '-'}
                                </span>
                            </div>
                        ))}
                        {actions.length > 0 && (
                            <div className="responsive-table__detail-actions">
                                {actions.map((action) => (
                                    <button
                                        key={action.key}
                                        onClick={() => { action.onClick(detailItem); setDetailItem(null); }}
                                        className={`responsive-table__detail-action-btn responsive-table__detail-action-btn--${action.variant || 'default'}`}
                                    >
                                        {action.icon} {action.label}
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </GlassModal>
        </>
    );
}
