<div id="user-modal" class="modal-backdrop hidden">
    <div class="modal-panel">
        <div class="mb-4 flex items-start justify-between">
            <h2 id="user-modal-title" class="text-base font-medium text-ink">Add User</h2>
            <button type="button" data-action="user-modal-close" class="text-2xl leading-none text-neutral-400 hover:text-ink" aria-label="Close">&times;</button>
        </div>

        <div id="user-form-error" class="mb-4 hidden rounded-sm border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

        <div class="mb-4">
            <label class="label" for="user-name">Name</label>
            <input class="input" type="text" id="user-name" maxlength="100">
        </div>

        <div class="mb-4">
            <label class="label" for="user-email">Email</label>
            <input class="input" type="email" id="user-email">
        </div>

        <div id="user-password-field" class="mb-4">
            <label class="label" for="user-password">Password</label>
            <input class="input" type="password" id="user-password" autocomplete="new-password">
            <p class="mt-1 text-xs text-neutral-400">At least 10 characters.</p>
        </div>

        <div class="mb-5">
            <label class="label" for="user-role">Role</label>
            <select class="input" id="user-role">
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="button" class="btn-secondary flex-1" data-action="user-modal-close">Cancel</button>
            <button type="button" class="btn-primary flex-1" data-action="user-save">Save</button>
        </div>
    </div>
</div>

<div id="reset-password-modal" class="modal-backdrop hidden">
    <div class="modal-panel">
        <div class="mb-4 flex items-start justify-between">
            <h2 class="text-base font-medium text-ink">Reset Password</h2>
            <button type="button" data-action="user-reset-close" class="text-2xl leading-none text-neutral-400 hover:text-ink" aria-label="Close">&times;</button>
        </div>

        <div id="reset-password-error" class="mb-4 hidden rounded-sm border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

        <div class="mb-5">
            <label class="label" for="reset-password-value">New Password</label>
            <input class="input" type="password" id="reset-password-value" autocomplete="new-password">
            <p class="mt-1 text-xs text-neutral-400">At least 10 characters.</p>
        </div>

        <div class="flex gap-2">
            <button type="button" class="btn-secondary flex-1" data-action="user-reset-close">Cancel</button>
            <button type="button" class="btn-primary flex-1" data-action="user-reset-submit">Set Password</button>
        </div>
    </div>
</div>
