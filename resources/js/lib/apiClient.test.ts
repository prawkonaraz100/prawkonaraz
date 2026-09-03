import {
    apiClient,
    extractApiClientErrorCode,
    extractApiClientErrorMessage,
    isApiClientError,
} from '@/lib/apiClient';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const createJsonResponse = (payload: unknown, status = 200) =>
    new Response(JSON.stringify(payload), {
        status,
        headers: {
            'content-type': 'application/json',
        },
    });

describe('apiClient', () => {
    const originalDocument = globalThis.document;
    const originalFetch = globalThis.fetch;
    const fetchMock = vi.fn<typeof fetch>();

    beforeEach(() => {
        fetchMock.mockReset();
        Object.defineProperty(globalThis, 'fetch', {
            configurable: true,
            writable: true,
            value: fetchMock,
        });
    });

    afterEach(() => {
        Object.defineProperty(globalThis, 'fetch', {
            configurable: true,
            writable: true,
            value: originalFetch,
        });

        if (originalDocument === undefined) {
            Reflect.deleteProperty(globalThis, 'document');

            return;
        }

        Object.defineProperty(globalThis, 'document', {
            configurable: true,
            writable: true,
            value: originalDocument,
        });
    });

    it('prefers the XSRF cookie over a stale csrf meta token for write requests', async () => {
        Object.defineProperty(globalThis, 'document', {
            configurable: true,
            writable: true,
            value: {
                cookie: 'XSRF-TOKEN='.concat(encodeURIComponent('fresh-cookie-token')),
                querySelector: vi.fn().mockReturnValue({
                    getAttribute: vi.fn().mockReturnValue('stale-meta-token'),
                }),
            },
        });

        fetchMock.mockResolvedValue(createJsonResponse({
            data: {
                ok: true,
            },
        }));

        await apiClient.post('/api/test', {
            category_id: 1,
        });

        expect(fetchMock).toHaveBeenCalledWith('/api/test', expect.objectContaining({
            method: 'POST',
            credentials: 'same-origin',
            headers: expect.objectContaining({
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': 'fresh-cookie-token',
            }),
        }));
        expect(fetchMock.mock.calls[0]?.[1]?.headers).not.toHaveProperty('X-CSRF-TOKEN');
    });

    it('falls back to the csrf meta token when the XSRF cookie is unavailable', async () => {
        Object.defineProperty(globalThis, 'document', {
            configurable: true,
            writable: true,
            value: {
                cookie: '',
                querySelector: vi.fn().mockReturnValue({
                    getAttribute: vi.fn().mockReturnValue('meta-token'),
                }),
            },
        });

        fetchMock.mockResolvedValue(createJsonResponse({
            data: {
                ok: true,
            },
        }));

        await apiClient.patch('/api/test', {
            status: 'done',
        });

        expect(fetchMock.mock.calls[0]?.[1]?.headers).toEqual(expect.objectContaining({
            'X-CSRF-TOKEN': 'meta-token',
        }));
    });

    it('extracts ranked error code and message from the ranked error envelope', async () => {
        fetchMock.mockResolvedValue(createJsonResponse({
            error: {
                code: 'MATCH_FINISHED',
                message: 'Ten mecz rankingowy został już zakończony albo porzucony.',
            },
            error_event: {
                event: 'error',
                data: {
                    error_code: 'MATCH_FINISHED',
                    message: 'Ten mecz rankingowy został już zakończony albo porzucony.',
                },
            },
        }, 422));

        let thrownError: unknown = null;

        try {
            await apiClient.post('/api/ranked/test', {
                question_id: 1,
            });
        } catch (error) {
            thrownError = error;
        }

        expect(isApiClientError(thrownError)).toBe(true);

        if (!isApiClientError(thrownError)) {
            throw new Error('Expected ApiClientError to be thrown.');
        }

        expect(extractApiClientErrorCode(thrownError)).toBe('MATCH_FINISHED');
        expect(extractApiClientErrorMessage(thrownError)).toBe(
            'Ten mecz rankingowy został już zakończony albo porzucony.',
        );
    });
});
