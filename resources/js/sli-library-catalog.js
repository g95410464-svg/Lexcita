import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

GlobalWorkerOptions.workerSrc = workerUrl;

export function mountCatalog(root) {
    const assets = import.meta.env.DEV ? '/node_modules/pdfjs-dist/' : root.dataset.pdfAssetsUrl;
    const queue = [];
    let active = 0;

    async function renderCover(cover) {
        let task;
        try {
            task = getDocument({url: cover.dataset.contentUrl, isEvalSupported: false, enableXfa: false,
                disableRange: true, cMapUrl: `${assets}cmaps/`, cMapPacked: true,
                standardFontDataUrl: `${assets}standard_fonts/`, wasmUrl: `${assets}wasm/`});
            const pdf = await task.promise;
            const page = await pdf.getPage(1);
            const base = page.getViewport({scale: 1});
            // Bound memory regardless of the PDF's page dimensions or screen density.
            const viewport = page.getViewport({scale: 600 / Math.max(base.width, base.height)});
            const canvas = document.createElement('canvas');
            canvas.width = Math.ceil(viewport.width);
            canvas.height = Math.ceil(viewport.height);
            canvas.setAttribute('aria-hidden', 'true');
            await page.render({canvasContext: canvas.getContext('2d'), viewport}).promise;
            cover.prepend(canvas);
            cover.dataset.coverState = 'ready';
        } catch (_) {
            // The title cover and the link to the reader remain usable.
            cover.dataset.coverState = 'unavailable';
        } finally {
            try { await task?.destroy(); } catch (_) { /* Worker already closed. */ }
        }
    }

    function drain() {
        while (active < 2 && queue.length) {
            active++;
            renderCover(queue.shift()).finally(() => { active--; drain(); });
        }
    }
    function enqueue(cover) {
        if (cover.dataset.coverState) return;
        cover.dataset.coverState = 'loading';
        queue.push(cover);
        drain();
    }
    const covers = root.querySelectorAll('[data-library-cover]');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                observer.unobserve(entry.target);
                enqueue(entry.target);
            });
        }, {rootMargin: '160px'});
        covers.forEach((cover) => observer.observe(cover));
    } else covers.forEach(enqueue);
}
