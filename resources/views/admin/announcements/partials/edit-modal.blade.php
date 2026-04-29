<!-- Edit Announcement Modal -->
<div id="edit-announcement-modal" class="hs-overlay hidden ti-modal">
    <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out">
        <div class="ti-modal-content">

            <!-- Modal Header -->
            <div class="ti-modal-header border-b border-gray-200 dark:border-white/10">
                <h6 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    Edit Announcement
                </h6>
                <button type="button"
                        class="hs-dropdown-toggle ti-modal-close-btn"
                        data-hs-overlay="#edit-announcement-modal">
                    <span class="sr-only">Close</span>
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div class="ti-modal-body p-4">
                <form id="edit-announcement-form"
                      action=""
                      method="POST"
                      class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Hidden ID -->
                    <input type="hidden" name="id" id="edit_id">

                    <!-- Position -->
                    <div>
                        <label for="edit_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Position
                        </label>
                        <select name="position" id="edit_position" class="ti-form-select w-full mt-1">
                            @for ($i = 1; $i <= 150; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                        
                    </div>

                    <!-- Title -->
                    <div>
                        <label for="edit_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Title
                        </label>
                        <input type="text" name="title" id="edit_title"
                               class="ti-form-input w-full mt-1"
                               required>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="edit_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Description
                        </label>
                        <textarea name="description" id="edit_description"
                                  rows="4"
                                  class="ti-form-textarea w-full mt-1"
                                  required></textarea>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="edit_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Status
                        </label>
                        <select name="status" id="edit_status" class="ti-form-select w-full mt-1">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="ti-modal-footer border-t border-gray-200 dark:border-white/10 flex justify-end gap-2 p-3">
                <button type="button"
                        class="ti-btn ti-btn-secondary"
                        data-hs-overlay="#edit-announcement-modal">
                    Cancel
                </button>
                <button type="submit"
                        form="edit-announcement-form"
                        class="ti-btn ti-btn-primary">
                    Update Announcement
                </button>
            </div>

        </div>
    </div>
</div>
