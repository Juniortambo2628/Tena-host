/**
 * Safe route helper - returns the URL for a named route, or a fallback if the
 * route doesn't exist in the current Ziggy route list.
 *
 * Usage:
 *   safeRoute('admin.payments.index') → '/admin/payments' or fallback
 *   safeRoute('admin.payments.show', { transaction: 1 }) → '/admin/payments/1' or fallback
 *   safeRoute('admin.payments.index', {}, '#') → '#' if route missing
 */
export function safeRoute(name, params = {}, fallback = '#') {
    try {
        if (typeof route === 'undefined') return fallback;
        if (typeof route().has === 'function' && !route().has(name)) {
            return fallback;
        }
        return route(name, params);
    } catch {
        return fallback;
    }
}

/**
 * Check if a named route exists in the current Ziggy route list.
 */
export function hasRoute(name) {
    try {
        if (typeof route === 'undefined') return false;
        if (typeof route().has === 'function') return route().has(name);
        return true;
    } catch {
        return false;
    }
}
