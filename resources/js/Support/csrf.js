// Inertia keeps the document head across login, but Laravel rotates the token.
// Read the current encrypted cookie at request time, never a static meta tag.
export function csrfHeaders() {
    const cookie = document.cookie.split(';').map(value => value.trim())
        .find(value => value.startsWith('XSRF-TOKEN='));
    return cookie ? { 'X-XSRF-TOKEN': decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) } : {};
}
