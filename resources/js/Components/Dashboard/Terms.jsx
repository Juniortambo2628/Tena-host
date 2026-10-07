import { usePage } from '@inertiajs/react';

// Business accounts talk about customers and locations, hosts about guests
// and properties. Longest words first so "Properties" isn't half-replaced.
const BUSINESS_WORDS = [
    ['Properties', 'Locations'], ['properties', 'locations'],
    ['Property', 'Location'], ['property', 'location'],
    ['Guests', 'Customers'], ['guests', 'customers'],
    ['Guest', 'Customer'], ['guest', 'customer'],
];

export const toBusinessWording = (text) => BUSINESS_WORDS.reduce(
    (out, [from, to]) => out.replace(new RegExp(`\\b${from}\\b`, 'g'), to),
    text,
);

/**
 * Returns t(text): the text in the signed-in account's wording.
 */
export function useTerms() {
    const { auth } = usePage().props;
    const isBusiness = auth?.user?.account_type === 'business';

    return (text) => (isBusiness && typeof text === 'string' ? toBusinessWording(text) : text);
}

/** <T>Total Guests</T> → "Total Customers" for business accounts. */
export function T({ children }) {
    return useTerms()(children);
}
