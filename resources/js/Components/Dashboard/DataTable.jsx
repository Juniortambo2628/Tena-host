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
import './DataTable.css';

export default function DataTable({
    data,
    columns,
    searchPlaceholder = "Search...",
    serverPagination = null,
    onSearch = null,
    enableSelection = false,
    onSelectionChange = null,
    getRowId = (row) => row.id,
}) {
    const [sorting, setSorting] = useState([]);
    const [globalFilter, setGlobalFilter] = useState('');
    const [rowSelection, setRowSelection] = useState({});

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
            ...(enableSelection ? { rowSelection } : {}),
        },
        onSortingChange: setSorting,
        onGlobalFilterChange: setGlobalFilter,
        ...(enableSelection ? { onRowSelectionChange: setRowSelection } : {}),
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: serverPagination ? undefined : getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        manualPagination: !!serverPagination,
        pageCount: serverPagination ? serverPagination.last_page : undefined,
        getRowId,
    });

    useEffect(() => {
        if (enableSelection && onSelectionChange) {
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

    const handlePageChange = (page) => {
        if (serverPagination && serverPagination.path) {
            const url = new URL(serverPagination.path);
            url.searchParams.set('page', page);
            router.get(url.pathname + url.search, {}, { preserveState: true, replace: true });
        }
    };

    const clearSelection = () => {
        setRowSelection({});
    };

    return (
        <div className="data-table">
            {/* Toolbar */}
            <div className="data-table__toolbar">
                <div className="data-table__search">
                    <Search size={16} className="data-table__search-icon" />
                    {serverPagination && onSearch ? (
                        <input
                            defaultValue={serverPagination?.search || ''}
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

            {/* Table */}
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
                        {table.getRowModel().rows.length > 0 ? (
                            table.getRowModel().rows.map((row) => (
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
                            ))
                        ) : (
                            <tr>
                                <td colSpan={allColumns.length} className="data-table__empty-cell">
                                    No results found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {serverPagination ? (
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
