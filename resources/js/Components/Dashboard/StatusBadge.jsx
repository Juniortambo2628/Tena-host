const STATUS_COLORS = {
    active: 'bg-green-100 text-green-700',
    completed: 'bg-green-100 text-green-700',
    fulfilled: 'bg-green-100 text-green-700',
    online: 'bg-green-100 text-green-700',
    pending: 'bg-amber-100 text-amber-700',
    draft: 'bg-amber-100 text-amber-700',
    paused: 'bg-amber-100 text-amber-700',
    inactive: 'bg-red-100 text-red-700',
    failed: 'bg-red-100 text-red-700',
    cancelled: 'bg-red-100 text-red-700',
    offline: 'bg-red/10 text-red/70',
    archived: 'bg-black/5 text-black/40',
    converted: 'bg-purple-100 text-purple-700',
};

export default function StatusBadge({ status, colors, className = '' }) {
    const lowerStatus = status?.toLowerCase() || '';
    const colorClass = colors?.[lowerStatus] || STATUS_COLORS[lowerStatus] || 'bg-black/5 text-black/40';

    return (
        <span className={`px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest ${colorClass} ${className}`}>
            {status}
        </span>
    );
}
