// Dashboard: quick status update + delete, both in-place (no full page reload).
(function () {
  'use strict';

  registerAction('order-quick-update', (el) => quickUpdate(parseInt(el.dataset.orderId, 10)));
  registerAction('order-delete', (el) => deleteOrder(parseInt(el.dataset.orderId, 10), el));

  async function quickUpdate(orderId) {
    const select = document.getElementById('quick-select-' + orderId);
    if (!select || !select.value) return;

    try {
      const data = await apiFetch(window.APP_URL + '/api/order_statuses.php', {
        method: 'POST',
        body: {
          action: 'mark-current',
          order_id: orderId,
          order_status_id: parseInt(select.value, 10),
        },
      });
      refreshOrderCard(orderId, data.steps);
      toast('Status updated');
    } catch (e) {
      toast(e.message || 'Failed to update status', true);
    }
  }

  function refreshOrderCard(orderId, steps) {
    const completed = steps.filter((s) => s.is_completed == 1).length;
    const total = steps.length;

    let current = null;
    steps.forEach((s) => {
      if (s.is_completed == 1) {
        current = s;
      }
    });

    const badge = document.getElementById('status-badge-' + orderId);
    if (badge) {
      badge.textContent = current ? current.name : 'Not started';
      badge.className = completed > 0 ? 'badge-done' : 'badge-pending';
    }

    const progress = document.getElementById('progress-text-' + orderId);
    if (progress) {
      progress.textContent = completed + '/' + total + ' steps';
    }

    const select = document.getElementById('quick-select-' + orderId);
    if (select) {
      select.innerHTML = '';
      steps.forEach((s) => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name + (s.is_completed == 1 ? ' ✓' : '');
        if (current && s.id === current.id) {
          opt.selected = true;
        }
        select.appendChild(opt);
      });
    }
  }

  async function deleteOrder(orderId, btn) {
    if (!confirm('Delete this order? This cannot be undone.')) return;

    try {
      await apiFetch(window.APP_URL + '/api/orders.php?id=' + orderId, { method: 'DELETE' });
      const card = btn.closest('[data-order-card]');
      if (card) card.remove();
      toast('Order deleted');
    } catch (e) {
      toast(e.message || 'Failed to delete order', true);
    }
  }
})();
