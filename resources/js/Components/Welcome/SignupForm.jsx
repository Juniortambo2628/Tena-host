import React, { useMemo, useState } from 'react';
import { CheckCircle2, ArrowLeft, ArrowRight, Loader2 } from 'lucide-react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems, stripHtml } from '@/lib/cms';
import { track } from '@/lib/analytics';
import { usePublic } from '@/Components/Public/PublicContext';
import './SignupForm.css';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function parseFields(json) {
    try {
        const fields = typeof json === 'string' ? JSON.parse(json) : json;
        return Array.isArray(fields) ? fields.filter((f) => f?.key) : [];
    } catch {
        return [];
    }
}

const isRequired = (field) => field.required === true || field.required === '1' || field.required === 'true';

function clientError(field, value) {
    const empty = value === undefined || value === null || String(value).trim() === '';
    if (isRequired(field) && empty) return `Please fill in ${stripHtml(field.label).toLowerCase()}.`;
    if (!empty && field.type === 'email' && !EMAIL_RE.test(value)) return 'Please enter a valid email address.';
    return null;
}

function Field({ field, value, error, onChange }) {
    const id = `signup-${field.key}`;
    const label = stripHtml(field.label);
    const options = Array.isArray(field.options) ? field.options : [];
    const common = {
        id,
        name: field.key,
        value: value ?? '',
        onChange: (e) => onChange(field.key, e.target.value),
        'aria-invalid': !!error,
        'aria-describedby': error ? `${id}-error` : undefined,
        className: 'signup-input',
    };

    let control;
    if (field.type === 'select') {
        control = (
            <select {...common}>
                <option value="">Select…</option>
                {options.map((o) => <option key={o} value={o}>{o}</option>)}
            </select>
        );
    } else if (field.type === 'radio') {
        control = (
            <div className="signup-radio-group" role="radiogroup" aria-labelledby={`${id}-label`}>
                {options.map((o) => (
                    <label key={o} className={`signup-radio ${value === o ? 'is-selected' : ''}`}>
                        <input type="radio" name={field.key} value={o} checked={value === o} onChange={() => onChange(field.key, o)} />
                        {o}
                    </label>
                ))}
            </div>
        );
    } else if (field.type === 'textarea') {
        control = <textarea rows={3} placeholder={field.placeholder} {...common} />;
    } else if (field.type === 'checkbox') {
        return (
            <label className="signup-checkbox">
                <input type="checkbox" checked={!!value} onChange={(e) => onChange(field.key, e.target.checked)} />
                <span>{label}</span>
            </label>
        );
    } else {
        control = <input type={['email', 'tel', 'number'].includes(field.type) ? field.type : 'text'} placeholder={field.placeholder} {...common} />;
    }

    return (
        <div className={`signup-field ${field.type === 'textarea' || field.type === 'radio' ? 'signup-field--wide' : ''}`}>
            <label id={`${id}-label`} htmlFor={id} className="signup-label">
                {label}{isRequired(field) && <span className="signup-required" aria-hidden="true"> *</span>}
            </label>
            {control}
            {error && <p id={`${id}-error`} className="signup-error">{error}</p>}
        </div>
    );
}

/**
 * Multi-step sign-up, defined entirely in the CMS (steps.{i}.fields). The
 * server validates against the same definition (SignupFormSchema).
 */
