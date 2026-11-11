import React from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import './Edit.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { ArrowLeft, Loader2, Sparkles, Star, Store, BadgeCheck, DoorOpen } from 'lucide-react';
import { T } from '@/Components/Dashboard/Terms';
import { TemplateThumb } from '@/Components/Properties/SplashEditor';
import { normalizeTemplate } from '@/Components/Properties/splashTemplates';
import { BRAND_COLORS } from '@/constants';
import { useConfirm } from '@/hooks/useConfirm';

export default function PropertyEdit({ property, reviewDefaults = {}, reviewStats = {}, reviewEmails = null, venueUrl = null, staffWifiEnabled = false, visitorLogEnabled = false }) {
    const { confirm, ConfirmDialogEl } = useConfirm();
    // Occupancy alerts are for rentals only.
    const { account } = usePage().props.auth || {};
    const isRentals = !account || account.type === 'host';
    const { data, setData, post, processing, errors, isDirty } = useForm({
        name: property.name || '',
        address: property.address || '',
        wifi_ssid: property.wifi_ssid || '',
        occupancy_threshold: property.occupancy_threshold || 20,
        review_requests_enabled: !!property.review_requests_enabled,
        review_url: property.review_url || '',
        review_request_delay_hours: property.review_request_delay_hours || 24,
        review_message: property.review_message || '',
        review_request_channel: property.review_request_channel || 'whatsapp',
        offers: property.offers || '',
        events: property.events || '',
        birthday_offer: property.birthday_offer || '',
        staff_ssid: property.staff_ssid || '',
        staff_email_domain: property.staff_email_domain || '',
        visitor_log: !!property.visitor_log,
        _method: 'patch',
    });

    // A thank-you and review request to every guest not yet asked (the saved settings).
    const askEveryone = async () => {
        const ok = await confirm({
            title: 'Ask everyone so far',
            message: `Send a thank-you and review request to ${reviewEmails.waiting} ${reviewEmails.waiting === 1 ? 'guest' : 'guests'} now? Each guest is only ever asked once.`,
            confirmLabel: 'Send now',
        });
        if (ok) router.post(route('host.properties.review-requests', property.id), {}, { preserveScroll: true });
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('host.properties.update', property.id), { forceFormData: true });
    };

    const breadcrumbs = [
        { label: 'WiFi Access', href: route('host.properties.index') },
        { label: property.name, href: route('host.properties.show', property.id) },
        { label: 'Edit' },
    ];

    return (
        <>
            <Head title={`Edit ${property.name}`} />

            <DashboardHero
                title={`Editing: ${property.name}`}
                breadcrumbs={breadcrumbs}
                actions={[
                    { label: 'Cancel', icon: <ArrowLeft size={16} />, href: route('host.properties.show', property.id) },
                ]}
            />

            <div className="host-properties-edit">
                <GlassCard padding="p-8">
                    <form onSubmit={submit} className="host-properties-edit-form">
                        <div>
                            <label className="host-properties-edit-label"><T>Property Name</T></label>
                            <input
                                type="text"
                                className="host-properties-edit-input"
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                            />
                            {errors.name && <p className="host-properties-edit-error">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="host-properties-edit-label">Address</label>
                            <textarea
                                rows="2"
                                className="host-properties-edit-input resize-none"
                                value={data.address}
                                onChange={e => setData('address', e.target.value)}
                            />
                        </div>

                        <div className="host-properties-edit-grid">
                            <div>
                                <label className="host-properties-edit-label">WiFi SSID</label>
                                <input
                                    type="text"
                                    className="host-properties-edit-input"
                                    value={data.wifi_ssid}
                                    onChange={e => setData('wifi_ssid', e.target.value)}
                                />
                            </div>
                            {isRentals && <div>
                                <label className="host-properties-edit-label">Occupancy Limit</label>
                                <input
                                    type="number"
                                    className="host-properties-edit-input"
                                    value={data.occupancy_threshold}
                                    onChange={e => setData('occupancy_threshold', e.target.value)}
                                />
                            </div>}
                        </div>

                        <div className="host-properties-edit-splash">
                            <TemplateThumb template={normalizeTemplate(property.branding_json?.template)} accent={property.branding_json?.accent || BRAND_COLORS.primary} />
                            <div>
                                <p className="host-properties-edit-section-title"><Sparkles size={14} /> Guest splash page</p>
                                <p className="host-properties-edit-hint">
                                    Template, photos, welcome note, house rules, emergency numbers, local picks, reviews, offers and cross-sell cards, with a live preview.
                                </p>
                            </div>
                            <PillButton variant="primary" onClick={() => router.get(route('host.properties.splash', property.id))}>Open Splash Studio</PillButton>
                        </div>

                        <fieldset className="host-properties-edit-section">
                            <legend className="host-properties-edit-section-title">
                                <Star size={14} /> Thank-you &amp; review request
                            </legend>
                            <p className="host-properties-edit-hint">
                                {reviewEmails
                                    ? "After check-out (or a guest's last WiFi visit), TenaFi thanks them the way you choose below and links to your review page."
                                    : "After check-out (or a guest's last WiFi visit), TenaFi thanks them on WhatsApp, or by SMS if WhatsApp can't reach them, and links to your review page."}{' '}
                                Each guest is asked once.
                                {reviewStats.requested > 0 && ` So far: ${reviewStats.requested} asked, ${reviewStats.clicked} opened the link.`}
                            </p>

                            <label className="host-properties-edit-toggle">
                                <input
                                    type="checkbox"
                                    checked={data.review_requests_enabled}
                                    onChange={e => setData('review_requests_enabled', e.target.checked)}
                                />
                                <span>Send review requests automatically</span>
                            </label>

                            <div>
                                <label className="host-properties-edit-label">Google review link</label>
                                <input
                                    type="url"
                                    className="host-properties-edit-input"
                                    placeholder="https://g.page/r/..."
                                    value={data.review_url}
                                    onChange={e => setData('review_url', e.target.value)}
                                />
                                {errors.review_url && <p className="host-properties-edit-error">{errors.review_url}</p>}
                            </div>

                            <div>
                                <label className="host-properties-edit-label">Send after (hours)</label>
                                <input
                                    type="number"
                                    min="1"
                                    max="168"
                                    className="host-properties-edit-input"
                                    value={data.review_request_delay_hours}
                                    onChange={e => setData('review_request_delay_hours', e.target.value)}
                                />
                                {errors.review_request_delay_hours && <p className="host-properties-edit-error">{errors.review_request_delay_hours}</p>}
                            </div>

                            <div>
                                <label className="host-properties-edit-label">Message</label>
                                <textarea
                                    rows="3"
                                    className="host-properties-edit-input resize-none"
                                    placeholder={reviewDefaults.message}
                                    value={data.review_message}
                                    onChange={e => setData('review_message', e.target.value)}
                                />
                                <p className="host-properties-edit-hint">
                                    Leave empty to use the message shown. Placeholders: {'{guest_name}'}, {'{property_name}'}, {'{review_link}'}.
                                </p>
                                {errors.review_message && <p className="host-properties-edit-error">{errors.review_message}</p>}
                            </div>

                            {reviewEmails && (
                                <>
                                    <div>
                                        <label className="host-properties-edit-label" htmlFor="review_request_channel">Send by</label>
                                        <select
                                            id="review_request_channel"
                                            className="host-properties-edit-input"
                                            value={data.review_request_channel}
                                            onChange={e => setData('review_request_channel', e.target.value)}
                                        >
                                            <option value="whatsapp">WhatsApp (SMS if WhatsApp can't reach them)</option>
                                            <option value="email">Email, in your name, logo and colour</option>
                                            <option value="both">Email if they gave one, otherwise WhatsApp</option>
                                        </select>
                                        <p className="host-properties-edit-hint">
                                            By email, your message (without the link) sits under your logo, and the link becomes a “Leave a Google review” button.
                                        </p>
                                        {errors.review_request_channel && <p className="host-properties-edit-error">{errors.review_request_channel}</p>}
                                    </div>

                                    {reviewEmails.canSend && (
                                        <div className="host-properties-edit-ask">
                                            <p className="host-properties-edit-hint">
                                                {reviewEmails.waiting > 0
                                                    ? <>{reviewEmails.waiting} {reviewEmails.waiting === 1 ? 'guest has' : 'guests have'} not been asked yet, including people who came before review requests were on.</>
                                                    : 'Everyone who can be asked has been asked.'}
                                                {isDirty && ' Save your changes first.'}
                                            </p>
                                            <PillButton
                                                variant="secondary"
                                                onClick={askEveryone}
                                                disabled={!reviewEmails.waiting || isDirty || !property.review_url}
                                            >
                                                Ask everyone so far
                                            </PillButton>
                                        </div>
                                    )}
                                </>
                            )}
                        </fieldset>

                        {venueUrl && (
                            <fieldset className="host-properties-edit-section">
                                <legend className="host-properties-edit-section-title">
                                    <Store size={14} /> Customer homepage
                                </legend>
                                <p className="host-properties-edit-hint">
                                    Customers see this right after connecting: your menu (active amenities), offers and events.{' '}
                                    <a href={venueUrl} target="_blank" rel="noopener" className="underline">Preview it</a>.
                                </p>
                                {[
                                    ['offers', 'Offers', 'One per line, e.g. 2-for-1 cappuccinos before 10am'],
                                    ['events', 'Events', 'One per line, e.g. Live music, Friday 7pm'],
                                    ['birthday_offer', 'Birthday treat', 'Happy birthday {guest_name}! Show this for a free slice of cake at {property_name} this week.'],
                                ].map(([key, label, placeholder]) => (
                                    <div key={key}>
                                        <label className="host-properties-edit-label">{label}</label>
                                        <textarea
                                            rows="3"
                                            className="host-properties-edit-input resize-none"
                                            placeholder={placeholder}
                                            value={data[key]}
                                            onChange={e => setData(key, e.target.value)}
                                        />
                                        {errors[key] && <p className="host-properties-edit-error">{errors[key]}</p>}
                                    </div>
                                ))}
                                <p className="host-properties-edit-hint">
                                    Birthday treats go out on WhatsApp on the customer's birthday, to customers who gave a birthday and opted in to offers. Leave it empty to turn them off.
                                </p>
                            </fieldset>
                        )}

                        {staffWifiEnabled && !isRentals && (
                            <fieldset className="host-properties-edit-section">
                                <legend className="host-properties-edit-section-title">
                                    <BadgeCheck size={14} /> Staff WiFi
                                </legend>
                                <p className="host-properties-edit-hint">
                                    If your team has its own network here, name it below. People joining it see a short staff sign-in (name and work email or staff ID) instead of the customer page, and never get marketing messages.
                                </p>
                                <div className="host-properties-edit-grid">
                                    <div>
                                        <label className="host-properties-edit-label">Staff network name (SSID)</label>
                                        <input
                                            type="text"
                                            className="host-properties-edit-input"
                                            placeholder="e.g. Acme-Staff"
                                            value={data.staff_ssid}
                                            onChange={e => setData('staff_ssid', e.target.value)}
                                        />
                                        {errors.staff_ssid && <p className="host-properties-edit-error">{errors.staff_ssid}</p>}
                                    </div>
                                    <div>
                                        <label className="host-properties-edit-label">Work email domain (optional)</label>
                                        <input
                                            type="text"
                                            className="host-properties-edit-input"
                                            placeholder="e.g. acme.co.ke"
                                            value={data.staff_email_domain}
                                            onChange={e => setData('staff_email_domain', e.target.value)}
                                        />
                                        {errors.staff_email_domain && <p className="host-properties-edit-error">{errors.staff_email_domain}</p>}
                                    </div>
                                </div>
                                <p className="host-properties-edit-hint">
                                    With a domain set, only addresses ending in it can sign in to the staff network.
                                </p>
                            </fieldset>
                        )}

                        {visitorLogEnabled && !isRentals && (
                            <fieldset className="host-properties-edit-section">
                                <legend className="host-properties-edit-section-title">
                                    <DoorOpen size={14} /> Reception visitor log
                                </legend>
                                <p className="host-properties-edit-hint">
                                    Visitors who sign in to the WiFi here are also asked who they're here to see, and each visit goes in your visitor log (Visitors in the menu), where reception can add walk-ins and sign people out.
                                </p>
                                <label className="host-properties-edit-toggle">
                                    <input
                                        type="checkbox"
                                        checked={data.visitor_log}
                                        onChange={e => setData('visitor_log', e.target.checked)}
                                    />
                                    <span>Keep a visitor log at this site</span>
                                </label>
                            </fieldset>
                        )}

                        <div className="host-properties-edit-actions">
                            <PillButton
                                variant="secondary"
                                onClick={() => router.get(route('host.properties.show', property.id))}
                                className="flex-1"
                            >
                                Cancel
                            </PillButton>
                            <PillButton
                                variant="primary"
                                onClick={submit}
                                disabled={processing}
                                className="flex-1"
                            >
                                {processing ? <Loader2 className="animate-spin" size={16} /> : null}
                                Save Changes
                            </PillButton>
                        </div>
                    </form>
                </GlassCard>
            </div>
            {ConfirmDialogEl}
        </>
    );
}
