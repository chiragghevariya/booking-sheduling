// Date helpers used by the calendar components. Pure functions, no UI deps.

export function startOfDay(d) {
    const x = new Date(d);
    x.setHours(0, 0, 0, 0);
    return x;
}

export function addDays(d, n) {
    const x = new Date(d);
    x.setDate(x.getDate() + n);
    return x;
}

export function isSameDay(a, b) {
    return a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate();
}

export function toISODate(d) {
    return new Date(d).toISOString().slice(0, 10);
}

/**
 * Returns the 6-row × 7-col grid of dates covering the month containing `d`,
 * starting on Monday.
 */
export function monthGrid(d) {
    const first = new Date(d.getFullYear(), d.getMonth(), 1);
    // 0 = Mon ... 6 = Sun
    const offset = (first.getDay() + 6) % 7;
    const start = addDays(first, -offset);

    const cells = [];
    for (let i = 0; i < 42; i++) {
        const date = addDays(start, i);
        cells.push({
            date,
            inMonth: date.getMonth() === d.getMonth(),
        });
    }
    return cells;
}

/**
 * Returns 7 dates starting from the Monday on/before `d`.
 */
export function weekDays(d) {
    const offset = (d.getDay() + 6) % 7;
    const start = addDays(startOfDay(d), -offset);
    return Array.from({ length: 7 }, (_, i) => addDays(start, i));
}

export function fmtMonth(d) {
    return d.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
}

export function fmtTime(iso) {
    return new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}

export function fmtDateLong(d) {
    return new Date(d).toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
}

export function fmtDateShort(d) {
    return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}
