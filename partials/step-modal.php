<div id="step-modal" class="modal-backdrop hidden">
    <div class="modal-panel">
        <div class="mb-4 flex items-start justify-between">
            <h2 id="step-modal-title" class="text-base font-medium text-ink">Edit Step</h2>
            <button type="button" data-action="step-modal-close" class="text-2xl leading-none text-neutral-400 hover:text-ink" aria-label="Close">&times;</button>
        </div>

        <div id="step-form-error" class="mb-4 hidden rounded-sm border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

        <p id="step-default-note" class="mb-4 hidden rounded-sm border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs text-neutral-500">
            This is a default status from your Status Library. To rename it or change its default timing for every order, edit it in Status Library — here you can set a date or message just for this order.
        </p>

        <!-- Full fields: only for custom (one-off) steps. -->
        <div id="step-full-fields">
            <div class="mb-4">
                <label class="label" for="step-name">Name <span class="text-red-500">*</span></label>
                <input class="input" type="text" id="step-name" maxlength="150">
            </div>
            <div class="mb-4 grid grid-cols-2 gap-3">
                <div>
                    <label class="label" for="step-duration-value">Expected Duration</label>
                    <input class="input" type="number" step="0.5" min="0" id="step-duration-value">
                </div>
                <div>
                    <label class="label" for="step-duration-unit">Unit</label>
                    <select class="input" id="step-duration-unit">
                        <option value="days">Days</option>
                        <option value="weeks">Weeks</option>
                        <option value="months">Months</option>
                    </select>
                </div>
            </div>
            <p class="-mt-3 mb-4 text-xs text-neutral-400">Leave duration blank if this step doesn't take extra time.</p>
            <div class="mb-4">
                <label class="label" for="step-description">Description</label>
                <textarea class="input" id="step-description" rows="2"></textarea>
            </div>
        </div>

        <div class="mb-4">
            <label class="label" for="step-custom-message">Custom Message</label>
            <input class="input" type="text" id="step-custom-message" maxlength="255"
                   placeholder="Shown to the customer on this step">
        </div>
        <div class="mb-4">
            <label class="label" for="step-actual-date">Date</label>
            <input class="input" type="date" id="step-actual-date">
            <p class="mt-1 text-xs text-neutral-400">Optional reminder, not required. Once this date arrives, the step (and everything before it) is automatically marked complete for the customer. A past date completes it right away.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
            <button type="button" class="btn-secondary flex-1" data-action="step-modal-close">Cancel</button>
            <button type="button" class="btn-primary flex-1" data-action="step-modal-save">Save Step</button>
        </div>
        <button type="button" id="step-remove-btn" class="btn-danger mt-2 w-full" data-action="step-modal-remove">Remove from timeline</button>
    </div>
</div>
