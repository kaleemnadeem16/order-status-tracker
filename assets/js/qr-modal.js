// Link/QR modal — depends on app.js (apiFetch/toast/registerAction) and the
// vendored qrcode.min.js. Opened from any element carrying:
//   data-action="link-open" data-order-id data-order-number data-url
// or programmatically via window.showLinkModal(id, number, url, onClose).
(function () {
  'use strict';

  let currentOrderId = null;
  let currentOrderNumber = null;
  let onCloseCallback = null;
  let brandLogoPromise = null;

  function brandLogo() {
    if (!brandLogoPromise) {
      brandLogoPromise = new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error('Brand logo could not be loaded'));
        img.src = window.APP_URL + '/images/wordmark.svg';
      });
    }
    return brandLogoPromise;
  }

  function renderQr(url) {
    const container = document.getElementById('qr-code-container');
    container.innerHTML = '';
    new QRCode(container, {
      text: url,
      width: 200,
      height: 200,
      colorDark: '#000000',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H,
    });
  }

  function openLinkModal(orderId, orderNumber, url, { created = false } = {}) {
    currentOrderId = orderId;
    currentOrderNumber = orderNumber;
    document.getElementById('qr-order-number').textContent = 'Order #' + orderNumber;
    document.getElementById('qr-url-input').value = url;
    document.getElementById('qr-created-banner').classList.toggle('hidden', !created);
    document.getElementById('qr-share-btn').classList.toggle('hidden', !navigator.share);
    renderQr(url);
    showModal('qr-modal');
  }

  window.showLinkModal = function showLinkModal(orderId, orderNumber, url, onClose) {
    onCloseCallback = onClose || null;
    openLinkModal(orderId, orderNumber, url, { created: true });
  };

  function fallbackCopy(input, done) {
    input.removeAttribute('readonly');
    input.select();
    try {
      document.execCommand('copy');
      done();
    } catch (e) {
      toast('Could not copy automatically — select and copy manually', true);
    } finally {
      input.setAttribute('readonly', 'readonly');
    }
  }

  registerAction('link-open', (el) => {
    onCloseCallback = null;
    openLinkModal(parseInt(el.dataset.orderId, 10), el.dataset.orderNumber, el.dataset.url);
  });

  registerAction('qr-close', () => {
    hideModal('qr-modal');
    const cb = onCloseCallback;
    onCloseCallback = null;
    if (cb) cb();
  });

  registerAction('qr-copy', () => {
    const input = document.getElementById('qr-url-input');
    const finish = () => toast('Link copied to clipboard');
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(input.value).then(finish).catch(() => fallbackCopy(input, finish));
    } else {
      fallbackCopy(input, finish);
    }
  });

  function qrCanvas() {
    return document.querySelector('#qr-code-container canvas');
  }

  function drawContainedImage(ctx, img, x, y, maxWidth, maxHeight) {
    const scale = Math.min(maxWidth / img.naturalWidth, maxHeight / img.naturalHeight);
    const width = img.naturalWidth * scale;
    const height = img.naturalHeight * scale;
    ctx.drawImage(img, x + (maxWidth - width) / 2, y + (maxHeight - height) / 2, width, height);
  }

  async function brandedQrCanvas() {
    const source = qrCanvas();
    if (!source) throw new Error('QR code not ready yet');

    const logo = await brandLogo();
    const canvas = document.createElement('canvas');
    canvas.width = 900;
    canvas.height = 1120;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2;
    ctx.strokeRect(32, 32, canvas.width - 64, canvas.height - 64);

    drawContainedImage(ctx, logo, 250, 82, 400, 92);

    ctx.textAlign = 'center';
    ctx.fillStyle = '#666666';
    ctx.font = '500 18px monospace';
    ctx.fillText('PRIVATE ORDER TRACKING', 450, 220);
    ctx.fillStyle = '#000000';
    ctx.font = '400 38px Georgia, serif';
    ctx.fillText('Order #' + currentOrderNumber, 450, 270);

    const qrSize = 620;
    const qrX = (canvas.width - qrSize) / 2;
    const qrY = 320;
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(source, qrX, qrY, qrSize, qrSize);
    ctx.imageSmoothingEnabled = true;

    // A compact white-backed wordmark stays inside QR error correction limits.
    const markWidth = 164;
    const markHeight = 54;
    const markX = (canvas.width - markWidth) / 2;
    const markY = qrY + (qrSize - markHeight) / 2;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(markX, markY, markWidth, markHeight);
    drawContainedImage(ctx, logo, markX + 12, markY + 9, markWidth - 24, markHeight - 18);

    ctx.fillStyle = '#000000';
    ctx.font = '500 18px monospace';
    ctx.fillText('ORDER STATUS TRACKER', 450, 1008);
    ctx.fillStyle = '#666666';
    ctx.font = '400 15px monospace';
    ctx.fillText('tracker.example.com', 450, 1042);
    return canvas;
  }

  registerAction('qr-download', async () => {
    try {
      const canvas = await brandedQrCanvas();
      const link = document.createElement('a');
      link.download = 'order-' + currentOrderNumber + '-qr.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
    } catch (e) {
      toast(e.message || 'QR code not ready yet', true);
    }
  });

  registerAction('qr-share', async () => {
    const url = document.getElementById('qr-url-input').value;
    const shareData = {
      title: 'Order #' + currentOrderNumber + ' tracking',
      text: 'Track your order #' + currentOrderNumber + ':',
      url,
    };

    try {
      const canvas = await brandedQrCanvas();
      if (canvas && navigator.canShare) {
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
        const file = new File([blob], 'order-' + currentOrderNumber + '-qr.png', { type: 'image/png' });
        if (navigator.canShare({ files: [file] })) {
          await navigator.share(Object.assign({ files: [file] }, shareData));
          return;
        }
      }
      await navigator.share(shareData);
    } catch (e) {
      if (e.name !== 'AbortError') {
        toast('Could not open the share sheet — link copied instead', true);
        document.getElementById('qr-url-input').value = url;
        fallbackCopy(document.getElementById('qr-url-input'), () => {});
      }
    }
  });

  registerAction('qr-regenerate', async () => {
    if (!currentOrderId) return;
    if (!confirm('This invalidates the current link — anyone using the old link or QR code will no longer be able to view this order. Continue?')) {
      return;
    }
    try {
      const data = await apiFetch(window.APP_URL + '/api/link.php', {
        method: 'POST',
        body: { order_id: currentOrderId },
      });
      document.getElementById('qr-url-input').value = data.url;
      renderQr(data.url);

      document.querySelectorAll('[data-action="link-open"][data-order-id="' + currentOrderId + '"]').forEach((btn) => {
        btn.dataset.url = data.url;
      });
      toast('Link regenerated');
    } catch (e) {
      toast(e.message || 'Failed to regenerate link', true);
    }
  });
})();
