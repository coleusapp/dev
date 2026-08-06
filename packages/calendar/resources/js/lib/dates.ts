export const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

export const hours = Array.from({ length: 24 }, (_, i) => i);

// Always 42 cells (6 full weeks) so the grid height stays constant across months.
// Leading/trailing cells spill into the adjacent month rather than being blank.
export function buildMonthGrid(year: number, month: number): Date[] {
    const firstDayIndex = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();

    const cells: Date[] = [
        ...Array.from({ length: firstDayIndex }, (_, i) => new Date(year, month, i - firstDayIndex + 1)),
        ...Array.from({ length: totalDays }, (_, i) => new Date(year, month, i + 1)),
    ];

    while (cells.length < 42) {
        cells.push(new Date(year, month, cells.length - firstDayIndex + 1));
    }

    return cells;
}

export function buildWeekDates(date: Date): Date[] {
    const start = new Date(date);
    start.setDate(date.getDate() - date.getDay());

    return Array.from({ length: 7 }, (_, i) => {
        const day = new Date(start);
        day.setDate(start.getDate() + i);
        return day;
    });
}

export function isSameDay(a: Date | null, b: Date): boolean {
    return a !== null && a.toDateString() === b.toDateString();
}

export function isSameMonth(a: Date, b: Date): boolean {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth();
}
