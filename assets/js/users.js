// Users page (admin only): list + add/edit modal + activate/deactivate + reset password.
(function () {
  'use strict';

  let users = [];
  let editingId = null;
  let resetTargetId = null;
  let currentUserId = null;

  registerAction('user-add-open', () => openAddModal());
  registerAction('user-modal-close', () => closeUserModal());
  registerAction('user-save', () => saveUser());
  registerAction('user-reset-close', () => closeResetPasswordModal());
  registerAction('user-reset-submit', () => submitResetPassword());

  function fmtDate(iso) {
    if (!iso) return 'Never';
    const d = new Date(iso.replace(' ', 'T'));
    if (isNaN(d.getTime())) return 'Never';
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function renderList() {
    const list = document.getElementById('user-list');
    list.innerHTML = '';

    users.forEach((u) => {
      const row = document.createElement('div');
      row.className = 'card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between';

      const main = document.createElement('div');
      main.className = 'min-w-0';

      const nameLine = document.createElement('div');
      nameLine.className = 'flex flex-wrap items-center gap-2';

      const nameEl = document.createElement('span');
      nameEl.className = 'font-medium text-ink';
      nameEl.textContent = u.name;
      nameLine.appendChild(nameEl);

      const roleBadge = document.createElement('span');
      roleBadge.className = u.role === 'admin' ? 'badge-admin' : 'badge-pending';
      roleBadge.textContent = u.role === 'admin' ? 'Admin' : 'Staff';
      nameLine.appendChild(roleBadge);

      if (!Number(u.is_active)) {
        const inactiveBadge = document.createElement('span');
        inactiveBadge.className = 'badge bg-red-50 text-red-600 ring-1 ring-inset ring-red-200';
        inactiveBadge.textContent = 'Deactivated';
        nameLine.appendChild(inactiveBadge);
      }

      main.appendChild(nameLine);

      const emailEl = document.createElement('p');
      emailEl.className = 'mt-0.5 text-sm text-neutral-500';
      emailEl.textContent = u.email;
      main.appendChild(emailEl);

      const metaEl = document.createElement('p');
      metaEl.className = 'mt-0.5 text-xs text-neutral-400';
      metaEl.textContent = 'Last login: ' + fmtDate(u.last_login_at);
      main.appendChild(metaEl);

      row.appendChild(main);

      const actions = document.createElement('div');
      actions.className = 'flex flex-wrap gap-2';

      const editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn-secondary btn-sm';
      editBtn.textContent = 'Edit';
      editBtn.addEventListener('click', () => openEditModal(u.id));
      actions.appendChild(editBtn);

      const resetBtn = document.createElement('button');
      resetBtn.type = 'button';
      resetBtn.className = 'btn-secondary btn-sm';
      resetBtn.textContent = 'Reset Password';
      resetBtn.addEventListener('click', () => openResetPasswordModal(u.id));
      actions.appendChild(resetBtn);

      const toggleBtn = document.createElement('button');
      toggleBtn.type = 'button';
      const isSelf = u.id === currentUserId;
      toggleBtn.className = Number(u.is_active) ? 'btn-danger btn-sm' : 'btn-secondary btn-sm';
      toggleBtn.textContent = Number(u.is_active) ? 'Deactivate' : 'Activate';
      toggleBtn.disabled = isSelf;
      toggleBtn.title = isSelf ? 'You cannot deactivate your own account' : '';
      toggleBtn.addEventListener('click', () => toggleActive(u.id, !Number(u.is_active)));
      actions.appendChild(toggleBtn);

      row.appendChild(actions);
      list.appendChild(row);
    });
  }

  function openAddModal() {
    editingId = null;
    document.getElementById('user-modal-title').textContent = 'Add User';
    document.getElementById('user-name').value = '';
    document.getElementById('user-email').value = '';
    document.getElementById('user-password').value = '';
    document.getElementById('user-password-field').classList.remove('hidden');
    document.getElementById('user-role').value = 'staff';
    document.getElementById('user-form-error').classList.add('hidden');
    showModal('user-modal');
  };

  function openEditModal(id) {
    const u = users.find((x) => x.id === id);
    if (!u) return;
    editingId = id;
    document.getElementById('user-modal-title').textContent = 'Edit User';
    document.getElementById('user-name').value = u.name;
    document.getElementById('user-email').value = u.email;
    document.getElementById('user-password-field').classList.add('hidden');
    document.getElementById('user-role').value = u.role;
    document.getElementById('user-form-error').classList.add('hidden');
    showModal('user-modal');
  };

  function closeUserModal() {
    hideModal('user-modal');
  };

  async function saveUser() {
    const errorBox = document.getElementById('user-form-error');
    errorBox.classList.add('hidden');

    const payload = {
      name: document.getElementById('user-name').value.trim(),
      email: document.getElementById('user-email').value.trim(),
      role: document.getElementById('user-role').value,
    };
    if (!editingId) {
      payload.password = document.getElementById('user-password').value;
    }

    try {
      if (editingId) {
        await apiFetch(window.APP_URL + '/api/users.php?id=' + editingId, { method: 'PUT', body: payload });
      } else {
        await apiFetch(window.APP_URL + '/api/users.php', { method: 'POST', body: payload });
      }
      const data = await apiFetch(window.APP_URL + '/api/users.php');
      users = data.users;
      renderList();
      closeUserModal();
      toast('User saved');
    } catch (e) {
      errorBox.textContent = e.message || 'Failed to save user.';
      errorBox.classList.remove('hidden');
    }
  };

  async function toggleActive(id, active) {
    if (!confirm(active ? 'Reactivate this user?' : 'Deactivate this user? They will no longer be able to log in.')) return;
    try {
      await apiFetch(window.APP_URL + '/api/users.php?id=' + id, { method: 'PUT', body: { action: 'set-active', is_active: active } });
      const data = await apiFetch(window.APP_URL + '/api/users.php');
      users = data.users;
      renderList();
      toast(active ? 'User activated' : 'User deactivated');
    } catch (e) {
      toast(e.message || 'Failed to update user', true);
    }
  }

  function openResetPasswordModal(id) {
    resetTargetId = id;
    document.getElementById('reset-password-value').value = '';
    document.getElementById('reset-password-error').classList.add('hidden');
    showModal('reset-password-modal');
  };

  function closeResetPasswordModal() {
    hideModal('reset-password-modal');
  };

  async function submitResetPassword() {
    const errorBox = document.getElementById('reset-password-error');
    errorBox.classList.add('hidden');
    const value = document.getElementById('reset-password-value').value;

    try {
      await apiFetch(window.APP_URL + '/api/users.php?id=' + resetTargetId, {
        method: 'PUT',
        body: { action: 'reset-password', new_password: value },
      });
      closeResetPasswordModal();
      toast('Password updated');
    } catch (e) {
      errorBox.textContent = e.message || 'Failed to reset password.';
      errorBox.classList.remove('hidden');
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    currentUserId = parseInt(document.getElementById('users-config').dataset.currentUserId, 10);
    users = JSON.parse(document.getElementById('users-data').textContent || '[]');
    renderList();
  });
})();
