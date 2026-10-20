<div id="qr-modal" class="modal-backdrop hidden">
    <div class="modal-panel">
        <div class="mb-4 flex items-start justify-between">
            <h2 class="text-base font-medium text-ink">Tracking link</h2>
            <button type="button" data-action="qr-close" class="text-2xl leading-none text-neutral-400 hover:text-ink" aria-label="Close">&times;</button>
        </div>

        <div id="qr-created-banner" class="mb-4 hidden rounded-sm border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            Order created — here's the link and QR code to share with your customer.
        </div>

        <div id="qr-brand-card" class="mb-5 border border-black/10 bg-white px-5 py-6 text-center">
            <img src="<?= APP_URL ?>/images/wordmark.svg" alt="<?= htmlspecialchars(APP_NAME) ?>" class="mx-auto h-auto w-40">
            <p class="mt-4 font-mono text-[8px] uppercase tracking-[0.28em] text-neutral-500">Private order tracking</p>
            <p id="qr-order-number" class="mt-2 font-serif text-xl font-light text-ink"></p>

            <div class="mt-5 flex justify-center">
                <div class="relative border border-black/10 bg-white p-3">
                    <div id="qr-code-container"></div>
                    <div class="pointer-events-none absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center bg-white px-1.5 py-1">
                        <img src="<?= APP_URL ?>/images/wordmark.svg" alt="" class="h-auto w-14">
                    </div>
                </div>
            </div>

            <p class="mt-5 font-mono text-[9px] uppercase tracking-[0.2em] text-black">Order Status Tracker</p>
            <p class="mt-1 font-mono text-[8px] tracking-[0.12em] text-neutral-500">tracker.example.com</p>
        </div>

        <button type="button" id="qr-share-btn" class="btn-primary mb-3 hidden w-full" data-action="qr-share">Share Link &amp; QR Code</button>

        <div class="mb-5">
            <label class="label">Public link</label>
            <div class="flex gap-2">
                <input id="qr-url-input" type="text" class="input" readonly>
                <button type="button" class="btn-secondary btn-sm shrink-0" data-action="qr-copy">Copy</button>
            </div>
            <p class="mt-2 text-xs text-neutral-400">Anyone with this link can view this order's timeline. It is not listed publicly or indexed by search engines.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
            <button type="button" class="btn-secondary flex-1" data-action="qr-download">Download QR (PNG)</button>
            <button type="button" class="btn-danger flex-1" data-action="qr-regenerate">Regenerate Link</button>
        </div>
    </div>
</div>
