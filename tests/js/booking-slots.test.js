import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setImmediate } from 'node:timers/promises';
import vm from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../public/js/booking-slots.js', import.meta.url), 'utf8');
const ok = (hora) => ({ ok: true, json: async () => [{ hora, hora_label: hora }] });

function setup(t, fetchImpl) {
    t.mock.timers.enable({ apis: ['setTimeout', 'Date'], now: 1000 });
    const requests = [];
    const shown = [];
    const errors = [];
    const window = {};
    vm.runInNewContext(source, {
        window, Date, setTimeout, clearTimeout, AbortController, URLSearchParams,
        fetch: (...args) => { requests.push(args); return fetchImpl(...args); },
    });
    const loader = window.createSlotLoader({
        url: '/api/slots', onLoading() {},
        onSuccess: (slots) => shown.push(slots[0]?.hora),
        onError: (error) => errors.push(error),
    });
    return { loader, requests, shown, errors };
}

test('clics rápidos solo consultan la última fecha', async (t) => {
    const { loader, requests, shown } = setup(t, async () => ok('10:00'));
    loader.select(1, '2026-10-01');
    loader.select(1, '2026-10-02');
    loader.select(1, '2026-10-03');
    assert.equal(requests.length, 0);
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(requests.length, 1);
    assert.match(requests[0][0], /fecha=2026-10-03/);
    assert.deepEqual(shown, ['10:00']);
});

test('doble clic durante una consulta no duplica solicitudes', async (t) => {
    let resolve;
    const { loader, requests } = setup(t, () => new Promise((done) => { resolve = done; }));
    loader.select(1, '2026-10-01');
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    assert.equal(requests.length, 1);
    resolve(ok('10:00'));
    await setImmediate();
});

test('caché breve separa abogados y vuelve a consultar tras caducar', async (t) => {
    const { loader, requests } = setup(t, async () => ok('10:00'));
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    await setImmediate();
    loader.select(1, '2026-10-01');
    assert.equal(requests.length, 1);
    loader.select(2, '2026-10-01');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(requests.length, 2);
    t.mock.timers.tick(15000);
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(requests.length, 3);
});

test('una respuesta antigua no sustituye los horarios del abogado nuevo', async (t) => {
    const resolves = [];
    const { loader, requests, shown, errors } = setup(t, () => new Promise((resolve) => resolves.push(resolve)));
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    loader.select(2, '2026-10-01');
    assert.equal(requests[0][1].signal.aborted, true);
    t.mock.timers.tick(200);
    resolves[1](ok('11:00'));
    await setImmediate();
    resolves[0](ok('09:00'));
    await setImmediate();
    assert.deepEqual(shown, ['11:00']);
    assert.equal(errors.length, 0);
});

test('un error HTTP permite reintentar y no se guarda en caché', async (t) => {
    let fail = true;
    const { loader, requests, shown, errors } = setup(t, async () => fail ? { ok: false, status: 500 } : ok('10:00'));
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(errors.length, 1);
    fail = false;
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(requests.length, 2);
    assert.deepEqual(shown, ['10:00']);
});

test('429 respeta Retry-After aunque cambien fecha y abogado', async (t) => {
    let limited = true;
    const { loader, requests, shown, errors } = setup(t, async () => limited
        ? { ok: false, status: 429, headers: { get: () => '10' } } : ok('10:00'));
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(errors[0].retryAfter, 10);
    loader.reset();
    loader.select(2, '2026-10-02');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(requests.length, 1);
    assert.equal(errors[1].status, 429);
    limited = false;
    t.mock.timers.tick(10000);
    loader.select(2, '2026-10-02');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(requests.length, 2);
    assert.deepEqual(shown, ['10:00']);
});

test('429 sin cabecera aplica espera de 60 segundos', async (t) => {
    const { loader, requests, errors } = setup(t, async () => ({ ok: false, status: 429 }));
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(errors[0].retryAfter, 60);
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(requests.length, 1);
});

test('cambiar abogado antes del envío cancela la consulta pendiente', async (t) => {
    const { loader, requests } = setup(t, async () => ok('10:00'));
    loader.select(1, '2026-10-01');
    loader.reset();
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(requests.length, 0);
});

test('una respuesta inválida no se presenta como disponibilidad vacía', async (t) => {
    const { loader, shown, errors } = setup(t, async () => ({ ok: true, json: async () => ({ message: 'error' }) }));
    loader.select(1, '2026-10-01');
    t.mock.timers.tick(200);
    await setImmediate();
    assert.equal(shown.length, 0);
    assert.equal(errors.length, 1);
});
