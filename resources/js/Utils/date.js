/**
 * Universal date formatter utility for Pirgacha Internet.
 * Converts ISO 8601 strings (e.g. "2026-11-23T00:00:00.000000Z") or raw date strings into clean, human-readable formats.
 */
export function formatDate(val, withTime = false) {
    if (!val || val === 'N/A' || val === 'null' || val === 'undefined') {
        return 'N/A';
    }

    if (typeof val === 'string') {
        const clean = val.replace('Z', '').trim();

        // If it's a date-only timestamp (e.g. ending in T00:00:00) or pure date "YYYY-MM-DD"
        if (clean.includes('T00:00:00') || /^\d{4}-\d{2}-\d{2}$/.test(clean)) {
            const datePart = clean.split('T')[0];
            const parts = datePart.split('-');
            if (parts.length === 3) {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const month = months[parseInt(parts[1], 10) - 1] || parts[1];
                return `${parts[2]} ${month} ${parts[0]}`;
            }
        }
    }

    try {
        const d = new Date(val);
        if (isNaN(d.getTime())) return String(val);

        const day = String(d.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[d.getMonth()];
        const year = d.getFullYear();

        if (withTime) {
            let hours = d.getHours();
            const minutes = String(d.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            return `${day} ${month} ${year}, ${hours}:${minutes} ${ampm}`;
        }

        return `${day} ${month} ${year}`;
    } catch {
        return String(val);
    }
}

export function formatDateTime(val) {
    return formatDate(val, true);
}
