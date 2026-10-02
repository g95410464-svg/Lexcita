(function () {
    'use strict';

    // La disponibilidad es orientativa: Laravel la comprueba otra vez al reservar.
    window.createSlotLoader = function ({ url, onLoading, onSuccess, onError, delay = 200, ttl = 15000 }) {
        const cache = new Map();
        let timer;
        let controller;
        let pendingKey = null;
        let version = 0;
        let blockedUntil = 0;

        function rateLimitError(seconds) {
            const error = new Error(`Demasiadas consultas. Espera ${seconds} segundos para volver a cargar horarios.`);
            error.status = 429;
            error.retryAfter = seconds;
            return error;
        }

        function reset() {
            version++;
            clearTimeout(timer);
            controller?.abort();
            controller = null;
            pendingKey = null;
        }

        function select(abogadoId, fecha) {
            if (!abogadoId || !fecha) {
                reset();
                return;
            }

            const key = `${abogadoId}:${fecha}`;
            // Un doble clic comparte la consulta que ya está programada o en curso.
            if (pendingKey === key) return;

            reset();
            const requestVersion = version;
            const cached = cache.get(key);
            if (cached && cached.expiresAt > Date.now()) {
                onSuccess(cached.slots);
                return;
            }

            if (blockedUntil > Date.now()) {
                onError(rateLimitError(Math.ceil((blockedUntil - Date.now()) / 1000)));
                return;
            }

            pendingKey = key;
            onLoading();
            timer = setTimeout(async () => {
                controller = new AbortController();
                try {
                    const query = new URLSearchParams({ abogado_id: abogadoId, fecha });
                    const response = await fetch(`${url}?${query}`, {
                        signal: controller.signal,
                        cache: 'no-store',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (response.status === 429) {
                        const retryAfter = Number(response.headers?.get('Retry-After'));
                        const seconds = Number.isFinite(retryAfter) && retryAfter > 0
                            ? Math.ceil(retryAfter) : 60;
                        if (requestVersion === version) blockedUntil = Date.now() + seconds * 1000;
                        throw rateLimitError(seconds);
                    }
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const slots = await response.json();
                    if (!Array.isArray(slots)) throw new Error('Respuesta de horarios inválida');
                    // Abortar no garantiza que el servidor detenga una respuesta ya iniciada.
                    if (requestVersion !== version) return;

                    for (const [entryKey, entry] of cache) {
                        if (entry.expiresAt <= Date.now()) cache.delete(entryKey);
                    }
                    if (cache.size >= 20) cache.delete(cache.keys().next().value);
                    cache.set(key, { slots, expiresAt: Date.now() + ttl });
                    onSuccess(slots);
                } catch (error) {
                    if (requestVersion === version && error.name !== 'AbortError') onError(error);
                } finally {
                    if (requestVersion === version) {
                        pendingKey = null;
                        controller = null;
                    }
                }
            }, delay);
        }

        return { select, reset };
    };
})();
