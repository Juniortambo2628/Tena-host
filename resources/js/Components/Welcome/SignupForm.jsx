import React, { useEffect, useMemo, useState } from 'react';
import { CheckCircle2, ArrowLeft, Loader2 } from 'lucide-react';
import { SectionWrapper, SectionHeader } from './layouts';
import { getContent, extractItems, stripHtml, baseSectionKey } from '@/lib/cms';
import { track } from '@/lib/analytics';
import { usePublic } from '@/Components/Public/PublicContext';
import LineList from '@/Components/Public/LineList';
import './SignupForm.css';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PILL_TYPES = ['singleSelect', 'radio', 'multiSelect'];

function parseFields(json) {
    try {
        const fields = typeof json === 'string' ? JSON.parse(json) : json;
        return Array.isArray(fields) ? fields.filter((f) => f?.key) : [];
    } catch {
        return [];
    }
}

/** CMS options are "value|Label", "Label" or { value, label }. */
function parseOptions(options) {
    return (Array.isArray(options) ? options : []).map((o) => {
        if (o && typeof o === 'object') return { value: String(o.value), label: String(o.label ?? o.value) };
        const [value, label] = String(o).split('|');
        return { value: value.trim(), label: (label ?? value).trim() };
    }).filter((o) => o.value);
}

const isRequired = (field) => field.required === true || field.required === '1' || field.required === 'true';
const isEmpty = (v) => v === undefined || v === null || (Array.isArray(v) ? v.length === 0 : String(v).trim() === '');
const phoneDigits = (v) => String(v || '').replace(/\D/g, '').replace(/^254/, '').replace(/^0/, '');
const formatKes = (n) => `KES ${Math.round(n).toLocaleString('en-KE')}`;

function clientError(field, value) {
    // "First name" -> "first name", but keep "WhatsApp number" as written.
    const raw = stripHtml(field.label);
    const label = /^[A-Z][a-z]/.test(raw) ? raw.charAt(0).toLowerCase() + raw.slice(1) : raw;
    if (isRequired(field) && isEmpty(value)) {
        return field.type === 'select' || PILL_TYPES.includes(field.type) || field.type === 'planCards'
            ? `Choose ${label}.`
            : `Add your ${label} to continue.`;
    }
    if (isEmpty(value)) return null;
    if (field.type === 'email' && !EMAIL_RE.test(value)) return 'Enter a valid email address.';
    if (field.type === 'tel' && phoneDigits(value).length < 9) return 'Enter a valid WhatsApp number, e.g. 712 345 678.';
    return null;
}

function Pills({ field, value, onChange, multiple }) {
    const selected = multiple ? (Array.isArray(value) ? value : []) : value;
    const toggle = (v) => {
        if (!multiple) return onChange(field.key, selected === v ? '' : v);
        onChange(field.key, selected.includes(v) ? selected.filter((x) => x !== v) : [...selected, v]);
    };
    return (
        <div className="signup-pills" role="group" aria-labelledby={`signup-${field.key}-label`}>
            {parseOptions(field.options).map((o) => {
                const on = multiple ? selected.includes(o.value) : selected === o.value;
                return (
                    <button key={o.value} type="button" className={`signup-pill ${on ? 'is-selected' : ''}`} aria-pressed={on} onClick={() => toggle(o.value)}>
                        {o.label}
                    </button>
                );
            })}
        </div>
    );
}

