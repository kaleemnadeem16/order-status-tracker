<div id="status-modal" class="modal-backdrop hidden">
    <div class="modal-panel">
        <div class="mb-4 flex items-start justify-between">
            <h2 id="status-modal-title" class="text-base font-medium text-ink">Add Status</h2>
            <button type="button" data-action="status-modal-close" class="text-2xl leading-none text-neutral-400 hover:text-ink" aria-label="Close">&times;</button>
        </div>

        <div id="status-form-error" class="mb-4 hidden rounded-sm border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

        <div class="mb-4">
            <label class="label" for="status-name">Name <span class="text-red-500">*</span></label>
            <input class="input" type="text" id="status-name" maxlength="150">
        </div>

        <div class="mb-4">
            <label class="label" for="status-description">Description (optional)</label>
            <textarea class="input" id="status-description" rows="2"></textarea>
        </div>

        <div class="mb-5 grid grid-cols-2 gap-3">
            <div>
                <label class="label" for="status-duration-value">Expected Duration (optional)</label>
                <input class="input" type="number" step="0.5" min="0" id="status-duration-value">
            </div>
            <div>
                <label class="label" for="status-duration-unit">Unit</label>
                <select class="input" id="status-duration-unit">
                    <option value="days">Days</option>
                    <option value="weeks">Weeks</option>
                    <option value="months">Months</option>
                </select>
            </div>
        </div>
        <p class="-mt-4 mb-5 text-xs text-neutral-400">Leave duration blank if this step doesn't take extra time — it won't show a duration at all.</p>

        <div class="flex gap-2">
            <button type="button" class="btn-secondary flex-1" data-action="status-modal-close">Cancel</button>
            <button type="button" class="btn-primary flex-1" data-action="status-save">Save</button>
        </div>
    </div>
</div>
