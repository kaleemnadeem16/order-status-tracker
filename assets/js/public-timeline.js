// Public tracking page: renders the read-only snake timeline from the JSON
// blob t.php embeds. Falls back to the <noscript> list if this never runs.
document.addEventListener('DOMContentLoaded', () => {
  const dataEl = document.getElementById('timeline-data');
  const container = document.getElementById('timeline-container');
  if (!dataEl || !container) return;

  const steps = JSON.parse(dataEl.textContent || '[]');
  if (steps.length === 0) return;

  function buildCircle(step, index) {
    const c = document.createElement('div');
    c.className = step.done
      ? 'flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500 text-white text-sm font-medium shadow-sm'
      : 'flex h-9 w-9 items-center justify-center rounded-full border-2 border-neutral-300 bg-white';
    c.textContent = step.done ? '✓' : String(index + 1);
    return c;
  }

  function line(className, text) {
    const p = document.createElement('p');
    p.className = className;
    p.textContent = text;
    return p;
  }

  function buildBody(step) {
    const body = document.createElement('div');
    body.className = 'mt-3 flex flex-col items-center gap-1';

    body.appendChild(line('snake-step-title text-sm font-medium ' + (step.done ? 'text-ink' : 'text-neutral-500'), step.name));
    if (step.duration) {
      body.appendChild(line('snake-step-meta text-xs text-neutral-400', step.duration));
    }
    if (step.dateLine) {
      body.appendChild(line('snake-step-meta text-xs text-neutral-700', step.dateLine));
    }
    if (step.description) {
      body.appendChild(line('snake-step-description mt-0.5 text-sm text-neutral-500', step.description));
    }
    if (step.message) {
      const msg = document.createElement('p');
      msg.className = 'snake-step-message mt-1 inline-block border border-neutral-200 bg-neutral-50 px-2 py-1 text-sm text-ink';
      msg.textContent = step.message;
      body.appendChild(msg);
    }
    return body;
  }

  const items = steps.map((step, index) => ({
    done: step.done,
    label: step.name,
    buildCircle: () => buildCircle(step, index),
    buildBody: () => buildBody(step),
  }));

  renderSnakeTimeline(container, items, {});
});
