// Shared bits for the event pages.
export const STATUS_LABEL = { live: 'Live now', upcoming: 'Upcoming', past: 'Past' };
export const STATUS_COLORS = { 'live now': 'bg-green-100 text-green-700', upcoming: 'bg-amber-100 text-amber-700', past: 'bg-black/5 text-black/40' };

const day = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });

/** "5 Dec 2026" or "5 Dec 2026 – 7 Dec 2026". */
export const dateRange = (start, end) => (start === end ? day(start) : `${day(start)} – ${day(end)}`);
