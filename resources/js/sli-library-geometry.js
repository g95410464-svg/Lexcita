// Coordinates relative to the page survive zoom and device changes.
export function normalizeRects(rectangles, page) {
    if (!page.width || !page.height) return [];
    return Array.from(rectangles).flatMap((rect) => {
        const left = Math.max(page.left, rect.left);
        const top = Math.max(page.top, rect.top);
        const right = Math.min(page.left + page.width, rect.right);
        const bottom = Math.min(page.top + page.height, rect.bottom);
        if (right - left < 1 || bottom - top < 1) return [];
        return [{x: (left - page.left) / page.width, y: (top - page.top) / page.height,
            width: (right - left) / page.width, height: (bottom - top) / page.height}];
    });
}
