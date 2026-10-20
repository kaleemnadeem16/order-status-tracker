registerAction('account-change-password', async () => {
  const errorBox = document.getElementById('account-error');
  const successBox = document.getElementById('account-success');
  errorBox.classList.add('hidden');
  successBox.classList.add('hidden');

  const current = document.getElementById('current-password').value;
  const next = document.getElementById('new-password').value;

  try {
    await apiFetch(window.APP_URL + '/api/users.php', {
      method: 'POST',
      body: { action: 'change-password', current_password: current, new_password: next },
    });
    document.getElementById('current-password').value = '';
    document.getElementById('new-password').value = '';
    successBox.textContent = 'Password updated.';
    successBox.classList.remove('hidden');
  } catch (e) {
    errorBox.textContent = e.message || 'Failed to update password.';
    errorBox.classList.remove('hidden');
  }
});
