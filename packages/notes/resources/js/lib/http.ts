/**
 * Laravel sets an XSRF-TOKEN cookie on every response; echoing it back in the
 * X-XSRF-TOKEN header satisfies CSRF protection for non-Inertia requests.
 */
export const xsrfToken = (): string => {
    const match = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.slice('XSRF-TOKEN='.length)) : '';
};

export const jsonHeaders = (): Record<string, string> => ({
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-XSRF-TOKEN': xsrfToken(),
});
