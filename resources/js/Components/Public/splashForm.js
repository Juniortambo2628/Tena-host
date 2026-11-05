/**
 * The sign-up form as the real guest WiFi page draws it
 * (resources/views/portal/splash.blade.php), so every drawn WiFi page on the
 * public site matches what guests actually see: first name and WhatsApp
 * number (always asked), email and birthday (optional, when the host turns
 * them on), then any extra question, and the privacy consent box.
 *
 * Takes the field lines an admin typed in a drawing's "screen" and returns
 * them in that order and wording; lines it doesn't recognise (an event's
 * question) are kept after the standard fields.
 */
const STANDARD = [
    { test: /first\s*name|^name$/i, label: 'First name' },
    { test: /whats\s*app|phone|\+254/i, label: '+254 WhatsApp number' },
    { test: /e-?mail/i, label: 'Email address (optional)' },
    { test: /birthday|date of birth/i, label: 'Birthday (optional)' },
];

/** The consent line every real WiFi page shows above the opt-in. */
export const SPLASH_CONSENT = 'I agree to the privacy policy';

export function splashFields(lines = []) {
    const found = STANDARD.map(() => false);
    const extra = [];

    lines.forEach((line) => {
        const i = STANDARD.findIndex(({ test }) => test.test(line));
        if (i === -1) extra.push(line);
        else found[i] = true;
    });

    // First name and WhatsApp number are required on every real page.
    found[0] = true;
    found[1] = true;

    return [...STANDARD.filter((_, i) => found[i]).map(f => f.label), ...extra];
}
