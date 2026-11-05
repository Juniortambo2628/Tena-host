import React from 'react';
import { useForm } from '@inertiajs/react';
import PillButton from '@/Components/Dashboard/PillButton';
import { FormField, TextInput, Select, FormActions } from '@/Components/Forms/FormPrimitives';
import './EventForm.css';

const blank = { name: '', property_id: '', starts_on: '', ends_on: '', question: '', branding_json: { heading: '', message: '' }, sponsor: { name: '', message: '', url: '' } };

/**
 * Create or edit an event: name, venue, dates, an optional extra question
 * for the WiFi sign-in and an optional sponsor (EventController).
 */
export default function EventForm({ event = null, venues, onDone, onCancel }) {
    const form = useForm(event
        ? { ...blank, ...event, question: event.question || '', branding_json: { ...blank.branding_json, ...(event.branding_json || {}) }, sponsor: { ...blank.sponsor, ...(event.sponsor || {}) } }
        : { ...blank, property_id: venues.length === 1 ? venues[0].id : '' });

    const submit = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => onDone?.() };
        event ? form.put(route('host.events.update', event.id), options) : form.post(route('host.events.store'), options);
    };
    const setSponsor = (key, value) => form.setData('sponsor', { ...form.data.sponsor, [key]: value });
    const setWelcome = (key, value) => form.setData('branding_json', { ...form.data.branding_json, [key]: value });

    return (
        <form onSubmit={submit} className="event-form">
            <FormField label="Event name" required error={form.errors.name}>
                <TextInput id="event-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="e.g. Kona Design Week" required />
            </FormField>
            <FormField label="Venue" required error={form.errors.property_id}>
                <Select id="event-venue" value={form.data.property_id} onChange={(e) => form.setData('property_id', e.target.value)} required>
                    <option value="">Choose a venue</option>
                    {venues.map((v) => <option key={v.id} value={v.id}>{v.name}</option>)}
                </Select>
            </FormField>
            <div className="event-form__row">
                <FormField label="First day" required error={form.errors.starts_on}>
                    <TextInput id="event-starts" type="date" value={form.data.starts_on} onChange={(e) => form.setData('starts_on', e.target.value)} required />
                </FormField>
                <FormField label="Last day" required error={form.errors.ends_on}>
                    <TextInput id="event-ends" type="date" value={form.data.ends_on} onChange={(e) => form.setData('ends_on', e.target.value)} required />
                </FormField>
            </div>
            <fieldset className="event-form__sponsor">
                <legend>WiFi page while the event runs</legend>
                <FormField label="Welcome heading" hint={`Leave empty for “Welcome to ${form.data.name || 'your event'}”.`} error={form.errors['branding_json.heading']}>
                    <TextInput id="event-heading" value={form.data.branding_json.heading} onChange={(e) => setWelcome('heading', e.target.value)} />
                </FormField>
                <FormField label="Welcome message" hint="Leave empty to keep the venue’s message." error={form.errors['branding_json.message']}>
                    <TextInput id="event-message" value={form.data.branding_json.message} onChange={(e) => setWelcome('message', e.target.value)} placeholder="e.g. Free WiFi all day. Join for the schedule." />
                </FormField>
            </fieldset>
            <FormField label="Extra question (optional)" hint="Asked on the WiFi sign-in while the event runs, e.g. “Where are you coming from?”" error={form.errors.question}>
                <TextInput id="event-question" value={form.data.question} onChange={(e) => form.setData('question', e.target.value)} />
            </FormField>
            <fieldset className="event-form__sponsor">
                <legend>Sponsor (optional)</legend>
                <FormField label="Sponsor name" error={form.errors['sponsor.name']}>
                    <TextInput id="event-sponsor-name" value={form.data.sponsor.name} onChange={(e) => setSponsor('name', e.target.value)} />
                </FormField>
                <FormField label="Message on the WiFi page" error={form.errors['sponsor.message']}>
                    <TextInput id="event-sponsor-message" value={form.data.sponsor.message} onChange={(e) => setSponsor('message', e.target.value)} placeholder="e.g. 2-for-1 coffee at the café stand" />
                </FormField>
                <FormField label="Sponsor link" error={form.errors['sponsor.url']}>
                    <TextInput id="event-sponsor-url" type="url" value={form.data.sponsor.url} onChange={(e) => setSponsor('url', e.target.value)} placeholder="https://" />
                </FormField>
            </fieldset>
            <FormActions>
                <PillButton type="button" variant="ghost" onClick={onCancel}>Cancel</PillButton>
                <PillButton type="submit" disabled={form.processing}>{event ? 'Save event' : 'Create event'}</PillButton>
            </FormActions>
        </form>
    );
}
