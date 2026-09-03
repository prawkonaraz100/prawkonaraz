import { refreshCsrfSessionIfRequired } from '@/lib/csrfSession';

type RequestHeaders = Record<string, string>;

interface RequestOptions {
    headers?: RequestHeaders;
}

interface RequestWithBodyOptions extends RequestOptions {
    body?: unknown;
}

const resolveMetaCsrfToken = () =>
    typeof document === 'undefined'
        ? null
        : document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content')
        ?? null;

const resolveCookie = (name: string) => {
    if (typeof document === 'undefined') {
        return null;
    }

    const prefix = `${name}=`;
    const cookie = document.cookie
        .split(';')
        .map((value) => value.trim())
        .find((value) => value.startsWith(prefix));

    if (!cookie) {
        return null;
    }

    return decodeURIComponent(cookie.slice(prefix.length));
};

const buildJsonHeaders = (hasBody: boolean, headers: RequestHeaders = {}) => {
    const csrfToken = resolveMetaCsrfToken();
    const xsrfToken = resolveCookie('XSRF-TOKEN');

    return {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(hasBody ? { 'Content-Type': 'application/json' } : {}),
        // Prefer the current XSRF cookie whenever it exists. In our Inertia flow the
        // meta csrf token can become stale after session regeneration, and Laravel will
        // validate X-CSRF-TOKEN before it ever looks at X-XSRF-TOKEN.
        ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
        ...(!xsrfToken && csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        ...headers,
    };
};

const parseResponseBody = async (response: Response) => {
    if (response.status === 204) {
        return null;
    }

    const contentType = response.headers.get('content-type') ?? '';

    if (contentType.includes('application/json')) {
        return response.json();
    }

    const text = await response.text();

    if (!text) {
        return null;
    }

    try {
        return JSON.parse(text) as unknown;
    } catch {
        return text;
    }
};

export class ApiClientError extends Error {
    status: number;
    data: unknown;
    response: Response;

    constructor(response: Response, data: unknown) {
        super(`API request failed with status ${response.status}`);
        this.name = 'ApiClientError';
        this.status = response.status;
        this.data = data;
        this.response = response;
    }
}

export const isApiClientError = (
    error: unknown,
): error is ApiClientError => error instanceof ApiClientError;

export const isCsrfMismatchError = (error: unknown): error is ApiClientError =>
    isApiClientError(error)
    && (
        error.status === 419
        || extractApiClientErrorCode(error) === 'CSRF_TOKEN_MISMATCH'
    );

export const isAuthenticationExpiredError = (
    error: unknown,
): error is ApiClientError =>
    isApiClientError(error)
    && (error.status === 401 || isCsrfMismatchError(error));

const extractPayloadObject = (data: unknown) =>
    typeof data === 'object' && data !== null
        ? data as {
            error?: {
                code?: string;
                message?: string;
            };
            error_event?: {
                data?: {
                    error_code?: string;
                    message?: string;
                };
            };
        }
        : null;

export const extractApiClientErrorCode = (error: ApiClientError): string | null => {
    const payload = extractPayloadObject(error.data);

    return payload?.error_event?.data?.error_code
        ?? payload?.error?.code
        ?? null;
};

export const extractApiClientErrorMessage = (error: ApiClientError): string | null => {
    const payload = extractPayloadObject(error.data);

    return payload?.error_event?.data?.message
        ?? payload?.error?.message
        ?? null;
};

const request = async <T>(
    url: string,
    method: string,
    options: RequestWithBodyOptions = {},
) => {
    const hasBody = options.body !== undefined;

    if (!['GET', 'HEAD', 'OPTIONS'].includes(method.toUpperCase())) {
        await refreshCsrfSessionIfRequired();
    }

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: buildJsonHeaders(hasBody, options.headers),
        ...(hasBody ? { body: JSON.stringify(options.body) } : {}),
    });

    const data = await parseResponseBody(response);

    if (!response.ok) {
        throw new ApiClientError(response, data);
    }

    return data as T;
};

export const apiClient = {
    get: <T>(url: string, options: RequestOptions = {}) =>
        request<T>(url, 'GET', options),
    post: <T>(url: string, body?: unknown, options: RequestOptions = {}) =>
        request<T>(url, 'POST', {
            ...options,
            body,
        }),
    patch: <T>(url: string, body?: unknown, options: RequestOptions = {}) =>
        request<T>(url, 'PATCH', {
            ...options,
            body,
        }),
};
