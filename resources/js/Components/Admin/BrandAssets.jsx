import React, { useRef } from 'react';
import { router } from '@inertiajs/react';
import { ImageIcon, Upload, RotateCcw } from 'lucide-react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import { notify } from '@/Components/Toast';

// Preview each logo on the surface it is made for.
const SURFACE = {
    logo_footer_url: 'bg-[#1b1b1b]',
    favicon_url: 'bg-gray-100',
};

function LogoSlot({ logo }) {
    const input = useRef(null);

    const upload = (file) => {
        if (!file) return;
        router.post(route('admin.branding.upload', logo.key), { file }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => notify.success(`${logo.label} updated`),
            onError: (errors) => notify.error(errors.file || 'Upload failed'),
        });
    };

    const reset = () => {
        if (!window.confirm(`Go back to the default ${logo.label.toLowerCase()}?`)) return;
        router.delete(route('admin.branding.reset', logo.key), {
            preserveScroll: true,
            onSuccess: () => notify.success(`${logo.label} reset`),
        });
    };

    return (
        <div className="rounded-2xl border border-black/5 p-4 space-y-3">
            <div className={`flex h-28 items-center justify-center rounded-xl p-4 ${SURFACE[logo.key] || 'bg-white border border-black/5'}`}>
                <img src={logo.url} alt={logo.label} className={logo.key === 'favicon_url' ? 'h-16 w-16 rounded-lg' : 'max-h-full max-w-full object-contain'} />
            </div>
            <div>
                <p className="text-sm font-bold">{logo.label}</p>
                <p className="text-xs text-black/50">{logo.hint}</p>
            </div>
            <div className="flex flex-wrap gap-2">
                <button type="button" onClick={() => input.current?.click()} className="inline-flex items-center gap-1.5 rounded-full bg-black px-4 py-2 text-xs font-bold text-white">
                    <Upload size={14} /> Upload
                </button>
                {logo.custom && (
                    <button type="button" onClick={reset} className="inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-bold text-black/60 hover:bg-black/5">
                        <RotateCcw size={14} /> Use default
                    </button>
                )}
                <input
                    ref={input}
                    type="file"
                    accept={logo.key === 'logo_url' ? 'image/png,image/jpeg,image/webp' : 'image/*,.ico'}
                    className="hidden"
                    onChange={(e) => { upload(e.target.files?.[0]); e.target.value = ''; }}
                />
            </div>
        </div>
    );
}

/** Admin → Settings → Branding: the logos every page and email use (App\Support\Brand). */
export default function BrandAssets({ logos }) {
    return (
        <GlassCard padding="p-6">
            <div className="space-y-4">
                <div className="settings-page__card-header">
                    <div className="settings-page__card-icon"><ImageIcon size={20} /></div>
                    <div>
                        <h3 className="settings-page__card-title">Logos</h3>
                        <p className="settings-page__card-subtitle">Used across the website, dashboards, WiFi pages and emails</p>
                    </div>
                </div>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {logos.map((logo) => <LogoSlot key={logo.key} logo={logo} />)}
                </div>
            </div>
        </GlassCard>
    );
}
