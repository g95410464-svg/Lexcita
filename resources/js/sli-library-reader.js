import { getDocument, GlobalWorkerOptions, TextLayer } from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { normalizeRects } from './sli-library-geometry';
import '../css/sli-library-reader.css';

GlobalWorkerOptions.workerSrc = workerUrl;

export async function mountReader(root) {
    const find = (name) => root.querySelector(`[data-reader-${name}]`);
    const sheet = find('sheet');
    const scroller = find('scroll');
    const status = find('status');
    const pageInput = find('page');
    const zoom = find('zoom');
    const marks = [...root.querySelectorAll('[data-reader-mark]')];
    let pdf, annotations = [], pageNumber = 1, renderVersion = 0, renderTask, textLayer;
    let selection = null, busy = false, ready = false;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    function message(text, error = false) {
        status.textContent = text;
        status.classList.toggle('is-error', error);
    }
    function controls() {
        find('prev').disabled = !ready || pageNumber <= 1;
        find('next').disabled = !ready || pageNumber >= (pdf?.numPages || 1);
        pageInput.disabled = zoom.disabled = !ready;
        find('bookmark').disabled = !ready || busy;
        marks.forEach((button) => button.disabled = !ready || busy || !selection);
    }
    async function request(url, options = {}) {
        const response = await fetch(url, {...options, credentials: 'same-origin', headers: {
            Accept: 'application/json', 'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/json', ...options.headers,
        }});
        if (!response.ok || response.redirected) throw new Error('request');
        return response.status === 204 ? null : response.json();
    }
    function clearSelection() {
        selection = null;
        find('selection').textContent = 'Ningún texto seleccionado.';
        controls();
    }
    function paintMarks() {
        const overlay = sheet.querySelector('.sli-reader-marks');
        if (!overlay) return;
        overlay.replaceChildren();
        annotations.filter((item) => item.page === pageNumber).forEach((item) => {
            (item.rects || []).forEach((rect) => {
                const mark = document.createElement('span');
                mark.className = `sli-reader-mark is-${item.kind}`;
                Object.assign(mark.style, {left: `${rect.x * 100}%`, top: `${rect.y * 100}%`,
                    width: `${rect.width * 100}%`, height: `${rect.height * 100}%`});
                overlay.append(mark);
            });
        });
    }
    function listNotes() {
        const container = find('notes');
        container.replaceChildren();
        if (!annotations.length) {
            const empty = document.createElement('p');
            empty.textContent = 'Tus anotaciones guardadas aparecerán aquí.';
            container.append(empty);
        }
        const names = {highlight: 'Resaltado', underline: 'Subrayado', bookmark: 'Marcador'};
        [...annotations].sort((a, b) => a.page - b.page || a.id - b.id).forEach((item) => {
            const article = document.createElement('article');
            const jump = document.createElement('button');
            jump.type = 'button';
            jump.textContent = `${names[item.kind]} · Página ${item.page}`;
            jump.addEventListener('click', () => render(item.page));
            article.append(jump);
            if (item.quote) {
                const quote = document.createElement('blockquote');
                quote.textContent = item.quote;
                article.append(quote);
            }
            if (item.note) {
                const note = document.createElement('p');
                note.textContent = item.note;
                article.append(note);
            }
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = 'Quitar mi anotación';
            remove.addEventListener('click', async () => {
                if (busy) return;
                busy = true; remove.disabled = true; controls();
                try {
                    await request(`${root.dataset.annotationsUrl}/${item.id}`, {method: 'DELETE'});
                    annotations = annotations.filter((entry) => entry.id !== item.id);
                    paintMarks(); listNotes(); message('Anotación eliminada.');
                } catch (_) { remove.disabled = false; message('No pudimos quitar la anotación. Inténtalo de nuevo.', true); }
                finally { busy = false; controls(); }
            });
            article.append(remove);
            container.append(article);
        });
    }
    async function render(number) {
        if (!pdf) return;
        const version = ++renderVersion;
        pageNumber = Math.min(pdf.numPages, Math.max(1, Number.parseInt(number, 10) || 1));
        ready = false; clearSelection();
        renderTask?.cancel(); textLayer?.cancel();
        pageInput.value = pageNumber;
        message(`Cargando página ${pageNumber}…`);
        try {
            const page = await pdf.getPage(pageNumber);
            if (version !== renderVersion) return;
            const base = page.getViewport({scale: 1});
            const available = Math.max(180, scroller.clientWidth - 32);
            const scale = Math.min(1.5, available / base.width) * Number(zoom.value);
            const viewport = page.getViewport({scale});
            const ratio = Math.min(window.devicePixelRatio || 1, 2);
            const canvas = document.createElement('canvas');
            canvas.width = Math.floor(viewport.width * ratio);
            canvas.height = Math.floor(viewport.height * ratio);
            canvas.style.width = `${viewport.width}px`;
            canvas.style.height = `${viewport.height}px`;
            canvas.setAttribute('aria-label', `Página ${pageNumber} del libro`);
            const layer = document.createElement('div');
            layer.className = 'textLayer';
            const overlay = document.createElement('div');
            overlay.className = 'sli-reader-marks';
            overlay.setAttribute('aria-hidden', 'true');
            sheet.style.width = `${viewport.width}px`;
            sheet.style.height = `${viewport.height}px`;
            sheet.style.setProperty('--total-scale-factor', scale * viewport.userUnit);
            sheet.style.setProperty('--scale-round-x', '1px');
            sheet.style.setProperty('--scale-round-y', '1px');
            sheet.replaceChildren(canvas, overlay, layer);
            renderTask = page.render({canvasContext: canvas.getContext('2d'), viewport,
                transform: ratio === 1 ? null : [ratio, 0, 0, ratio, 0, 0]});
            await renderTask.promise;
            if (version !== renderVersion) return;
            textLayer = new TextLayer({textContentSource: await page.getTextContent(), container: layer, viewport});
            await textLayer.render();
            if (version !== renderVersion) return;
            ready = true; controls(); paintMarks();
            scroller.scrollTo({top: 0, left: 0});
            message(`Página ${pageNumber} de ${pdf.numPages}. Selecciona texto para marcarlo.`);
        } catch (error) {
            if (version !== renderVersion || error.name === 'RenderingCancelledException' || error.name === 'AbortException') return;
            message('No pudimos mostrar esta página. Vuelve a abrir el libro o contacta con g95410464@proton.me.', true);
        }
    }
    document.addEventListener('selectionchange', () => {
        if (!ready || busy) return;
        const current = window.getSelection();
        const layer = sheet.querySelector('.textLayer');
        if (!current?.rangeCount || current.isCollapsed || !layer?.contains(current.anchorNode)
            || !layer.contains(current.focusNode)) return;
        const quote = current.toString().trim();
        const rects = normalizeRects(current.getRangeAt(0).getClientRects(), sheet.getBoundingClientRect());
        if (!quote || quote.length > 10000 || !rects.length || rects.length > 200) {
            clearSelection(); return;
        }
        selection = {page: pageNumber, quote, rects};
        find('selection').textContent = `Texto seleccionado: ${quote.slice(0, 220)}${quote.length > 220 ? '…' : ''}`;
        controls();
    });
    async function save(kind) {
        if (!ready || busy || (kind !== 'bookmark' && !selection)) return;
        const data = kind === 'bookmark' ? {page: pageNumber, kind, rects: []} : {...selection, kind};
        data.note = find('note').value.trim();
        busy = true; controls(); message('Guardando tu anotación…');
        try {
            annotations.push(await request(root.dataset.annotationsUrl, {method: 'POST', body: JSON.stringify(data)}));
            find('note').value = '';
            window.getSelection()?.removeAllRanges();
            clearSelection(); paintMarks(); listNotes(); message('Guardado en tus anotaciones personales.');
        } catch (_) { message('No pudimos guardar la anotación. Inténtalo de nuevo; si persiste, contacta con g95410464@proton.me.', true); }
        finally { busy = false; controls(); }
    }
    marks.forEach((button) => {
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => save(button.dataset.readerMark));
    });
    find('bookmark').addEventListener('click', () => save('bookmark'));
    find('prev').addEventListener('click', () => render(pageNumber - 1));
    find('next').addEventListener('click', () => render(pageNumber + 1));
    pageInput.addEventListener('change', () => render(pageInput.value));
    zoom.addEventListener('change', () => render(pageNumber));
    let resizeTimer;
    let previousWidth = scroller.clientWidth;
    new ResizeObserver(() => {
        if (Math.abs(scroller.clientWidth - previousWidth) < 2) return;
        previousWidth = scroller.clientWidth;
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => render(pageNumber), 200);
    }).observe(scroller);
    try {
        const assets = import.meta.env.DEV ? '/node_modules/pdfjs-dist/' : root.dataset.pdfAssetsUrl;
        [pdf, annotations] = await Promise.all([
            getDocument({url: root.dataset.contentUrl, isEvalSupported: false, enableXfa: false,
                disableRange: true, cMapUrl: `${assets}cmaps/`, cMapPacked: true,
                standardFontDataUrl: `${assets}standard_fonts/`, wasmUrl: `${assets}wasm/`}).promise,
            request(root.dataset.annotationsUrl),
        ]);
        find('pages').textContent = `de ${pdf.numPages}`;
        pageInput.max = pdf.numPages;
        listNotes(); await render(1);
    } catch (_) {
        message('No pudimos abrir el libro. Vuelve a iniciar sesión e inténtalo de nuevo. Si persiste, contacta con g95410464@proton.me.', true);
    }
}