export default function SignupForm({ section }) {
    const { page } = usePublic();
    const steps = useMemo(
        () => extractItems(section, 'steps', ['title', 'description', 'fields']).map((s) => ({ ...s, fields: parseFields(s.fields) })),
        [section],
    );

    const [step, setStep] = useState(0);
    const [values, setValues] = useState({});
    const [errors, setErrors] = useState({});
    const [consent, setConsent] = useState(false);
    const [status, setStatus] = useState('idle'); // idle | submitting | done
    const [formError, setFormError] = useState('');

    if (!section || steps.length === 0) return null;

    const anchor = stripHtml(getContent(section, 'anchor', 'join'));
    const type = stripHtml(getContent(section, 'signup_type', 'host'));
    const isLast = step === steps.length - 1;
    const current = steps[step];

    const setValue = (key, value) => {
        setValues((v) => ({ ...v, [key]: value }));
        setErrors((e) => ({ ...e, [key]: undefined }));
    };

    const validateStep = (index) => {
        const stepErrors = {};
        steps[index].fields.forEach((f) => {
            const message = clientError(f, values[f.key]);
            if (message) stepErrors[f.key] = message;
        });
        setErrors((e) => ({ ...e, ...stepErrors }));
        return Object.keys(stepErrors).length === 0;
    };

    const next = () => {
        if (!validateStep(step)) return;
        track(`signup_step_${step + 1}`, page.slug);
        setStep(step + 1);
    };

    const submit = async (e) => {
        e.preventDefault();
        if (!isLast) return next();
        if (!validateStep(step)) return;
        if (!consent) {
            setErrors((er) => ({ ...er, consent: 'Please tick the box to continue.' }));
            return;
        }

        setStatus('submitting');
        setFormError('');
        track(`signup_step_${step + 1}`, page.slug);
        track('signup_submit', page.slug);

        try {
            await window.axios.post('/api/signups', { type, ...values, consent: true });
            // Only confirm once the server has saved the application.
            setStatus('done');
            track('signup_success', page.slug);
        } catch (err) {
            setStatus('idle');
            const serverErrors = err.response?.data?.errors;
            if (err.response?.status === 422 && serverErrors) {
                const mapped = Object.fromEntries(Object.entries(serverErrors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                setErrors(mapped);
                const firstBadStep = steps.findIndex((s) => s.fields.some((f) => mapped[f.key]));
                if (firstBadStep >= 0) setStep(firstBadStep);
                setFormError(mapped.type || mapped.consent || 'Please check the highlighted fields.');
            } else {
                setFormError(err.response?.data?.message || 'Something went wrong. Please try again in a moment.');
            }
        }
    };

    return (
        <SectionWrapper id={anchor} bg={section.bg || 'white'} width="narrow">
            <SectionHeader
                badge={stripHtml(getContent(section, 'badge', ''))}
                title={stripHtml(getContent(section, 'title', 'Apply to join'))}
                subtitle={getContent(section, 'subtitle', '')}
            />

            <div className="signup-card">
                {status === 'done' ? (
                    <div className="signup-success" role="status">
                        <CheckCircle2 size={48} className="signup-success-icon" />
                        <h3>{stripHtml(getContent(section, 'success_title', 'Thank you!'))}</h3>
                        <p>{stripHtml(getContent(section, 'success_message', "We'll be in touch shortly."))}</p>
                    </div>
                ) : (
                    <form onSubmit={submit} noValidate>
                        <ol className="signup-progress" aria-label="Sign-up steps">
                            {steps.map((s, i) => (
                                <li key={i} className={i === step ? 'is-current' : i < step ? 'is-done' : ''} aria-current={i === step ? 'step' : undefined}>
                                    <span className="signup-progress-num">{i + 1}</span>
                                    <span className="signup-progress-title">{stripHtml(s.title)}</span>
                                </li>
                            ))}
                        </ol>

                        {current.description && <p className="signup-step-desc">{stripHtml(current.description)}</p>}

                        <div className="signup-fields">
                            {current.fields.map((f) => (
                                <Field key={f.key} field={f} value={values[f.key]} error={errors[f.key]} onChange={setValue} />
                            ))}
                        </div>

                        {isLast && (
                            <label className="signup-consent">
                                <input
                                    type="checkbox"
                                    checked={consent}
                                    onChange={(e) => { setConsent(e.target.checked); setErrors((er) => ({ ...er, consent: undefined })); }}
                                />
                                <span>
                                    {stripHtml(getContent(section, 'consent_text', ''))}{' '}
                                    <a href="/privacy" target="_blank" rel="noopener">Privacy Policy</a> · <a href="/terms" target="_blank" rel="noopener">Terms</a>
                                </span>
                            </label>
                        )}
                        {errors.consent && <p className="signup-error">{errors.consent}</p>}
                        {formError && <p className="signup-form-error" role="alert">{formError}</p>}

                        <div className="signup-actions">
                            {step > 0 && (
                                <button type="button" className="signup-btn-back" onClick={() => setStep(step - 1)}>
                                    <ArrowLeft size={16} /> Back
                                </button>
                            )}
                            <button type="submit" className="signup-btn-next" disabled={status === 'submitting'}>
                                {status === 'submitting' && <Loader2 size={16} className="animate-spin" />}
                                {isLast ? stripHtml(getContent(section, 'submit_label', 'Submit')) : 'Continue'}
                                {!isLast && <ArrowRight size={16} />}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </SectionWrapper>
    );
}
