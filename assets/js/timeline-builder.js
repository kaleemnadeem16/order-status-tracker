// Order add/edit: the order's timeline always mirrors the Status Library's
// default sequence — nothing is "added" by the user here. Advanced Features
// lets staff set a date/message on any step (pencil) or insert a rare
// one-off exception step at a specific point (+ between steps). The default
// sequence itself only ever changes via the Status Library page.
(function () {
  'use strict';

  const UNITS = ['instant', 'days', 'weeks', 'months'];

  const state = {
    advancedEnabled: false,
    steps: [],
    statuses: [],
    mode: 'add',
    orderId: null,
    editingIndex: null,   // index of an existing step being edited via the modal
    insertAtIndex: null,  // array index a new custom step should be inserted at
  };

  // ---------------------------------------------------------------- helpers

  // Returns null when no duration is set — steps with no expected time show nothing.
  function durationLabel(value, unit) {
    if (unit === 'instant' || value === null || value === undefined || value === '' || Number(value) === 0) {
      return null;
    }
    const n = Number(value);
    return n + ' ' + unit.replace(/s$/, '') + (n === 1 ? '' : 's');
  }

  function formatDateShort(isoDate) {
    const d = new Date(isoDate + 'T00:00:00');
    if (isNaN(d.getTime())) return isoDate;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  // Timeline dates must stay in step order: a step's date can't fall before
  // an earlier step's date, or after a later step's. Steps with no date set
  // are simply skipped when checking. Returns an error string, or null.
  function findChronologyError(steps) {
    let lastDate = null;
    let lastName = null;
    for (const s of steps) {
      if (!s.actual_date) continue;
      if (lastDate && s.actual_date < lastDate) {
        return 'The date for "' + s.name + '" can\'t be before "' + lastName + '"\'s date (' + formatDateShort(lastDate) + ').';
      }
      lastDate = s.actual_date;
      lastName = s.name;
    }
    return null;
  }

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function pencilButton(index) {
    const btn = el('button', 'flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-neutral-300 bg-white text-neutral-500 hover:border-ink hover:text-ink');
    btn.type = 'button';
    btn.title = 'Edit this step';
    btn.setAttribute('aria-label', 'Edit step');
    btn.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">' +
      '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />' +
      '</svg>';
    btn.addEventListener('click', () => openEditModal(index));
    return btn;
  }

  function gapButton(insertIndex) {
    const btn = el('button', 'flex h-5 w-5 items-center justify-center rounded-full border border-dashed border-neutral-300 bg-neutral-50 text-neutral-400 hover:border-ink hover:bg-white hover:text-ink', '+');
    btn.type = 'button';
    btn.title = 'Insert a one-off step here';
    btn.setAttribute('aria-label', 'Insert step here');
    btn.style.fontSize = '13px';
    btn.style.lineHeight = '1';
    btn.addEventListener('click', () => openInsertModal(insertIndex));
    return btn;
  }

  function buildStepCircle(step, index) {
    const done = !!step.is_completed;
    return el(
      'div',
      done
        ? 'flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500 text-white text-sm font-medium shadow-sm'
        : 'flex h-9 w-9 items-center justify-center rounded-full border-2 border-neutral-300 bg-white',
      done ? '✓' : String(index + 1)
    );
  }

  function buildStepBody(step, index) {
    const body = el('div', 'mt-3 flex flex-col items-center gap-1.5');
    body.appendChild(el('p', 'snake-step-title text-sm font-medium text-ink', step.name));

    const bits = [];
    const duration = durationLabel(step.expected_duration_value, step.expected_duration_unit);
    if (duration) bits.push(duration);
    if (state.advancedEnabled) {
      if (step.custom_message) bits.push('Message set');
      if (step.status_id === null) bits.push('Custom');
    }
    if (bits.length) {
      body.appendChild(el('p', 'snake-step-meta text-xs text-neutral-400', bits.join(' · ')));
    }

    if (state.advancedEnabled) {
      // Staff-facing reminder: unlike the customer page, admins see exactly
      // which steps still need a date, and the date itself once one is set.
      const dateSet = !!step.actual_date;
      body.appendChild(el(
        'p',
        'snake-step-meta text-xs ' + (dateSet ? 'text-neutral-500' : 'text-neutral-400 italic'),
        dateSet ? 'Date: ' + formatDateShort(step.actual_date) : 'Date not set'
      ));
    }

    if (state.advancedEnabled) {
      body.appendChild(pencilButton(index));
    }
    return body;
  }

  async function handleDotClick(index) {
    const step = state.steps[index];
    if (!step.id) {
      if (state.mode === 'add') {
        state.steps.forEach((item, itemIndex) => { item.is_completed = itemIndex <= index; });
        renderSteps();
        toast('Initial status selected');
        return;
      }
      toast('Save the order first — then you can update its status from here.', true);
      return;
    }
    try {
      await apiFetch(window.APP_URL + '/api/order_statuses.php', {
        method: 'POST',
        body: { action: 'mark-current', order_id: state.orderId, order_status_id: step.id },
      });
      state.steps.forEach((s, i) => { s.is_completed = i <= index; });
      renderSteps();
      toast('Status updated');
    } catch (e) {
      toast(e.message || 'Failed to update status', true);
    }
  }

  // -------------------------------------------------------------- rendering

  function renderSteps() {
    document.getElementById('advanced-hint').classList.toggle('hidden', !state.advancedEnabled);

    const wrap = document.getElementById('steps-timeline');
    const saveBtn = document.querySelector('[data-action="order-save"]');

    if (state.steps.length === 0) {
      wrap.innerHTML = '';
      const empty = el('div', 'card p-8 text-center text-sm text-neutral-400');
      empty.innerHTML = 'No statuses in your Status Library yet. <a href="' + window.APP_URL + '/statuses.php" class="text-ink underline">Add some there first</a>.';
      wrap.appendChild(empty);
      saveBtn.disabled = true;
      return;
    }
    saveBtn.disabled = false;

    const items = state.steps.map((step, i) => ({
      done: !!step.is_completed,
      label: step.name,
      buildCircle: () => buildStepCircle(step, i),
      buildBody: () => buildStepBody(step, i),
      onDotClick: () => handleDotClick(i),
    }));

    const options = state.advancedEnabled ? { buildGap: (insertIndex) => gapButton(insertIndex) } : {};
    renderSnakeTimeline(wrap, items, options);
  }

  // ------------------------------------------------------------ step modal

  function openEditModal(index) {
    const step = state.steps[index];
    if (!step) return;
    const isDefault = step.status_id !== null;

    state.editingIndex = index;
    state.insertAtIndex = null;

    document.getElementById('step-modal-title').textContent = isDefault ? step.name : 'Edit: ' + step.name;
    document.getElementById('step-default-note').classList.toggle('hidden', !isDefault);
    document.getElementById('step-full-fields').classList.toggle('hidden', isDefault);

    document.getElementById('step-name').value = step.name || '';
    document.getElementById('step-description').value = step.description || '';
    document.getElementById('step-duration-value').value =
      step.expected_duration_value === null || step.expected_duration_value === undefined ? '' : step.expected_duration_value;
    document.getElementById('step-duration-unit').value =
      step.expected_duration_unit && step.expected_duration_unit !== 'instant' ? step.expected_duration_unit : 'days';
    document.getElementById('step-custom-message').value = step.custom_message || '';
    const dateInput = document.getElementById('step-actual-date');
    dateInput.min = document.getElementById('start_date').value || '';
    dateInput.value = step.actual_date || '';

    document.getElementById('step-remove-btn').classList.toggle('hidden', isDefault);
    document.getElementById('step-form-error').classList.add('hidden');
    showModal('step-modal');
  }

  function openInsertModal(atIndex) {
    state.editingIndex = null;
    state.insertAtIndex = atIndex;

    document.getElementById('step-modal-title').textContent = 'Add Custom Step';
    document.getElementById('step-default-note').classList.add('hidden');
    document.getElementById('step-full-fields').classList.remove('hidden');

    document.getElementById('step-name').value = '';
    document.getElementById('step-description').value = '';
    document.getElementById('step-duration-value').value = '';
    document.getElementById('step-duration-unit').value = 'days';
    document.getElementById('step-custom-message').value = '';
    const dateInput = document.getElementById('step-actual-date');
    dateInput.min = document.getElementById('start_date').value || '';
    dateInput.value = '';

    document.getElementById('step-remove-btn').classList.add('hidden');
    document.getElementById('step-form-error').classList.add('hidden');
    showModal('step-modal');
  }

  function closeStepModal() {
    hideModal('step-modal');
    state.editingIndex = null;
    state.insertAtIndex = null;
  }

  function stepModalError(message) {
    const box = document.getElementById('step-form-error');
    box.textContent = message;
    box.classList.remove('hidden');
  }

  function saveStepModal() {
    const customMessage = document.getElementById('step-custom-message').value.trim() || null;
    const actualDate = document.getElementById('step-actual-date').value || null;

    const orderStartDate = document.getElementById('start_date').value;
    if (actualDate && orderStartDate && actualDate < orderStartDate) {
      stepModalError("Date can't be before the order's start date.");
      return;
    }

    if (actualDate) {
      const preview = state.steps.slice();
      if (state.editingIndex !== null) {
        preview[state.editingIndex] = Object.assign({}, preview[state.editingIndex], { actual_date: actualDate });
      } else if (state.insertAtIndex !== null) {
        const insertedName = document.getElementById('step-name').value.trim() || 'This step';
        preview.splice(state.insertAtIndex, 0, { name: insertedName, actual_date: actualDate });
      }
      const chronoError = findChronologyError(preview);
      if (chronoError) {
        stepModalError(chronoError);
        return;
      }
    }

    const editingDefault = state.editingIndex !== null && state.steps[state.editingIndex].status_id !== null;
    if (editingDefault) {
      state.steps[state.editingIndex].custom_message = customMessage;
      state.steps[state.editingIndex].actual_date = actualDate;
      closeStepModal();
      renderSteps();
      toast('Step updated');
      return;
    }

    const name = document.getElementById('step-name').value.trim();
    if (!name) {
      stepModalError('Name is required.');
      return;
    }
    const rawValue = document.getElementById('step-duration-value').value;
    const unit = document.getElementById('step-duration-unit').value;
    const durationValue = rawValue === '' ? null : parseFloat(rawValue);
    const fields = {
      status_id: null,
      name,
      description: document.getElementById('step-description').value.trim() || null,
      expected_duration_value: durationValue,
      // No value means no duration at all, regardless of which unit was left selected.
      expected_duration_unit: durationValue === null ? 'instant' : (UNITS.includes(unit) ? unit : 'days'),
      custom_message: customMessage,
      actual_date: actualDate,
    };

    if (state.editingIndex !== null) {
      Object.assign(state.steps[state.editingIndex], fields);
      toast('Step updated');
    } else {
      state.steps.splice(state.insertAtIndex, 0, Object.assign({ id: null, is_completed: false }, fields));
      toast('Custom step added');
    }

    closeStepModal();
    renderSteps();
  }

  function removeStepFromModal() {
    if (state.editingIndex === null) return;
    if (!confirm('Remove this step from the timeline?')) return;
    state.steps.splice(state.editingIndex, 1);
    closeStepModal();
    renderSteps();
    toast('Step removed');
  }

  // ------------------------------------------------------------- save/delete

  function showError(message) {
    const errorBox = document.getElementById('form-error');
    errorBox.textContent = message;
    errorBox.classList.remove('hidden');
    errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  async function saveOrder() {
    document.getElementById('form-error').classList.add('hidden');

    const orderNumber = document.getElementById('order_number').value.trim();
    const startDate = document.getElementById('start_date').value;

    if (!orderNumber) return showError('Order number is required.');
    if (!startDate) return showError('Start date is required.');
    if (state.steps.length === 0) return showError('Your Status Library has no statuses — add some there first.');

    const payload = {
      order_number: orderNumber,
      start_date: startDate,
      advanced_enabled: state.advancedEnabled,
      steps: state.steps,
    };

    try {
      if (state.mode === 'edit') {
        await apiFetch(window.APP_URL + '/api/orders.php?id=' + state.orderId, { method: 'PUT', body: payload });
        window.location.href = window.APP_URL + '/dashboard.php';
      } else {
        const result = await apiFetch(window.APP_URL + '/api/orders.php', { method: 'POST', body: payload });
        state.mode = 'edit';
        state.orderId = result.id;
        state.steps = (result.steps || []).map((s) => ({
          id: s.id,
          status_id: s.status_id,
          name: s.name,
          description: s.description,
          expected_duration_value: s.expected_duration_value,
          expected_duration_unit: s.expected_duration_unit,
          custom_message: s.custom_message,
          actual_date: s.actual_date,
          is_completed: !!Number(s.is_completed),
        }));

        document.getElementById('order-form-title').textContent = 'Edit Order';
        document.getElementById('order-form-config').dataset.mode = 'edit';
        document.getElementById('order-form-config').dataset.orderId = String(result.id);

        const actions = document.getElementById('order-form-actions');
        const viewLink = document.getElementById('order-view-link');
        const linkButton = document.getElementById('order-link-button');
        const deleteButton = document.getElementById('order-delete-button');
        actions.classList.remove('hidden');
        viewLink.href = result.url;
        linkButton.dataset.orderId = String(result.id);
        linkButton.dataset.orderNumber = orderNumber;
        linkButton.dataset.url = result.url;
        deleteButton.dataset.orderId = String(result.id);

        window.history.replaceState({}, '', window.APP_URL + '/order-edit.php?id=' + result.id);
        renderSteps();
        showLinkModal(result.id, orderNumber, result.url);
      }
    } catch (e) {
      showError(e.message || 'Failed to save order.');
    }
  }

  async function deleteOrderAndRedirect(orderId) {
    if (!confirm('Delete this order? This cannot be undone.')) return;
    try {
      await apiFetch(window.APP_URL + '/api/orders.php?id=' + orderId, { method: 'DELETE' });
      window.location.href = window.APP_URL + '/dashboard.php';
    } catch (e) {
      toast(e.message || 'Failed to delete order', true);
    }
  }

  // ---------------------------------------------------------------- wiring

  registerAction('step-modal-close', closeStepModal);
  registerAction('step-modal-save', saveStepModal);
  registerAction('step-modal-remove', removeStepFromModal);
  registerAction('order-save', saveOrder);
  registerAction('order-delete-redirect', (elm) => deleteOrderAndRedirect(parseInt(elm.dataset.orderId, 10)));

  document.addEventListener('DOMContentLoaded', () => {
    const config = document.getElementById('order-form-config');
    state.mode = config.dataset.mode;
    state.orderId = config.dataset.orderId ? parseInt(config.dataset.orderId, 10) : null;

    state.statuses = JSON.parse(document.getElementById('statuses-data').textContent || '[]');

    const orderDataEl = document.getElementById('order-data');
    const orderData = orderDataEl ? JSON.parse(orderDataEl.textContent || 'null') : null;

    if (orderData) {
      // Editing an existing order: load its already-built timeline as-is.
      state.advancedEnabled = !!Number(orderData.advanced_enabled);
      state.steps = (orderData.steps || []).map((s) => ({
        id: s.id,
        status_id: s.status_id,
        name: s.name,
        description: s.description,
        expected_duration_value: s.expected_duration_value,
        expected_duration_unit: s.expected_duration_unit,
        custom_message: s.custom_message,
        actual_date: s.actual_date,
        is_completed: !!Number(s.is_completed),
      }));
    } else {
      // New order: the timeline always starts as the Status Library's full
      // default sequence — nothing is picked or added by hand.
      state.steps = state.statuses.map((s) => ({
        id: null,
        status_id: s.id,
        name: s.name,
        description: s.description,
        expected_duration_value: s.expected_duration_value,
        expected_duration_unit: s.expected_duration_unit,
        custom_message: null,
        actual_date: null,
        is_completed: false,
      }));
    }

    const advToggle = document.getElementById('advanced-toggle');
    advToggle.checked = state.advancedEnabled;
    advToggle.addEventListener('change', (e) => {
      state.advancedEnabled = e.target.checked;
      renderSteps();
    });

    renderSteps();
  });
})();
