import React, { useEffect, useState } from 'react';
import { Download, Printer, QrCode } from 'lucide-react';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import { useTerms } from '@/Components/Dashboard/Terms';
import { notify } from '@/Components/Toast';
import { BRAND_COLORS } from '@/constants';
import './WifiQrCard.css';

// The standard "join this WiFi" QR text that phone cameras understand
// (iPhone, Android 10+). \ ; , : and " in the name or password need a backslash.
const escapeWifi = (value) => value.replace(/([\\;,:"])/g, '\\$1');

export function wifiQrText(ssid, password = '') {
    const name = escapeWifi(ssid.trim());

    return password
        ? `WIFI:T:WPA;S:${name};P:${escapeWifi(password)};;`
        : `WIFI:T:nopass;S:${name};;`;
}

const escapeHtml = (value) => value.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

// Black on white scans best, so the code ignores the dashboard theme.
const QR_OPTIONS = { errorCorrectionLevel: 'M', margin: 1, color: { dark: '#000000', light: '#ffffff' } };

/**
 * A printable "scan to join the WiFi" QR code for one site. The password is
 * only used to draw the code: it isn't saved or sent anywhere.
 */
export default function WifiQrCard({ property }) {
    const t = useTerms();
    const [ssid, setSsid] = useState(property.wifi_ssid || '');
    const [password, setPassword] = useState('');
    const [svg, setSvg] = useState('');
    const text = ssid.trim() ? wifiQrText(ssid, password) : '';

    useEffect(() => {
        let current = true;
        if (!text) {
            setSvg('');
            return undefined;
        }
        import('qrcode')
            .then((QRCode) => QRCode.toString(text, { ...QR_OPTIONS, type: 'svg' }))
            .then((markup) => current && setSvg(markup))
            .catch(() => current && setSvg(''));

        return () => { current = false; };
    }, [text]);

    const download = async () => {
        try {
            const QRCode = await import('qrcode');
            const link = document.createElement('a');
            link.href = await QRCode.toDataURL(text, { ...QR_OPTIONS, margin: 2, width: 1024 });
            link.download = `${property.name} WiFi QR code.png`;
            link.click();
        } catch {
            notify.error('The QR code could not be downloaded. Please try again.');
        }
    };

    const print = () => {
        const win = window.open('', '_blank', 'width=600,height=800');
        if (!win) {
            notify.error('Your browser blocked the print window. Allow pop-ups for this site and try again.');
            return;
        }
        win.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${escapeHtml(property.name)} WiFi</title>
<style>
  body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:${BRAND_COLORS.dark};background:#fff}
  .card{max-width:420px;margin:40px auto;padding:32px;border:2px solid ${BRAND_COLORS.dark};border-radius:24px;text-align:center}
  .bar{height:8px;border-radius:8px;background:${BRAND_COLORS.primary};margin:-8px 0 24px}
  h1{font-size:26px;margin:0 0 4px}
  p{font-size:16px;margin:4px 0}
  .qr{width:280px;height:280px;margin:20px auto}
  .qr svg{width:100%;height:100%}
  .small{font-size:13px;color:#555;margin-top:16px}
</style></head><body><div class="card"><div class="bar"></div>
<h1>${escapeHtml(property.name)}</h1>
<p><strong>Scan to join the WiFi</strong></p>
<div class="qr">${svg}</div>
<p>Network: <strong>${escapeHtml(ssid.trim())}</strong></p>
${password ? `<p>Password: <strong>${escapeHtml(password)}</strong></p>` : ''}
<p class="small">Point your phone camera at the code, tap to join, then follow the WiFi page.</p>
</div><script>window.onload=function(){window.print()}</script></body></html>`);
        win.document.close();
    };

    return (
        <GlassCard padding="p-8">
            <div className="wifi-qr-card">
                <div className="wifi-qr-card__intro">
                    <h3 className="font-black text-lg">WiFi QR code</h3>
                    <p className="text-sm mt-2">
                        Print it for rooms, tables or reception. {t('Guests')} scan it with their phone camera to join the WiFi in one tap, then your WiFi page opens as usual.
                    </p>
                </div>

                <div className="wifi-qr-card__body">
                    <div className="wifi-qr-card__fields">
                        <label className="wifi-qr-card__label" htmlFor="wifi-qr-ssid">Network name</label>
                        <input
                            id="wifi-qr-ssid"
                            type="text"
                            className="wifi-qr-card__input"
                            value={ssid}
                            maxLength={32}
                            onChange={(e) => setSsid(e.target.value)}
                            placeholder="e.g. Sunrise Guest WiFi"
                        />
                        <p className="wifi-qr-card__hint">Exactly as phones show it, capitals and spaces included.</p>

                        <label className="wifi-qr-card__label" htmlFor="wifi-qr-password">WiFi password</label>
                        <input
                            id="wifi-qr-password"
                            type="text"
                            className="wifi-qr-card__input"
                            value={password}
                            autoComplete="off"
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="Leave empty if the WiFi is open"
                        />
                        <p className="wifi-qr-card__hint">Only used to draw the code; it isn't saved. Most guest networks with a WiFi page have no password.</p>

                        <div className="wifi-qr-card__actions">
                            <PillButton onClick={download} disabled={!svg} icon={<Download size={16} />}>Download</PillButton>
                            <PillButton onClick={print} disabled={!svg} variant="secondary" icon={<Printer size={16} />}>Print card</PillButton>
                        </div>
                    </div>

                    <div className="wifi-qr-card__preview">
                        {svg
                            ? <div className="wifi-qr-card__code" role="img" aria-label={`QR code to join ${ssid.trim()}`} dangerouslySetInnerHTML={{ __html: svg }} />
                            : <div className="wifi-qr-card__empty"><QrCode size={40} /><span>Add the network name to see the code</span></div>}
                    </div>
                </div>
            </div>
        </GlassCard>
    );
}
