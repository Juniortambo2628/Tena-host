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
}) {
    const [sorting, setSorting] = useState([]);
    const [globalFilter, setGlobalFilter] = useState('');

    const table = useReactTable({
        data,
        columns,
        state: {
            sorting,
            globalFilter,
        },
        onSortingChange: setSorting,
        onGlobalFilterChange: setGlobalFilter,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: serverPagination ? undefined : getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        manualPagination: !!serverPagination,
        pageCount: serverPagination ? serverPagination.last_page : undefined,
    });

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

    return (
        <div className="data-table">
            {/* Toolbar */}
            {!serverPagination && (
                <div className="data-table__toolbar">
                    <div className="data-table__search">
                        <Search size={16} className="data-table__search-icon" />
                        <input
                            value={globalFilter ?? ''}
                            onChange={(e) => setGlobalFilter(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="data-table__search-input"
                        />
                    </div>
                </div>
            )}

            {serverPagination && onSearch && (
                <div className="data-table__toolbar">
                    <div className="data-table__search">
                        <Search size={16} className="data-table__search-icon" />
                        <input
                            defaultValue={serverPagination?.search || ''}
                            onChange={(e) => onSearch(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="data-table__search-input"
                        />
                    </div>
                </div>
            )}

            {/* Table */}
            <div className="data-table__wrapper">
                <table className="data-table__table">
                    <thead>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <tr key={headerGroup.id} className="data-table__header-row">
                                {headerGroup.headers.map((header) => (
                                    <th
                                        key={header.id}
                                        className="data-table__header-cell"
                                        onClick={header.column.getToggleSortingHandler()}
                                    >
                                        <div className="data-table__header-content group">
                                            {flexRender(header.column.columnDef.header, header.getContext())}
                                            {{
                                                asc: <ArrowUpDown size={12} className="data-table__sort-icon--asc" />,
                                                desc: <ArrowUpDown size={12} />,
                                            }[header.column.getIsSorted()] ?? <ArrowUpDown size={12} className="data-table__sort-icon--inactive" />}
                                        </div>
                                    </th>
                                ))}
                            </tr>
                        ))}
                    </thead>
                    <tbody className="data-table__body">
                        {table.getRowModel().rows.length > 0 ? (
                            table.getRowModel().rows.map((row) => (
                                <tr key={row.id} className="data-table__row">
                                    {row.getVisibleCells().map((cell) => (
                                        <td key={cell.id} className="data-table__cell">
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={columns.length} className="data-table__empty-cell">
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
