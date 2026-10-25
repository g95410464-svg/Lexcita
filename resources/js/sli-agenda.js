import '../css/sli-agenda.css';

const message = 'No pudimos guardar el cambio. Inténtalo de nuevo; si persiste, contacta con g95410464@proton.me.';
async function request(url, method = 'GET', data) {
    let response;
    try { response = await fetch(url, {
        method, credentials: 'same-origin', headers: { Accept: 'application/json',
            'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        ...(data ? { body: JSON.stringify(data) } : {}),
    }); } catch (_) { throw new Error(message); }
    if (response.redirected) throw new Error('Tu sesión venció. Recarga la página e inicia sesión de nuevo.');
    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(response.status < 500 ? (Object.values(body.errors || {}).flat()[0] || body.message || message) : message);
        error.conflict = Boolean(body.errors?.acknowledge_conflicts);
        throw error;
    }
    return body;
}

export function mountAgenda(root) {
    const status = root.querySelector('[data-agenda-status]');
    const dialog = root.querySelector('[data-agenda-event-dialog]');
    const form = dialog.querySelector('form');
    const error = form.querySelector('[data-agenda-event-error]');
    const proposalDialog = root.querySelector('[data-agenda-proposal-dialog]');
    const proposalForm = proposalDialog.querySelector('form');
    const key = `sli.agenda.${root.dataset.userId}`;
    let calendar;
    let sendUrl;
    let choices = [];
    let busy = false;
    const rememberAndReload = () => {
        try { sessionStorage.setItem(key, JSON.stringify({ date: calendar.getDate().toISOString().slice(0, 10), view: calendar.view.type })); } catch (_) { /* Optional preference. */ }
        location.reload();
    };
    const open = (values = {}) => {
        form.reset(); error.textContent = '';
        form.querySelector('[data-agenda-conflict-warning]').hidden = true;
        for (const [name, value] of Object.entries(values)) {
            const field = form.elements.namedItem(name);
            if (field) { if (field.type === 'checkbox') field.checked = Boolean(value); else field.value = value ?? ''; }
        }
        form.querySelector('[data-agenda-dialog-title]').textContent = values.event_id ? 'Editar actividad' : 'Nueva actividad';
        form.querySelector('[data-agenda-repeat]').hidden = Boolean(values.event_id);
        form.querySelector('[data-agenda-delete]').hidden = !values.event_id;
        form.querySelector('[data-agenda-duplicate]').hidden = !values.event_id;
        if (!dialog.open) dialog.showModal();
    };
    root.querySelectorAll('[data-agenda-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    root.querySelector('[data-agenda-new]').addEventListener('click', () => open());
    form.querySelector('[data-agenda-duplicate]').addEventListener('click', () => {
        const data = Object.fromEntries(new FormData(form));
        data.event_id = ''; data.blocking = form.elements.blocking.checked;
        data.title = `${data.title} (copia)`; open(data);
    });
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (busy) return;
        const data = Object.fromEntries(new FormData(form));
        const id = data.event_id; delete data.event_id;
        data.blocking = form.elements.blocking.checked;
        data.acknowledge_conflicts = form.elements.acknowledge_conflicts.checked;
        if (id) delete data.repeat_weeks;
        busy = true; error.textContent = 'Guardando…';
        try { await request(root.dataset.eventsUrl + (id ? `/${id}` : ''), id ? 'PATCH' : 'POST', data); rememberAndReload(); }
        catch (failure) { error.textContent = failure.message; if (failure.conflict) form.querySelector('[data-agenda-conflict-warning]').hidden = false; }
        finally { busy = false; }
    });
    form.querySelector('[data-agenda-delete]').addEventListener('click', async () => {
        if (busy || !confirm('¿Retirar esta actividad? Las citas existentes conservarán su horario.')) return;
        busy = true;
        try { await request(`${root.dataset.eventsUrl}/${form.elements.event_id.value}`, 'DELETE'); rememberAndReload(); }
        catch (failure) { error.textContent = failure.message; } finally { busy = false; }
    });
    root.querySelectorAll('[data-agenda-propose]').forEach(button => button.addEventListener('click', async () => {
        sendUrl = button.dataset.sendUrl;
        const options = proposalForm.querySelector('[data-agenda-options]');
        const feedback = proposalForm.querySelector('[data-agenda-proposal-error]');
        options.replaceChildren(); feedback.textContent = 'Consultando horarios…'; proposalDialog.showModal();
        try {
            choices = await request(button.dataset.optionsUrl);
            feedback.textContent = choices.length ? '' : 'No encontramos alternativas compatibles. Revisa actividades, trayectos o la disponibilidad que envió el cliente.';
            choices.forEach((choice, index) => {
                const label = document.createElement('label'); label.className = 'sli-agenda-option';
                const input = document.createElement('input'); input.type = 'checkbox'; input.name = 'choice'; input.value = String(index);
                label.append(input, document.createTextNode(choice.label)); options.append(label);
            });
        } catch (failure) { feedback.textContent = failure.message; }
    }));
    proposalForm.addEventListener('submit', async event => {
        event.preventDefault(); if (busy) return;
        const feedback = proposalForm.querySelector('[data-agenda-proposal-error]');
        const selected = [...proposalForm.querySelectorAll('[name="choice"]:checked')].map(input => choices[Number(input.value)]);
        if (!selected.length || selected.length > 5) { feedback.textContent = 'Elige entre uno y cinco horarios.'; return; }
        busy = true; feedback.textContent = 'Enviando…';
        try { await request(sendUrl, 'POST', { options: selected.map(({ date, time }) => ({ date, time })) }); rememberAndReload(); }
        catch (failure) { feedback.textContent = failure.message; } finally { busy = false; }
    });
    const init = () => {
        if (!window.FullCalendar) { status.textContent = 'No pudimos cargar el calendario. Recarga la página.'; return; }
        let saved = {}; try { saved = JSON.parse(sessionStorage.getItem(key) || '{}'); } catch (_) { /* Optional preference. */ }
        calendar = new window.FullCalendar.Calendar(root.querySelector('#calendario'), {
            initialView: ['dayGridMonth', 'timeGridWeek', 'timeGridDay'].includes(saved.view) ? saved.view : (matchMedia('(max-width:650px)').matches ? 'timeGridDay' : 'timeGridWeek'),
            ...(saved.date ? { initialDate: saved.date } : {}), locale: 'es', timeZone: 'America/El_Salvador',
            firstDay: 1, height: 'auto', nowIndicator: true, selectable: true, allDaySlot: false,
            slotMinTime: '06:00:00', slotMaxTime: '22:00:00', eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
            buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día' },
            events: async (range, success, failure) => {
                try { const url = new URL(root.dataset.feedUrl, location.origin); url.searchParams.set('start', range.startStr); url.searchParams.set('end', range.endStr); success(await request(url)); }
                catch (err) { status.textContent = err.message; failure(err); }
            },
            select: range => open({ starts_at: range.startStr.slice(0, 16), ends_at: range.endStr.slice(0, 16) }),
            eventClick: ({ event }) => {
                const props = event.extendedProps;
                if (props.kind === 'activity') open({ ...props, title: event.title, starts_at: props.local_start, ends_at: props.local_end });
                else if (props.url_sala) location.assign(props.url_sala);
            },
        });
        calendar.render();
    };
    if (document.readyState === 'complete') init(); else window.addEventListener('load', init, { once: true });
}

export function mountAvailabilityForms() {
    document.querySelectorAll('[data-add-availability]').forEach(button => button.addEventListener('click', () => {
        const container = button.form.querySelector('[data-availability-windows]');
        const index = container.children.length; if (index >= 5) return;
        const row = container.firstElementChild.cloneNode(true);
        row.querySelectorAll('input').forEach(input => { input.value = ''; input.name = input.name.replace('[0]', `[${index}]`); });
        container.append(row); button.hidden = index === 4;
    }));
}
