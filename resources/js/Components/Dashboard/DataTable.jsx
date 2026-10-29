import React, { useState, useEffect } from 'react';
import {
    useReactTable,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    flexRender,
} from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Search, ArrowUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import EmptyState from './EmptyState';
import { useTerms } from './Terms';
import './DataTable.css';

// A column's label in the phone card view: meta.label, else a string header, else its id.
const columnLabel = (column) => column.columnDef.meta?.label
    ?? (typeof column.columnDef.header === 'string' ? column.columnDef.header : column.id);

export default function DataTable({
    data,
    columns,
    title = null,
    searchPlaceholder = "Search...",
    searchValue = '',
    serverPagination = null,
    onSearch = null,
    enableSelection = false,
    // Pass selectedIds to control the selection (onSelectionChange then gets ids),
    // so a page's "clear selection" also unticks the rows.
    selectedIds = null,
    onSelectionChange = null,
    getRowId = (row) => row.id,
    emptyMessage = 'No results found',
    emptyIcon,
    emptyAction = null,
}) {
    const t = useTerms();
    const [sorting, setSorting] = useState([]);
    const [globalFilter, setGlobalFilter] = useState('');
    const [rowSelection, setRowSelection] = useState({});
    const isSelectionControlled = Array.isArray(selectedIds);
    const selectionState = isSelectionControlled
        ? Object.fromEntries(selectedIds.map((id) => [String(id), true]))
        : rowSelection;

    const handleRowSelectionChange = (updater) => {
        const next = typeof updater === 'function' ? updater(selectionState) : updater;
        if (!isSelectionControlled) {
            setRowSelection(next);
            return;
        }
        onSelectionChange?.(data.map(getRowId).filter((id) => next[String(id)]));
    };

    const selectionColumn = enableSelection ? [{
        id: 'select',
        header: ({ table }) => (
            <input
                type="checkbox"
                checked={table.getIsAllPageRowsSelected()}
                onChange={(e) => table.toggleAllPageRowsSelected(e.target.checked)}
                className="data-table__checkbox"
            />
        ),
        cell: ({ row }) => (
            <input
                type="checkbox"
                checked={row.getIsSelected()}
                onChange={(e) => row.toggleSelected(e.target.checked)}
                className="data-table__checkbox"
            />
        ),
        size: 40,
        enableSorting: false,
        enableHiding: false,
    }] : [];

    const allColumns = [...selectionColumn, ...columns];

    const table = useReactTable({
        data,
        columns: allColumns,
        state: {
            sorting,
            globalFilter,
            ...(enableSelection ? { rowSelection: selectionState } : {}),
        },
        onSortingChange: setSorting,
        onGlobalFilterChange: setGlobalFilter,
        ...(enableSelection ? { onRowSelectionChange: handleRowSelectionChange } : {}),
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: serverPagination ? undefined : getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        manualPagination: !!serverPagination,
        pageCount: serverPagination ? serverPagination.last_page : undefined,
        getRowId,
    });

    useEffect(() => {
        if (enableSelection && onSelectionChange && !isSelectionControlled) {
            const selectedRows = table.getSelectedRowModel().rows.map(r => r.original);
            onSelectionChange(selectedRows);
        }
    }, [rowSelection]);

    useEffect(() => {
        if (onSearch && globalFilter !== undefined) {
            const timeout = setTimeout(() => {
                onSearch(globalFilter);
            }, 300);
            return () => clearTimeout(timeout);
        }
    }, [globalFilter]);

    // Keeps the current query (search, filters) and changes only the page.
    const handlePageChange = (page) => {
        if (!serverPagination) return;
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        router.get(url.pathname + url.search, {}, { preserveState: true, replace: true });
    };

    const rows = table.getRowModel().rows;

    const clearSelection = () => {
        setRowSelection({});
    };

    return (
        <div className="data-table">
            {/* Toolbar */}
            <div className="data-table__toolbar">
                {title && <h3 className="data-table__title">{t(title)}</h3>}
                <div className="data-table__search">
                    <Search size={16} className="data-table__search-icon" />
                    {serverPagination && onSearch ? (
                        <input
                            defaultValue={searchValue}
                            onChange={(e) => onSearch(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="data-table__search-input"
                        />
                    ) : (
                        <input
                            value={globalFilter ?? ''}
                            onChange={(e) => setGlobalFilter(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="data-table__search-input"
                        />
                    )}
                </div>
            </div>

            {rows.length === 0 && (
                <EmptyState icon={emptyIcon} message={emptyMessage} action={emptyAction} />
            )}

            {/* Table (from lg) */}
            {rows.length > 0 && (
            <div className="data-table__wrapper">
                <table className="data-table__table">
                    <thead>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <tr key={headerGroup.id} className="data-table__header-row">
                                {headerGroup.headers.map((header) => (
                                    <th
                                        key={header.id}
                                        className={cn("data-table__header-cell", header.id === 'select' && 'data-table__header-cell--checkbox')}
                                        onClick={header.id !== 'select' ? header.column.getToggleSortingHandler() : undefined}
                                    >
                                        <div className="data-table__header-content group">
                                            {flexRender(header.column.columnDef.header, header.getContext())}
                                            {header.id !== 'select' && ({
                                                asc: <ArrowUpDown size={12} className="data-table__sort-icon--asc" />,
                                                desc: <ArrowUpDown size={12} />,
                                            }[header.column.getIsSorted()] ?? <ArrowUpDown size={12} className="data-table__sort-icon--inactive" />)}
                                        </div>
                                    </th>
                                ))}
                            </tr>
                        ))}
                    </thead>
                    <tbody className="data-table__body">
                        {rows.map((row) => (
                                <tr
                                    key={row.id}
                                    className={cn("data-table__row", row.getIsSelected() && "data-table__row--selected")}
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <td
                                            key={cell.id}
                                            className={cn("data-table__cell", cell.column.id === 'select' && 'data-table__cell--checkbox')}
                                        >
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </td>
                                    ))}
                                </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            )}

            {/* Cards (below lg): the first column heads the card, the rest are label/value rows */}
            {rows.length > 0 && (
                <div className="data-table__cards">
                    {rows.map((row) => {
                        const cells = row.getVisibleCells();
                        const select = cells.find((c) => c.column.id === 'select');
                        const actions = cells.find((c) => c.column.id === 'actions');
                        const [primary, ...fields] = cells.filter((c) => c !== select && c !== actions);
                        const render = (cell) => flexRender(cell.column.columnDef.cell, cell.getContext());
                        return (
                            <div key={row.id} className={cn('data-table__card', row.getIsSelected() && 'data-table__card--selected')}>
                                <div className="data-table__card-header">
                                    {select && <div className="data-table__card-select">{render(select)}</div>}
                                    <div className="data-table__card-primary">{primary && render(primary)}</div>
                                </div>
                                {fields.map((cell) => (
                                    <div key={cell.id} className="data-table__card-field">
                                        <span className="data-table__card-label">{t(columnLabel(cell.column))}</span>
                                        <div className="data-table__card-value">{render(cell)}</div>
                                    </div>
                                ))}
                                {actions && <div className="data-table__card-actions">{render(actions)}</div>}
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Pagination */}
            {rows.length === 0 ? null : serverPagination ? (
                <div className="data-table__pagination">
                    <div className="data-table__pagination-info">
                        Showing {serverPagination.from}-{serverPagination.to} of {serverPagination.total}
                    </div>
                    <div className="data-table__pagination-controls">
                        <button
                            className="data-table__pagination-button"
                            onClick={() => handlePageChange(1)}
                            disabled={!serverPagination.prev_page_url}
                        >
                            <ChevronsLeft size={16} />
                        </button>
                        <button
                            className="data-table__pagination-button"
                            onClick={() => handlePageChange(serverPagination.current_page - 1)}
                            disabled={!serverPagination.prev_page_url}
                        >
                            <ChevronLeft size={16} />
                        </button>
                        <span className="data-table__pagination-page">
                            Page {serverPagination.current_page} of {serverPagination.last_page}
                        </span>
                        <button
                            className="data-table__pagination-button"
                            onClick={() => handlePageChange(serverPagination.current_page + 1)}
                            disabled={!serverPagination.next_page_url}
                        >
                            <ChevronRight size={16} />
                        </button>
                        <button
                            className="data-table__pagination-button"
                            onClick={() => handlePageChange(serverPagination.last_page)}
                            disabled={!serverPagination.next_page_url}
                        >
                            <ChevronsRight size={16} />
                        </button>
                    </div>
                </div>
            ) : (
                <div className="data-table__pagination">
                    <div className="data-table__pagination-info">
                        Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
                    </div>
                    <div className="data-table__pagination-controls">
                        <button
                            className="data-table__pagination-button"
                            onClick={() => table.setPageIndex(0)}
                            disabled={!table.getCanPreviousPage()}
                        >
                            <ChevronsLeft size={16} />
                        </button>
                        <button
                            className="data-table__pagination-button"
                            onClick={() => table.previousPage()}
                            disabled={!table.getCanPreviousPage()}
                        >
                            <ChevronLeft size={16} />
                        </button>
                        <button
                            className="data-table__pagination-button"
                            onClick={() => table.nextPage()}
                            disabled={!table.getCanNextPage()}
                        >
                            <ChevronRight size={16} />
                        </button>
                        <button
                            className="data-table__pagination-button"
                            onClick={() => table.setPageIndex(table.getPageCount() - 1)}
                            disabled={!table.getCanNextPage()}
                        >
                            <ChevronsRight size={16} />
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
