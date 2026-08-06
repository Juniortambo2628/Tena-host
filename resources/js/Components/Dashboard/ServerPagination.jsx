import { Link } from '@inertiajs/react';

export default function ServerPagination({ links, className = '' }) {
    if (!links || links.length <= 3) return null;

    return (
        <div className={`flex items-center gap-1 ${className}`}>
            {links.map((link, i) =>
                link.url ? (
                    <Link
                        key={i}
                        href={link.url}
                        className={`px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all ${
                            link.active
                                ? 'bg-black text-white'
                                : 'bg-black/5 text-black/40 hover:bg-black/10 hover:text-black/60'
                        }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span
                        key={i}
                        className="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-black/5 text-black/20 cursor-not-allowed"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                )
            )}
        </div>
    );
}
