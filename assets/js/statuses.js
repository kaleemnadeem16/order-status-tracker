// Status Library page: list + add/edit modal + delete + reorder.
(function () {
  'use strict';

  let statuses = [];
  let editingId = null;

  registerAction('save-default-duration', async () => {
    const value = document.getElementById('default-duration-input').value.trim();
    try {
      await apiFetch(window.APP_URL + '/api/settings.php', {
        method: 'PUT',
        body: { default_order_duration: value },
      });
      toast('Saved');
    } catch (e) {
      toast(e.message || 'Failed to save', true);
    }
  });

  // Returns null when no duration is set — steps with no expected time show nothing.
  function durationLabel(value, unit) {
    if (unit === 'instant' || value === null || value === undefined || value === '' || Number(value) === 0) {
      return null;
    }
    const n = Number(value);
    const unitLabel = unit.replace(/s$/, '');
    return n + ' ' + unitLabel + (n === 1 ? '' : 's');
  }

  function renderList() {
    const list = document.getElementById('status-list');
    list.innerHTML = '';

    if (statuses.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'card p-8 text-center text-sm text-neutral-400';
      empty.textContent = 'No statuses yet. Add your first one above.';
      list.appendChild(empty);
      return;
    }

    statuses.forEach((s, index) => {
      const row = document.createElement('div');
      row.className = 'card flex items-start gap-3 p-4';

      const controls = document.createElement('div');
      controls.className = 'flex flex-col gap-1 pt-0.5';
      const upBtn = mkIconBtn('↑', () => moveStatus(index, -1));
      upBtn.disabled = index === 0;
      const downBtn = mkIconBtn('↓', () => moveStatus(index, 1));
      downBtn.disabled = index === statuses.length - 1;
      controls.append(upBtn, downBtn);
      row.appendChild(controls);

      const main = document.createElement('div');
      main.className = 'min-w-0 flex-1';

      const nameEl = document.createElement('p');
      nameEl.className = 'font-medium text-ink';
      nameEl.textContent = s.name;
      main.appendChild(nameEl);

      const duration = durationLabel(s.expected_duration_value, s.expected_duration_unit);
      if (duration) {
        const meta = document.createElement('p');
        meta.className = 'mt-0.5 text-xs text-neutral-400';
        meta.textContent = duration;
        main.appendChild(meta);
      }

      if (s.description) {
        const desc = document.createElement('p');
        desc.className = 'mt-1.5 text-sm text-neutral-500';
        desc.textContent = s.description;
        main.appendChild(desc);
      }

      row.appendChild(main);

      const actions = document.createElement('div');
      actions.className = 'flex shrink-0 gap-2';
      const editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn-secondary btn-sm';
      editBtn.textContent = 'Edit';
      editBtn.addEventListener('click', () => openEditModal(s.id));
      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'btn-danger btn-sm';
      delBtn.textContent = 'Delete';
      delBtn.addEventListener('click', () => deleteStatus(s.id));
      actions.append(editBtn, delBtn);
      row.appendChild(actions);

      list.appendChild(row);
    });
  }

  function mkIconBtn(symbol, onClick) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'flex h-7 w-7 items-center justify-center rounded-sm border border-neutral-200 text-sm text-neutral-500 hover:border-ink hover:text-ink disabled:opacity-30';
    btn.textContent = symbol;
    btn.addEventListener('click', onClick);
    return btn;
  }

  registerAction('status-add-open', () => openAddModal());
  registerAction('status-modal-close', () => closeStatusModal());
  registerAction('status-save', () => saveStatus());

  function openAddModal() {
    editingId = null;
    document.getElementById('status-modal-title').textContent = 'Add Status';
    document.getElementById('status-name').value = '';
    document.getElementById('status-description').value = '';
    document.getElementById('status-duration-value').value = '';
    document.getElementById('status-duration-unit').value = 'instant';
    document.getElementById('status-form-error').classList.add('hidden');
    showModal('status-modal');
  }

  function openEditModal(id) {
    const s = statuses.find((x) => x.id === id);
    if (!s) return;
    editingId = id;
    document.getElementById('status-modal-title').textContent = 'Edit Status';
    document.getElementById('status-name').value = s.name;
    document.getElementById('status-description').value = s.description || '';
    document.getElementById('status-duration-value').value = s.expected_duration_value ?? '';
    document.getElementById('status-duration-unit').value = s.expected_duration_unit || 'instant';
    document.getElementById('status-form-error').classList.add('hidden');
    showModal('status-modal');
  }

  function closeStatusModal() {
    hideModal('status-modal');
  }

  async function saveStatus() {
    const errorBox = document.getElementById('status-form-error');
    errorBox.classList.add('hidden');

    const name = document.getElementById('status-name').value.trim();
    if (!name) {
      errorBox.textContent = 'Name is required.';
      errorBox.classList.remove('hidden');
      return;
    }

    const payload = {
      name,
      description: document.getElementById('status-description').value.trim(),
      expected_duration_value: document.getElementById('status-duration-value').value,
      expected_duration_unit: document.getElementById('status-duration-unit').value,
    };

    try {
      if (editingId) {
        await apiFetch(window.APP_URL + '/api/statuses.php?id=' + editingId, { method: 'PUT', body: payload });
      } else {
        await apiFetch(window.APP_URL + '/api/statuses.php', { method: 'POST', body: payload });
      }
      const data = await apiFetch(window.APP_URL + '/api/statuses.php');
      statuses = data.statuses;
      renderList();
      closeStatusModal();
      toast('Status saved');
    } catch (e) {
      errorBox.textContent = e.message || 'Failed to save status.';
      errorBox.classList.remove('hidden');
    }
  }

  async function deleteStatus(id) {
    if (!confirm('Delete this status from the library? Orders already using it keep their own copy and are unaffected.')) return;
    try {
      await apiFetch(window.APP_URL + '/api/statuses.php?id=' + id, { method: 'DELETE' });
      statuses = statuses.filter((s) => s.id !== id);
      renderList();
      toast('Status deleted');
    } catch (e) {
      toast(e.message || 'Failed to delete status', true);
    }
  }

  async function moveStatus(index, dir) {
    const target = index + dir;
    if (target < 0 || target >= statuses.length) return;
    const tmp = statuses[index];
    statuses[index] = statuses[target];
    statuses[target] = tmp;
    renderList();

    try {
      const data = await apiFetch(window.APP_URL + '/api/statuses.php', {
        method: 'POST',
        body: { action: 'reorder', ids: statuses.map((s) => s.id) },
      });
      statuses = data.statuses;
      renderList();
    } catch (e) {
      toast(e.message || 'Failed to reorder', true);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    statuses = JSON.parse(document.getElementById('statuses-data').textContent || '[]');
    renderList();
  });
})();
