import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Fetch all results from a paginated WordPress REST API endpoint.
 *
 * The WordPress REST API limits requests to a maximum of 100 items per page.
 * This utility automatically requests subsequent pages until all items have been retrieved,
 * concatenates them, and returns the full result set.
 *
 * @typeParam T - The item type returned by the endpoint.
 * @param path - The REST API endpoint path (e.g. `/wp/v2/posts`).
 * @param query - Query parameters to include with each request.
 * @param signal - Abort signal to cancel the request loop early.
 * @param perPage - Number of items to fetch per page. Defaults to 100.
 * @returns A promise that resolves with an array of all items across all pages.
 */
export async function fetchAllPages<T>(
  path: string,
  query: Record<string, string | number | boolean | undefined> = {},
  signal?: AbortSignal,
  perPage = 100
): Promise<T[]> {
  const firstPath = addQueryArgs(path, { ...query, per_page: perPage, page: 1 });

  const res = await apiFetch({
    path: firstPath,
    parse: false,
    signal,
  }) as unknown as Response;

  if (signal?.aborted) {
    return [];
  }

  const header = res.headers.get('X-WP-TotalPages');
  const parsedTotalPages = header ? Number(header) : 1;
  const totalPages = Number.isFinite(parsedTotalPages) && parsedTotalPages > 0
    ? parsedTotalPages
    : 1;

  const firstPageRaw = await res.json();
  const firstPage = Array.isArray(firstPageRaw) ? firstPageRaw as T[] : [];
  const all: T[] = [...firstPage];

  for (let page = 2; page <= totalPages; page += 1) {
    if (signal?.aborted) {
      break;
    }

    const pagePath = addQueryArgs(path, { ...query, per_page: perPage, page });

    try {
      const data = await apiFetch({
        path: pagePath,
        signal,
      }) as unknown as T[];

      if (Array.isArray(data) && data.length > 0) {
        all.push(...data);
      }
    } catch (e: unknown) {
      const err = e as { status?: number; code?: string; data?: { status?: number; code?: string } };
      const status = err?.status ?? err?.data?.status;
      const code = err?.code ?? err?.data?.code;

      if (status === 400 || code === 'invalid_page_number') {
        break;
      }

      throw e;
    }
  }

  return all;
}