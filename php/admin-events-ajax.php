<?php
declare(strict_types=1);

// Admin: event CRUD without page reloads, using the JSON API in api/events.php and the Fetch API.
require_once __DIR__ . '/auth.php';
requireRole('admin');

$script = <<<'JS'
    const apiUrl = 'api/events.php';
    const csrfToken = __CSRF__;
    const form = document.getElementById('apiEventForm');
    const message = document.getElementById('apiMessage');
    const rows = document.getElementById('apiEventRows');
    const search = document.getElementById('apiSearch');
    const fields = {
      event_id: document.getElementById('apiEventId'),
      title: document.getElementById('apiTitle'),
      category: document.getElementById('apiCategory'),
      event_date: document.getElementById('apiDate'),
      event_time: document.getElementById('apiTime'),
      venue: document.getElementById('apiVenue'),
      description: document.getElementById('apiDescription')
    };
    let debounceTimer;

    function showMessage(text, type) {
      message.textContent = text;
      message.className = 'message-box' + (type ? ' ' + type : '');
    }

    async function requestApi(method, payload) {
      const options = { method, headers: { 'Accept': 'application/json' } };
      if (payload) {
        options.headers['Content-Type'] = 'application/json';
        options.headers['X-CSRF-Token'] = csrfToken;
        options.body = JSON.stringify(payload);
      }
      const query = method === 'GET' && search.value.trim() ? '?q=' + encodeURIComponent(search.value.trim()) : '';
      const response = await fetch(apiUrl + query, options);
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) throw new Error(result.message || 'Request failed.');
      return result;
    }

    function cell(text) {
      const td = document.createElement('td');
      td.textContent = text ?? '';
      return td;
    }

    function renderEvents(events) {
      rows.replaceChildren();
      if (!events.length) {
        const tr = document.createElement('tr');
        const td = cell('No events found.');
        td.colSpan = 6;
        tr.append(td);
        rows.append(tr);
        return;
      }
      events.forEach((event) => {
        const tr = document.createElement('tr');
        tr.append(cell(event.event_id), cell(event.title), cell(event.category), cell(event.event_date), cell(event.venue));

        const edit = document.createElement('button');
        edit.type = 'button';
        edit.className = 'btn btn-small btn-outline';
        edit.textContent = 'Edit';
        edit.addEventListener('click', () => startEdit(event));

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-small';
        remove.textContent = 'Delete';
        remove.addEventListener('click', async () => {
          if (!confirm('Delete this event? Related registrations will also be deleted.')) return;
          try {
            showMessage('Deleting event…');
            const result = await requestApi('DELETE', { event_id: Number(event.event_id) });
            showMessage(result.message, 'success');
            await loadEvents();
          } catch (error) {
            showMessage(error.message, 'error');
          }
        });

        const wrap = document.createElement('div');
        wrap.className = 'action-links';
        wrap.append(edit, remove);
        const actions = cell('');
        actions.append(wrap);
        tr.append(actions);
        rows.append(tr);
      });
    }

    async function loadEvents() {
      rows.innerHTML = '<tr><td colspan="6">Loading events…</td></tr>';
      try {
        const result = await requestApi('GET');
        renderEvents(result.events);
        showMessage('Events loaded. Changes are saved without reloading the page.');
      } catch (error) {
        showMessage(error.message, 'error');
        rows.replaceChildren();
      }
    }

    function startEdit(event) {
      Object.keys(fields).forEach((key) => { fields[key].value = event[key] ?? ''; });
      document.getElementById('apiFormTitle').textContent = 'Update event';
      document.getElementById('apiSaveButton').textContent = 'Save changes';
      document.getElementById('apiCancelButton').hidden = false;
      fields.title.focus();
    }

    function resetForm() {
      form.reset();
      fields.event_id.value = '';
      document.getElementById('apiFormTitle').textContent = 'Add event';
      document.getElementById('apiSaveButton').textContent = 'Add event';
      document.getElementById('apiCancelButton').hidden = true;
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const payload = {
        title: fields.title.value.trim(),
        category: fields.category.value.trim(),
        event_date: fields.event_date.value,
        event_time: fields.event_time.value.trim(),
        venue: fields.venue.value.trim(),
        description: fields.description.value.trim()
      };
      const editing = fields.event_id.value !== '';
      if (editing) payload.event_id = Number(fields.event_id.value);
      const saveButton = document.getElementById('apiSaveButton');
      try {
        showMessage('Saving event…');
        saveButton.disabled = true;
        const result = await requestApi(editing ? 'PUT' : 'POST', payload);
        showMessage(result.message, 'success');
        resetForm();
        await loadEvents();
      } catch (error) {
        showMessage(error.message, 'error');
      } finally {
        saveButton.disabled = false;
      }
    });
    document.getElementById('apiCancelButton').addEventListener('click', resetForm);
    search.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(loadEvents, 300);
    });
    loadEvents();
JS;
$script = str_replace('__CSRF__', json_encode(csrfToken()), $script);

sh_head(['title' => 'Live Events']);
sh_dashboard_open('admin', 'events-live');
?>
        <div class="dashboard-title">
          <div>
            <div class="kicker">Admin / Fetch API</div>
            <h1>Live Events</h1>
          </div>
          <span class="muted">No page reloads</span>
        </div>

        <div id="apiMessage" class="message-box" role="status" aria-live="polite">Loading events…</div>

        <div class="api-layout">
          <form id="apiEventForm" class="form-card admin-form">
            <h2 id="apiFormTitle">Add event</h2>
            <input type="hidden" id="apiEventId" value="">
            <div class="form-group"><label for="apiTitle">Event title</label><input id="apiTitle" maxlength="150" required></div>
            <div class="form-group"><label for="apiCategory">Category</label><input id="apiCategory" maxlength="50" placeholder="e.g. Technical" required></div>
            <div class="form-group"><label for="apiDate">Event date</label><input id="apiDate" type="date" required></div>
            <div class="form-group"><label for="apiTime">Event time</label><input id="apiTime" maxlength="30" placeholder="e.g. 10:00 AM" required></div>
            <div class="form-group"><label for="apiVenue">Venue</label><input id="apiVenue" maxlength="150" required></div>
            <div class="form-group"><label for="apiDescription">Description</label><textarea id="apiDescription" rows="3" required></textarea></div>
            <button class="btn" id="apiSaveButton" type="submit">Add event</button>
            <button class="btn btn-outline" id="apiCancelButton" type="button" hidden>Cancel edit</button>
          </form>

          <section class="api-list">
            <div class="toolbar single"><label class="search-box"><span class="sr-only">Search events</span><input id="apiSearch" type="search" placeholder="Search title, category or venue"></label></div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>ID</th><th>Event</th><th>Category</th><th>Date</th><th>Venue</th><th>Actions</th></tr></thead>
                <tbody id="apiEventRows"><tr><td colspan="6">Loading…</td></tr></tbody>
              </table>
            </div>
          </section>
        </div>
<?php
sh_dashboard_close();
sh_foot($script);
