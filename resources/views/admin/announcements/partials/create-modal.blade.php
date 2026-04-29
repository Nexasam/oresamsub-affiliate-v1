<!-- Create Announcement Modal -->
<div id="create-announcement-modal" class="hs-overlay hidden ti-modal">
    <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out">
        <div class="ti-modal-content">

            <!-- Modal Header -->
            <div class="ti-modal-header border-b border-gray-200 dark:border-white/10">
                <h6 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    Add New Announcement
                </h6>
                <button type="button"
                        class="hs-dropdown-toggle ti-modal-close-btn"
                        data-hs-overlay="#create-announcement-modal">
                    <span class="sr-only">Close</span>
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div class="ti-modal-body p-4">
                <form action="{{ route('admin.announcements.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Position -->
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
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
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Title
                        </label>
                        <input type="text" name="title" id="title" placeholder="Enter announcement title"
                               class="ti-form-input w-full mt-1" required>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Description
                        </label>
                        <textarea name="description" id="description" rows="4" placeholder="Enter announcement details"
                                  class="ti-form-textarea w-full mt-1" required></textarea>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Status
                        </label>
                        <select name="status" id="status" class="ti-form-select w-full mt-1">
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
                        data-hs-overlay="#create-announcement-modal">
                    Cancel
                </button>
                <button type="submit"
                        form="create-announcement-form"
                        class="ti-btn ti-btn-success">
                    Save Announcement
                </button>
            </div>

        </div>
    </div>
</div>
