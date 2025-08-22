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
 * @param query - Query parameters to include with each request (e.g. filters, ordering, `_fields`).
 *   These are appended to `path` via `addQueryArgs`. Optional.
 * @param signal - Abort signal to cancel the request loop early. Optional.
 * @param perPage - Number of items to fetch per page (WordPress max is 100). Defaults to 100.
 * @returns A promise that resolves with an array of all items across all pages.
 *
 * @example
 * ```ts
 * // Fetch all posts of a custom type
 * const people = await fetchAllPages<CustomPersonRESTApi>(
 *   '/wp/v2/custom_person',
 *   { orderby: 'title', order: 'asc', _fields: 'id,title,meta' }
 * );
 * ```
 */
export async function fetchAllPages<T>(
  path: string,
  query: Record<string, any> = {},
  signal?: AbortSignal,
  perPage = 100,
): Promise<T[]> {
  let page = 1;
  const all: T[] = [];

  while (true) {
    const finalPath = addQueryArgs(path, { ...query, per_page: perPage, page });

    const data = await apiFetch({
      path: finalPath,
      signal,
    }) as unknown as T[];

    all.push(...data);

    if (!Array.isArray(data) || data.length < perPage) break;
    page += 1;
  }

  return all;
}
