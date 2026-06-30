// Thin fetch wrappers around the Admin\Morse JSON API. Endpoint URLs come from the
// page (data-url-* attributes) so PHP owns the routing. Presets are opaque blobs:
// `data` is whatever object the caller hands over, round-tripped as-is.

export function createPresetApi(urls) {
    // `id` is a route placeholder (admin/<presenter>/<action>/<id>), so it must go
    // into the path, not the query string.
    const withId = (url, id) => url.replace(/\/+$/, '') + '/' + encodeURIComponent(id);

    const json = async (res) => {
        if (!res.ok) {
            throw new Error('Komunikace se serverem selhala.');
        }
        return res.json();
    };

    return {
        /** @returns {Promise<{id: number, name: string}[]>} */
        list() {
            return fetch(urls.list, { headers: { Accept: 'application/json' } }).then(json);
        },

        /** @returns {Promise<{id: number, name: string, data: object}>} */
        load(id) {
            return fetch(withId(urls.load, id), { headers: { Accept: 'application/json' } }).then(json);
        },

        /** Overwrites an existing preset's data blob. */
        save(id, data) {
            return fetch(withId(urls.save, id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ data }),
            }).then(json);
        },

        /** Creates a new preset; resolves to {id, name}. */
        create(name, data) {
            return fetch(urls.create, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, data }),
            }).then(json);
        },
    };
}
