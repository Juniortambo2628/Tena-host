import { Search } from 'lucide-react';

export default function SearchFilterBar({ search, onSearch, filters, onFilterChange, placeholder = 'Search...' }) {
    return (
        <div className="flex items-center gap-3">
            <div className="relative flex-1">
                <Search size={16} className="absolute left-4 top-1/2 -translate-y-1/2 text-black/30" />
                <input
                    type="text"
                    value={search || ''}
                    onChange={(e) => onSearch(e.target.value)}
                    placeholder={placeholder}
                    className="w-full bg-black/5 border-none rounded-2xl pl-10 pr-6 py-3 outline-none font-bold text-sm focus:ring-4 focus:ring-black/5 transition-all"
                />
            </div>
            {filters && (
                <div className="flex items-center gap-2">
                    {filters.map((filter) => (
                        <button
                            key={filter.value}
                            onClick={() => onFilterChange(filter.value)}
                            className={`px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${
                                filter.active
                                    ? 'bg-black text-white'
                                    : 'bg-black/5 text-black/40 hover:bg-black/10'
                            }`}
                        >
                            {filter.label}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
