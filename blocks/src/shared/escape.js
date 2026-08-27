/**
 * Escapes a plain text value so that it can safely be added to an HTML string.
 *
 * The plain text fields from the MyClub API are stored as text in the database, so any HTML
 * special characters in them have to be escaped before they are inserted into innerHTML or
 * into an attribute value.
 *
 * @param {*} value The value to escape
 * @returns {string} The escaped value
 */
export const escapeHtml = (value) => {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
};
