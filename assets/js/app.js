// Shared helpers: authenticated JSON fetch wrapper, toasts, mobile nav toggle.
(function () {
  'use strict';

  window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
  window.APP_URL = document.querySelector('meta[name="app-url"]')?.content || '';

  // Shared modal visibility + mobile-safe background scroll locking. A Set
  // allows one modal to replace another without unlocking the page early.
  const visibleModals = new Set();
  let lockedScrollY = 0;

  function lockPageScroll() {
    if (visibleModals.size !== 1) return;
    lockedScrollY = window.scrollY || window.pageYOffset || 0;
    document.body.style.position = 'fixed';
    document.body.style.top = '-' + lockedScrollY + 'px';
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.body.style.width = '100%';
    document.body.style.overflow = 'hidden';
  }

  function unlockPageScroll() {
    if (visibleModals.size !== 0) return;
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.body.style.width = '';
    document.body.style.overflow = '';
    window.scrollTo(0, lockedScrollY);
  }

  window.showModal = function showModal(modalOrId) {
    const modal = typeof modalOrId === 'string' ? document.getElementById(modalOrId) : modalOrId;
    if (!modal) return;
    if (!visibleModals.has(modal)) {
      visibleModals.add(modal);
      lockPageScroll();
    }
    modal.classList.remove('hidden');
  };

  window.hideModal = function hideModal(modalOrId) {
    const modal = typeof modalOrId === 'string' ? document.getElementById(modalOrId) : modalOrId;
    if (!modal) return;
    modal.classList.add('hidden');
    visibleModals.delete(modal);
    unlockPageScroll();
  };

  /**
   * fetch() wrapper that sends JSON, attaches the CSRF header, and throws an
   * Error (with .status and .data) on non-2xx responses.
   */
  window.apiFetch = async function apiFetch(url, options = {}) {
    const opts = Object.assign({ headers: {} }, options);
    opts.headers = Object.assign(
      {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CSRF_TOKEN,
      },
      options.headers || {}
    );
    if (opts.body && typeof opts.body !== 'string') {
      opts.body = JSON.stringify(opts.body);
    }

    const res = await fetch(url, opts);
    let data = null;
    try {
      data = await res.json();
    } catch (e) {
      data = null;
    }
    if (!res.ok) {
      const err = new Error((data && data.error) || 'Request failed');
      err.status = res.status;
      err.data = data;
      throw err;
    }
    return data;
  };

  window.toast = function toast(message, isError = false) {
    // Toasts stack in a fixed column so rapid successive messages never overlap.
    let stack = document.getElementById('toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.id = 'toast-stack';
      stack.className = 'fixed bottom-4 left-1/2 z-50 flex -translate-x-1/2 flex-col items-center gap-2';
      document.body.appendChild(stack);
    }

    const el = document.createElement('div');
    el.className = 'rounded-sm px-4 py-3 text-sm text-white shadow-lg ' + (isError ? 'bg-red-700' : 'bg-ink');
    el.textContent = message;
    stack.appendChild(el);

    setTimeout(() => {
      el.style.transition = 'opacity .3s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 300);
    }, 2600);
  };

  /**
   * Delegated action dispatch. Markup declares `data-action="name"` instead of
   * an inline onclick, so the app runs under a strict CSP (script-src 'self')
   * with no 'unsafe-inline'. Handlers register themselves via registerAction().
   */
  const actions = Object.create(null);

  window.registerAction = function registerAction(name, fn) {
    actions[name] = fn;
  };

  document.addEventListener('click', (event) => {
    const el = event.target.closest('[data-action]');
    if (!el) return;
    const fn = actions[el.dataset.action];
    if (!fn) {
      console.warn('No handler registered for action:', el.dataset.action);
      return;
    }
    event.preventDefault();
    fn(el, event);
  });

  const navToggle = document.getElementById('nav-toggle');
  const navMenu = document.getElementById('nav-menu');
  if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
      navMenu.classList.toggle('hidden');
    });
  }
})();
