import { usePage } from '@inertiajs/react';

const cap = (word) => word.charAt(0).toUpperCase() + word.slice(1);

/**
 * The signed-in account's words for "guests" and "properties" (App\Support\AccountType,
 * shared as auth.account): customers and locations for businesses, attendees
 * and venues for events. Longest words first so "Properties" isn't half-replaced.
 */
function replacements(account) {
    if (!account || account.type === 'host') return [];
    const { person, people, place, places } = account;
    return [
        ['Properties', cap(places)], ['properties', places],
        ['Property', cap(place)], ['property', place],
        ['Guests', cap(people)], ['guests', people],
        ['Guest', cap(person)], ['guest', person],
    ];
}

export const toAccountWording = (text, account) => replacements(account).reduce(
    (out, [from, to]) => out.replace(new RegExp(`\\b${from}\\b`, 'g'), to),
    text,
);

/**
 * Returns t(text): the text in the signed-in account's wording.
 */
export function useTerms() {
    const { auth } = usePage().props;
    return (text) => (typeof text === 'string' ? toAccountWording(text, auth?.account) : text);
}

/** <T>Total Guests</T> → "Total Customers" for business accounts, "Total Attendees" for events. */
export function T({ children }) {
    return useTerms()(children);
}
