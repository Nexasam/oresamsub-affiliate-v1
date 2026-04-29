@extends('layouts.app')
@section('content')
<div class="main-content">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <h3 class="text-xl font-semibold text-gray-700 dark:text-white">📢 Announcements</h3>
    </div>

    <!-- Announcements Section -->
    <div class="box">
        <div class="box-header flex items-center justify-between">
            <h5 class="box-title">Announcements for Customers</h5>
            <button class="ti-btn ti-btn-success" data-hs-overlay="#create-announcement-modal">
                + Add Announcement
            </button>
        </div>

        <!-- Create Modal -->
        <div id="create-announcement-modal" class="hs-overlay ti-modal hidden">
            <div class="ti-modal-box">
                <div class="ti-modal-content">
                    <div class="ti-modal-header">
                        <h3 class="ti-modal-title">Add Announcement</h3>
                        <button type="button" class="ti-modal-clode-btn" data-hs-overlay="#create-announcement-modal">✕</button>
                    </div>
                    <div class="ti-modal-body">
                        <form method="POST" action="{{ route('admin.announcements.store') }}">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="ti-form-label">Title</label>
                                    <input type="text" name="title" required class="ti-form-input" placeholder="Enter title">
                                </div>

                                <div>
                                    <label class="ti-form-label">Description</label>
                                    <textarea name="description" id="ckeditor-create" rows="6" class="editor ti-form-input dark:bg-gray-900"></textarea>
                                </div>

                                <div>
                                  <label class="ti-form-label">Position</label>
                                  <select name="position" class="ti-form-select">
                                      @for ($i = 1; $i <= 50; $i++)
                                          <option value="{{ $i }}">
                                              {{ $i }}
                                          </option>
                                      @endfor
                                  </select>
                              </div>
                              

                                <div>
                                    <label class="ti-form-label">Status</label>
                                    <select name="status" required class="ti-form-select">
                                        <option value="1">Active</option>
                                        <option value="0" selected>Inactive</option>
                                    </select>
                                </div>

                                <button type="submit" class="ti-btn ti-btn-primary w-full">Create</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Create Modal -->

        <!-- Table -->
        <div class="box-body overflow-x-auto">
            @if (Session::has('success'))
                <div class="alert bg-green-100 text-green-700">{{ Session::get('success') }}</div>
            @endif
            @if (Session::has('failure'))
                <div class="alert bg-red-100 text-red-700">{{ Session::get('failure') }}</div>
            @endif

            <table class="w-full border border-gray-200 dark:border-gray-700 border-collapse rounded-lg overflow-hidden">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                  <tr>
                    <th class="px-3 py-2 text-left">ID</th>
                    <th class="px-3 py-2 text-left">Position</th>
                    <th class="px-3 py-2 text-left">Title</th>
                    <th class="px-3 py-2 text-left">Description</th>
                    <th class="px-3 py-2 text-left">Status</th>
                    <th class="px-3 py-2 text-left">Date Added</th>
                  </tr>
                </thead>
              
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                  @foreach ($announcements as $index => $ann)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-200">
                      <td class="px-3 py-2">{{ $index + 1 }}</td>
                      <td class="px-3 py-2">{{ $ann->position }}</td>
                      <td class="px-3 py-2 font-medium">
                        {{ $ann->title }}
                        <br>
                        <button class="hs-dropdown-toggle mt-1 ti-btn ti-btn-primary text-xs" data-hs-overlay="#edit-announcement-{{ $ann->id }}">
                          EDIT
                        </button>
              
                        <!-- Edit Modal -->
                        <div id="edit-announcement-{{ $ann->id }}" class="hs-overlay ti-modal hidden">
                          <div class="ti-modal-box dark:bg-gray-900 dark:text-gray-200">
                            <div class="ti-modal-content">
                              <div class="ti-modal-header border-b dark:border-white/10">
                                <h3 class="ti-modal-title text-base font-semibold">Edit {{ $ann->title }}</h3>
                                <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#edit-announcement-{{ $ann->id }}">
                                  <svg class="w-4 h-4" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                      d="M0.258206 1.00652C0.351976 0.912791 0.479126 0.860131 0.611706 0.860131C0.744296 0.860131 0.871447 0.912791 0.965207 1.00652L3.61171 3.65302L6.25822 1.00652C6.30432 0.958771 6.35952 0.920671 6.42052 0.894471C6.48152 0.868271 6.54712 0.854471 6.61352 0.853901C6.67992 0.853321 6.74572 0.865971 6.80722 0.891111C6.86862 0.916251 6.92442 0.953381 6.97142 1.00032C7.01832 1.04727 7.05552 1.1031 7.08062 1.16454C7.10572 1.22599 7.11842 1.29183 7.11782 1.35822C7.11722 1.42461 7.10342 1.49022 7.07722 1.55122C7.05102 1.61222 7.01292 1.6674 6.96522 1.71352L4.31871 4.36002L6.96522 7.00648C7.05632 7.10078 7.10672 7.22708 7.10552 7.35818C7.10442 7.48928 7.05182 7.61468 6.95912 7.70738C6.86642 7.80018 6.74102 7.85268 6.60992 7.85388C6.47882 7.85498 6.35252 7.80458 6.25822 7.71348L3.61171 5.06702L0.965207 7.71348C0.870907 7.80458 0.744606 7.85498 0.613506 7.85388C0.482406 7.85268 0.357007 7.80018 0.264297 7.70738C0.171597 7.61468 0.119017 7.48928 0.117877 7.35818C0.116737 7.22708 0.167126 7.10078 0.258206 7.00648L2.90471 4.36002L0.258206 1.71352C0.164476 1.61976 0.111816 1.4926 0.111816 1.36002C0.111816 1.22744 0.164476 1.10028 0.258206 1.00652Z"
                                      fill="currentColor" />
                                  </svg>
                                </button>
                              </div>
              
                              <div class="ti-modal-body">
                                <form method="POST" action="{{ route('admin.announcements.update', ['id' => $ann->id]) }}">
                                  @csrf
                                  <div class="grid gap-4">
                                    <div>
                                      <label class="ti-form-label mb-0">Title</label>
                                      <input value="{{ $ann->title }}" name="title" type="text" required
                                        class="ti-form-input w-full dark:!bg-gray-800 dark:!border-gray-700 dark:text-gray-100"
                                        placeholder="Title">
                                    </div>
              
                                    <div>
                                      <label class="ti-form-label mb-0">Description</label>
                                      <textarea name="description" rows="5"
                                        class="ti-form-input w-full dark:!bg-gray-800 dark:!border-gray-700 dark:text-gray-100">{!! $ann->description !!}</textarea>
                                    </div>
              
                                    <div>
                                      <label class="ti-form-label mb-0">Position</label>
                                      <input value="{{ $ann->position }}" name="position" type="number"
                                        class="ti-form-input w-full dark:!bg-gray-800 dark:!border-gray-700 dark:text-gray-100"
                                        placeholder="Position">
                                    </div>
              
                                    <div>
                                      <label class="ti-form-label mb-0">Status</label>
                                      <select name="status" required
                                        class="ti-form-select w-full dark:!bg-gray-800 dark:!border-gray-700 dark:text-gray-100">
                                        <option selected value="{{ $ann->status }}">{{ $ann->status == 1 ? 'Active' : 'Inactive' }}</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                      </select>
                                    </div>
              
                                    <button type="submit" class="ti-btn ti-btn-primary w-full">Update Announcement</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </div>
                      </td>
              
                      <td class="px-3 py-2">{!! $ann->description !!}</td>
                      <td class="px-3 py-2">
                        @if ($ann->status == 1)
                          <span class="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 px-2 py-1 rounded-full text-xs font-semibold">ACTIVE</span>
                        @else
                          <span class="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 px-2 py-1 rounded-full text-xs font-semibold">INACTIVE</span>
                        @endif
                      </td>
                      <td class="px-3 py-2">{{ $ann->created_at->format('d M, Y') }}</td>
                    </tr>
                  @endforeach
                </tbody>
            </table>
              
        </div>
    </div>

</div>
@endsection