function PlanCards({ field, value, onChange, plans, discount, suffix }) {
    return (
        <div className="signup-plans" role="radiogroup" aria-labelledby={`signup-${field.key}-label`}>
            {parseOptions(field.options).map((o) => {
                const base = Number(plans[o.value]?.price_kes) || 0;
                const on = value === o.value;
                return (
                    <button key={o.value} type="button" role="radio" aria-checked={on} className={`signup-plan ${on ? 'is-selected' : ''}`} onClick={() => onChange(field.key, o.value)}>
                        <span className="signup-plan-name">{o.label}</span>
                        {base > 0 && (
                            <span className="signup-plan-price">
                                {discount > 0 && <s>{formatKes(base)}</s>} {formatKes(base * (1 - discount / 100))}
                                <small> {suffix}</small>
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}

function Field({ field, value, error, onChange, planProps }) {
    const id = `signup-${field.key}`;
    const label = stripHtml(field.label);
    const hint = stripHtml(field.hint);
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
                <option value="">Choose</option>
                {parseOptions(field.options).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
        );
    } else if (PILL_TYPES.includes(field.type)) {
        control = <Pills field={field} value={value} onChange={onChange} multiple={field.type === 'multiSelect'} />;
    } else if (field.type === 'planCards') {
        control = <PlanCards field={field} value={value} onChange={onChange} {...planProps} />;
    } else if (field.type === 'textarea') {
        control = <textarea rows={3} placeholder={field.placeholder} {...common} />;
    } else if (field.type === 'tel') {
        control = (
            <div className="signup-tel">
                <span className="signup-tel-prefix">{hint || '+254'}</span>
                <input type="tel" inputMode="tel" autoComplete="tel-national" placeholder={field.placeholder} {...common} />
            </div>
        );
    } else {
        control = <input type={['email', 'number'].includes(field.type) ? field.type : 'text'} placeholder={field.placeholder} {...common} />;
    }

    const wide = ['textarea', 'planCards', ...PILL_TYPES].includes(field.type);
    return (
        <div className={`signup-field ${wide ? 'signup-field--wide' : ''}`}>
            <label id={`${id}-label`} htmlFor={id} className="signup-label">
                {label}
                {isRequired(field) && <span className="signup-required" aria-hidden="true"> *</span>}
                {hint && field.type !== 'tel' && <span className="signup-hint"> {hint}</span>}
            </label>
            {control}
            {field.type === 'planCards' && planProps.note && <p className="signup-plan-note">{planProps.note}</p>}
            {error && <p id={`${id}-error`} className="signup-error">{error}</p>}
        </div>
    );
}

/**
 * Sign-up from Glen's SIGNUP-FIELDS.md: input steps defined in the CMS
 * (steps.{i}.fields), then a confirmation step with a summary, estimated
 * price and next steps. The server validates against the same definition
 * (SignupFormSchema).
 */
export default function SignupForm({ section }) {
    const { page, sections } = usePublic();
    const steps = useMemo(
        () => extractItems(section, 'steps', ['title', 'heading', 'description', 'fields']).map((s) => ({ ...s, fields: parseFields(s.fields) })),
        [section],
    );
    const allFields = steps.flatMap((s) => s.fields);
    const planField = allFields.find((f) => f.type === 'planCards');

    // Plan prices come from this page's pricing section (plans.{i}.id / price_kes).
    const plans = useMemo(() => {
        const pricing = sections.find((s) => baseSectionKey(s.section_key) === 'pricing');
        return Object.fromEntries(extractItems(pricing, 'plans', ['id', 'label', 'price_kes']).map((p) => [p.id, p]));
    }, [sections]);

    const [step, setStep] = useState(0);
    const [values, setValues] = useState(() => (planField?.placeholder ? { [planField.key]: planField.placeholder } : {}));
    const [errors, setErrors] = useState({});
    const [consent, setConsent] = useState(false);
    const [status, setStatus] = useState('idle'); // idle | submitting | done
    const [formError, setFormError] = useState('');

    // "#join?plan=growth" (from a pricing card) pre-selects that plan.
    useEffect(() => {
        if (!planField) return undefined;
        const fromHash = () => {
            const match = window.location.hash.match(/plan=(\w+)/);
            const wanted = match && parseOptions(planField.options).find((o) => o.value.toLowerCase() === match[1].toLowerCase());
            if (wanted) setValues((v) => ({ ...v, [planField.key]: wanted.value }));
        };
        fromHash();
        window.addEventListener('hashchange', fromHash);
        return () => window.removeEventListener('hashchange', fromHash);
    }, [planField]);

    if (!section || steps.length === 0) return null;

    const text = (key, fallback = '') => stripHtml(getContent(section, key, fallback));
    const type = text('signup_type', 'host');
    const isLastInput = step === steps.length - 1;
    const current = steps[step];

    // Multi-unit discount, e.g. units "10-49" => 20% (hosts only).
    const discountField = text('discount_field');
    const discountRule = extractItems(section, 'discounts', ['match', 'percent']).find((d) => discountField && d.match === values[discountField]);
    const discount = Number(discountRule?.percent) || 0;
    const planProps = {
        plans,
        discount,
        suffix: text('price_suffix', 'per month'),
        note: discount ? text('discount_applied').replace('{percent}', discount) : text('discount_hint'),
    };
    const selectedPlan = planField ? values[planField.key] : null;
    const basePrice = Number(plans[selectedPlan]?.price_kes) || 0;
    const estimatedPrice = basePrice ? Math.round(basePrice * (1 - discount / 100)) : null;

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

    const payload = () => {
        const body = { type, ...values };
        allFields.filter((f) => f.type === 'tel' && body[f.key]).forEach((f) => {
            body[f.key] = `+254${phoneDigits(body[f.key])}`;
        });
        return {
            ...body,
            estimatedPriceKES: estimatedPrice,
            consent: true,
            consentText: text('consent_text'),
            submittedAt: new Date().toISOString(),
            source: window.location.pathname,
        };
    };

    const submit = async (e) => {
        e.preventDefault();
        if (!validateStep(step)) return;
        if (!isLastInput) {
            track(`signup_step_${step + 1}`, page.slug);
            return setStep(step + 1);
        }

        if (!consent) {
            setErrors((er) => ({ ...er, consent: 'Tick the box so we can contact you about your application.' }));
            return;
        }

        track(`signup_step_${step + 1}`, page.slug);
        setStatus('submitting');
        setFormError('');
        track('signup_submit', page.slug);

        try {
            await window.axios.post('/api/signups', payload());
            // Only confirm once the server has saved the application.
            setStatus('done');
            track('signup_success', page.slug);
        } catch (err) {
            setStatus('idle');
            const serverErrors = err.response?.data?.errors;
            if (err.response?.status === 422 && serverErrors) {
                const mapped = Object.fromEntries(Object.entries(serverErrors).map(([k, v]) => [k.split('.')[0], Array.isArray(v) ? v[0] : v]));
                setErrors(mapped);
                const firstBadStep = steps.findIndex((s) => s.fields.some((f) => mapped[f.key]));
                if (firstBadStep >= 0) setStep(firstBadStep);
                setFormError(mapped.type || mapped.consent || 'Please check the highlighted fields.');
            } else {
                setFormError(err.response?.data?.message && err.response.status === 429 ? err.response.data.message : text('error_message', 'Something went wrong. Please try again.'));
            }
        }
    };

    const summary = () => allFields
        .filter((f) => !isEmpty(values[f.key]) && f.type !== 'planCards' && f.type !== 'tel')
        .map((f) => {
            const options = parseOptions(f.options);
            const display = (v) => options.find((o) => o.value === v)?.label ?? v;
            const value = values[f.key];
            return [stripHtml(f.label), Array.isArray(value) ? value.map(display).join(', ') : display(value)];
        })
        .concat(planField ? [[stripHtml(planField.label), parseOptions(planField.options).find((o) => o.value === selectedPlan)?.label || '-']] : [])
        .concat(planField ? [['Estimated price', estimatedPrice ? `${formatKes(estimatedPrice)} ${planProps.suffix}` : 'To be recommended']] : [])
        .concat(allFields.filter((f) => f.type === 'tel' && values[f.key]).map((f) => [stripHtml(f.label), `+254 ${phoneDigits(values[f.key])}`]));

    const progress = [...steps.map((s) => stripHtml(s.title)), 'Confirmation'];
    const activeIndex = status === 'done' ? steps.length : step;
    const firstName = values.firstName || values.first_name || '';

    return (
        <SectionWrapper bg={section.bg || 'white'} width="narrow">
            <SectionHeader
                badge={text('badge')}
                title={text('title', 'Apply to join')}
                subtitle={getContent(section, 'subtitle', '')}
            />

            <div className="signup-card">
                <ol className="signup-progress" aria-label="Sign-up steps">
                    {progress.map((title, i) => (
                        <li key={i} className={i === activeIndex ? 'is-current' : i < activeIndex ? 'is-done' : ''} aria-current={i === activeIndex ? 'step' : undefined}>
                            <span className="signup-progress-num">{i + 1}</span>
                            <span className="signup-progress-title">{title}</span>
                        </li>
                    ))}
                </ol>

                {status === 'done' ? (
                    <div className="signup-success" role="status">
                        <CheckCircle2 size={44} className="signup-success-icon" />
                        <h3>{text('success_title', 'Asante!').replace('{firstName}', firstName).replace(/,\s*!/, '!')}</h3>
                        <p>{text('success_message')}</p>
                        <dl className="signup-summary">
                            {summary().map(([label, value]) => (
                                <div key={label}><dt>{label}</dt><dd>{value}</dd></div>
                            ))}
                        </dl>
                        {text('next_steps') && (
                            <div className="signup-next">
                                <h4>{text('next_steps_title', 'What happens next')}</h4>
                                <LineList text={getContent(section, 'next_steps', '')} className="signup-next-list" />
                            </div>
                        )}
                    </div>
                ) : (
                    <form onSubmit={submit} noValidate>
                        {current.heading && <h3 className="signup-step-heading">{stripHtml(current.heading)}</h3>}
                        {current.description && <p className="signup-step-desc">{stripHtml(current.description)}</p>}

                        <div className="signup-fields">
                            {current.fields.map((f) => (
                                <Field key={f.key} field={f} value={values[f.key]} error={errors[f.key]} onChange={setValue} planProps={planProps} />
                            ))}
                        </div>

                        {isLastInput && (
                            <label className="signup-consent">
                                <input
                                    type="checkbox"
                                    checked={consent}
                                    onChange={(e) => { setConsent(e.target.checked); setErrors((er) => ({ ...er, consent: undefined })); }}
                                />
                                <span>
                                    {text('consent_text')}{' '}
                                    <a href="/privacy" target="_blank" rel="noopener">Privacy policy</a>
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
                                {isLastInput ? text('submit_label', 'Submit application') : 'Next step'}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </SectionWrapper>
    );
}
