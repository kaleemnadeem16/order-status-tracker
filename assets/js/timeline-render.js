// Shared responsive order timeline used by both the admin editor and the
// public tracking page. Desktop uses three columns and phones use two; both
// snake left/right with row turns in an outer gutter, clear of all copy.
(function () {
  'use strict';

  const SVG_NS = 'http://www.w3.org/2000/svg';
  const registry = new Map();
  let renderId = 0;
  let resizeTimer = null;

  function svgElement(tag, className) {
    const node = document.createElementNS(SVG_NS, tag);
    if (className) node.setAttribute('class', className);
    return node;
  }

  function pathBetween(from, to, turnX) {
    if (turnX === null) {
      return 'M ' + from.x + ' ' + from.y + ' L ' + to.x + ' ' + to.y;
    }

    const direction = turnX > from.x ? 1 : -1;
    const radius = Math.max(8, Math.min(18, Math.abs(turnX - from.x) / 2, Math.abs(to.y - from.y) / 3));
    const beforeTurn = turnX - direction * radius;

    return [
      'M', from.x, from.y,
      'H', beforeTurn,
      'Q', turnX, from.y, turnX, from.y + radius,
      'V', to.y - radius,
      'Q', turnX, to.y, beforeTurn, to.y,
      'H', to.x,
    ].join(' ');
  }

  function addConnector(svg, d, state) {
    const base = svgElement('path', 'snake-connector snake-connector--base');
    base.setAttribute('d', d);
    svg.appendChild(base);

    if (state !== 'pending') {
      const progress = svgElement(
        'path',
        'snake-connector snake-connector--progress' + (state === 'active' ? ' snake-connector--active' : '')
      );
      progress.setAttribute('d', d);
      svg.appendChild(progress);
    }
  }

  function draw(container, items, options, preserveViewport) {
    const thisRender = ++renderId;
    const anchorTop = preserveViewport && container.firstElementChild
      ? container.getBoundingClientRect().top
      : null;
    container.innerHTML = '';
    container.classList.add('snake-timeline');
    if (items.length === 0) return;

    const containerWidth = container.clientWidth || 280;
    const mobile = containerWidth < (options.compactAt || 560);
    const minColumnWidth = options.itemWidth || 166;
    const gutter = options.turnGutter || (mobile ? 22 : 38);
    const maxColumns = options.columns || 3;
    const cols = mobile
      ? Math.min(2, items.length)
      : Math.max(2, Math.min(maxColumns, Math.floor((containerWidth - gutter * 2) / minColumnWidth)));

    const lastDone = items.reduce((latest, item, index) => item.done ? index : latest, -1);
    const wrap = document.createElement('div');
    wrap.className = 'snake-timeline-wrap ' + (mobile ? 'snake-timeline-wrap--mobile' : 'snake-timeline-wrap--desktop');
    wrap.style.setProperty('--snake-columns', String(cols));
    wrap.style.setProperty('--snake-gutter', gutter + 'px');

    const svg = svgElement('svg', 'snake-connectors');
    svg.setAttribute('aria-hidden', 'true');
    wrap.appendChild(svg);

    const meta = document.createElement('div');
    meta.className = 'snake-timeline-meta';
    const metaLabel = document.createElement('span');
    metaLabel.textContent = 'Order progress';
    const metaCount = document.createElement('span');
    const completedCount = items.filter((item) => item.done).length;
    metaCount.textContent = String(completedCount).padStart(2, '0') + ' — ' + String(items.length).padStart(2, '0');
    meta.appendChild(metaLabel);
    meta.appendChild(metaCount);
    wrap.appendChild(meta);

    const grid = document.createElement('div');
    grid.className = 'snake-grid';
    wrap.appendChild(grid);
    container.appendChild(wrap);

    const refs = items.map((item, index) => {
      const rowIndex = Math.floor(index / cols);
      const localIndex = index % cols;
      const col = rowIndex % 2 === 1 ? cols - 1 - localIndex : localIndex;

      const cell = document.createElement('div');
      const isCurrent = index === lastDone;
      cell.className = 'snake-step ' + (
        isCurrent ? 'snake-step--current' : (item.done ? 'snake-step--done' : 'snake-step--pending')
      );
      cell.style.gridColumn = String(col + 1);
      cell.style.gridRow = String(rowIndex + 1);

      const circle = item.buildCircle();
      circle.classList.add('snake-step-marker');
      if (item.done) circle.classList.add('snake-step-marker--done');
      if (index === lastDone) circle.classList.add('snake-step-marker--current');
      if (!item.done) circle.classList.add('snake-step-marker--pending');
      circle.setAttribute('aria-current', index === lastDone ? 'step' : 'false');

      if (item.onDotClick) {
        circle.classList.add('snake-step-marker--interactive');
        circle.setAttribute('role', 'button');
        circle.setAttribute('tabindex', '0');
        if (item.label) {
          circle.setAttribute('aria-label', 'Set current status to ' + item.label);
          circle.setAttribute('title', 'Set current status to ' + item.label);
        }
        circle.addEventListener('click', item.onDotClick);
        circle.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            item.onDotClick();
          }
        });
      }

      const body = item.buildBody();
      body.classList.add('snake-step-copy');
      cell.appendChild(circle);
      cell.appendChild(body);
      grid.appendChild(cell);
      return { circle, rowIndex };
    });

    requestAnimationFrame(() => {
      if (thisRender !== renderId && !document.body.contains(wrap)) return;

      const wrapRect = wrap.getBoundingClientRect();
      const centers = refs.map(({ circle }) => {
        const rect = circle.getBoundingClientRect();
        return {
          x: rect.left + rect.width / 2 - wrapRect.left,
          y: rect.top + rect.height / 2 - wrapRect.top,
        };
      });

      const width = Math.max(wrap.scrollWidth, Math.ceil(wrapRect.width));
      const height = Math.max(wrap.scrollHeight, Math.ceil(wrapRect.height));
      svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
      svg.setAttribute('width', String(width));
      svg.setAttribute('height', String(height));

      for (let index = 0; index < centers.length - 1; index++) {
        const changesRow = refs[index].rowIndex !== refs[index + 1].rowIndex;
        let turnX = null;
        if (changesRow) {
          turnX = centers[index].x > width / 2 ? width - 18 : 18;
        }

        const d = pathBetween(centers[index], centers[index + 1], turnX);
        const connectorState = items[index].done
          ? (items[index + 1].done ? 'completed' : 'active')
          : 'pending';
        addConnector(svg, d, connectorState);

        if (options.buildGap) {
          const gap = options.buildGap(index + 1);
          if (gap) {
            gap.classList.add('snake-gap');
            const gapX = turnX === null ? (centers[index].x + centers[index + 1].x) / 2 : turnX;
            const gapY = (centers[index].y + centers[index + 1].y) / 2;
            gap.style.left = gapX + 'px';
            gap.style.top = gapY + 'px';
            wrap.appendChild(gap);
          }
        }
      }

      if (anchorTop !== null) {
        const topDelta = container.getBoundingClientRect().top - anchorTop;
        if (Math.abs(topDelta) > 0.5) {
          window.scrollBy(0, topDelta);
        }
      }
    });
  }

  function redrawAll() {
    registry.forEach((args, container) => {
      if (!document.body.contains(container)) {
        registry.delete(container);
        return;
      }

      // Mobile address bars change viewport height while scrolling. Only a
      // genuine width change (rotation/window resize) needs new geometry.
      const nextWidth = Math.round(container.clientWidth);
      if (Math.abs(nextWidth - args.width) < 2) return;
      args.width = nextWidth;
      draw(container, args.items, args.options, true);
    });
  }

  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(redrawAll, 120);
  });

  window.renderSnakeTimeline = function renderSnakeTimeline(container, items, options) {
    const settings = options || {};
    const alreadyRendered = container.firstElementChild !== null;
    registry.set(container, { items, options: settings, width: Math.round(container.clientWidth) });
    draw(container, items, settings, alreadyRendered);
  };
})();
