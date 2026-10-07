import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, useForm, router } from '@inertiajs/react';
import './Edit.css';
import GlassCard from '@/Components/Dashboard/GlassCard';
import PillButton from '@/Components/Dashboard/PillButton';
import DashboardHero from '@/Components/Dashboard/DashboardHero';
import { ArrowLeft, Loader2, Star, Store } from 'lucide-react';
import { T } from '@/Components/Dashboard/Terms';

export default function PropertyEdit({ property, reviewDefaults = {}, reviewStats = {}, venueUrl = null }) {
    const { data, setData, post, processing, errors } = useForm({
        name: property.name || '',
        address: property.address || '',
        wifi_ssid: property.wifi_ssid || '',
        occupancy_threshold: property.occupancy_threshold || 20,
        splash_image: null,
        review_requests_enabled: !!property.review_requests_enabled,
        review_url: property.review_url || '',
        review_request_delay_hours: property.review_request_delay_hours || 24,
        review_message: property.review_message || '',
        offers: property.offers || '',
        events: property.events || '',
        birthday_offer: property.birthday_offer || '',
        _method: 'patch',
    });

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
        <DashboardLayout title={`Edit ${property.name}`}>
            <Head title={`Edit ${property.name}`} />

            <DashboardHero
                title={`Editing: ${property.name}`}
                breadcrumbs={breadcrumbs}
                actions={[
                    { label: 'Cancel', icon: <ArrowLeft size={16} />, onClick: () => router.get(route('host.properties.show', property.id)) },
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
                            <div>
                                <label className="host-properties-edit-label">Occupancy Limit</label>
                                <input
                                    type="number"
                                    className="host-properties-edit-input"
                                    value={data.occupancy_threshold}
                                    onChange={e => setData('occupancy_threshold', e.target.value)}
                                />
                            </div>
                        </div>

                        <div>
                            <label className="host-properties-edit-label">Splash Image</label>
                            <input
                                type="file"
                                accept="image/*"
                                onChange={e => setData('splash_image', e.target.files[0])}
                                className="host-properties-edit-input"
                            />
                            {property.splash_image_path && (
                                <div className="mt-4">
                                    <img src={property.splash_image_path} alt="Current" className="w-full h-32 object-cover rounded-2xl" />
                                </div>
                            )}
                        </div>

                        <fieldset className="host-properties-edit-section">
                            <legend className="host-properties-edit-section-title">
                                <Star size={14} /> Thank-you &amp; review request
                            </legend>
                            <p className="host-properties-edit-hint">
                                After check-out (or a guest's last WiFi visit), TenaFi thanks them on WhatsApp, or by SMS if WhatsApp can't reach them, and links to your review page.
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
        </DashboardLayout>
    );
}
