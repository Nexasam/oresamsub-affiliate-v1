@extends('layouts.app')
@section('content')

<!-- Start::main-content -->
<div class="main-content">

  <!-- Page Header -->
  <div class="page-header mb-4">
    @if (Session::has('success'))
      <div class="bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 text-green-700 dark:text-green-300 p-3 rounded-md">
        ✅ {{ Session::get('success') }}
      </div>
    @endif

    @if (Session::has('failure'))
      <div class="bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300 p-3 rounded-md">
        ❌ {{ Session::get('failure') }}
      </div>
    @endif
  </div>
  <!-- Page Header Close -->

  <!-- Start::row -->
  <div class="grid grid-cols-12 gap-6">
    <div class="col-span-12">
      <div class="box dark:bg-gray-900 dark:border-gray-700">
        <div class="box-header flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
          <h5 class="box-title text-gray-800 dark:text-gray-100">🌍 Language Translations</h5>

          <div class="flex gap-2">
            <!-- Add New Translation Button (Triggers Modal) -->
            <button type="button" class="ti-btn ti-btn-success" data-hs-overlay="#add-translation-modal">
              + Add New Translation
            </button>

            <form action="{{ route('multilanguage.translation') }}" method="GET">
              @csrf
              <button type="submit" class="ti-btn ti-btn-warning">🔄 Synchronize Translations</button>
            </form>
          </div>
        </div>

        <div class="box-body dark:bg-gray-900 dark:text-gray-200">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            You can use ChatGPT to help generate translations.<br>
            <strong>Note:</strong> Ensure correct content is set in the Landing Page Settings first.
          </p>

          <!-- Tabs -->
          <nav class="flex space-x-2 mt-4" aria-label="Tabs" role="tablist">
            <button
              class="hs-tab-active:bg-primary hs-tab-active:text-white py-2 px-4 text-sm font-medium rounded-sm active bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200"
              id="tab-system" data-hs-tab="#tab-content-system" aria-controls="tab-content-system">
              System / Custom Translations
            </button>
            <button
              class="hs-tab-active:bg-primary hs-tab-active:text-white py-2 px-4 text-sm font-medium rounded-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200"
              id="tab-landing" data-hs-tab="#tab-content-landing" aria-controls="tab-content-landing">
              Landing Page Translations
            </button>
          </nav>

          <!-- System Translations -->
          <div id="tab-content-system" role="tabpanel" class="mt-3">
            <div class="overflow-auto rounded-lg">
              <table class="w-full border border-gray-200 dark:border-gray-700 border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                  <tr>
                    <th class="px-4 py-2 text-left">View / Update Translations [SYSTEM]</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                  @foreach ($language_lines as $lang)
                    @php 
                      $decoded_text = is_array($lang->text) ? $lang->text : json_decode($lang->text, true);
                    @endphp
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-800 transition duration-200">
                      <td class="p-3">
                        <form method="POST" action="{{ route('admin.translations.store') }}">
                          @csrf
                          <div class="grid grid-cols-5 gap-2">
                            <div>
                              <label class="text-xs text-gray-600 dark:text-gray-400">English</label>
                              <input type="text" name="add_or_update_translation[]" value="{{ $decoded_text['en'] ?? '' }}"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-md">
                            </div>
                            <div>
                              <label class="text-xs text-gray-600 dark:text-gray-400">Yoruba</label>
                              <input type="text" name="add_or_update_translation[]" value="{{ $decoded_text['yo'] ?? '' }}"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-md">
                            </div>
                            <div>
                              <label class="text-xs text-gray-600 dark:text-gray-400">Igbo</label>
                              <input type="text" name="add_or_update_translation[]" value="{{ $decoded_text['ig'] ?? '' }}"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-md">
                            </div>
                            <div>
                              <label class="text-xs text-gray-600 dark:text-gray-400">Hausa</label>
                              <input type="text" name="add_or_update_translation[]" value="{{ $decoded_text['ha'] ?? '' }}"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-md">
                            </div>
                            <div class="flex items-end">
                              <button type="submit" class="ti-btn ti-btn-primary w-full">Update</button>
                            </div>
                          </div>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <!-- Landing Page Translations -->
          <div id="tab-content-landing" class="hidden mt-3" role="tabpanel">
            <div class="overflow-auto rounded-lg">
              <table class="w-full border border-gray-200 dark:border-gray-700 border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                  <tr>
                    <th class="px-4 py-2 text-left">View / Update Translations [LANDING PAGE]</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                  @foreach ($landing_data as $key => $landing)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-800 transition duration-200">
                      <td class="p-3">
                        @php
                          $check_language = DB::table('language_lines')->where('key', $landing)->first();
                          $decoded_text = $check_language ? json_decode($check_language->text, true) : null;
                        @endphp
                        <form class="language_trans" method="POST" action="{{ route('admin.translations.store_ajax') }}">
                          @csrf
                          <input type="hidden" name="translation_key" value="{{ $key }}">
                          <div class="grid grid-cols-5 gap-2">
                            @foreach (['en' => 'English', 'yo' => 'Yoruba', 'ig' => 'Igbo', 'ha' => 'Hausa'] as $code => $label)
                              <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400">{{ $label }}</label>
                                <input type="text" name="add_or_update_translation[]" value="{{ $decoded_text[$code] ?? $landing }}"
                                  class="w-full px-2 py-1 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-md">
                              </div>
                            @endforeach
                            <div class="flex items-end">
                              <button type="submit"
                                class="ti-btn {{ $check_language ? 'ti-btn-warning' : 'ti-btn-primary' }} w-full submit-btn"
                                data-original-text="{{ $check_language ? 'Update Landing' : 'Create Translation' }}">
                                {{ $check_language ? 'Update Landing' : 'Create Translation' }}
                              </button>
                            </div>
                          </div>
                        </form>
                        <span class="responseMessage text-sm block mt-2"></span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

        </div><!-- /.box-body -->
      </div>
    </div>
  </div>
  <!-- End::row -->

</div>
<!-- End::main-content -->


<!-- ✅ Add Translation Modal -->
<div id="add-translation-modal" class="hs-overlay ti-modal hidden">
  <div class="ti-modal-box">
    <div class="ti-modal-content">
      <div class="ti-modal-header">
        <h3 class="ti-modal-title">➕ Add New Language Combination</h3>
        <button type="button" class="ti-modal-close-btn" data-hs-overlay="#add-translation-modal">✕</button>
      </div>
      <div class="ti-modal-body">
        <form method="POST" action="{{ route('admin.translations.store') }}">
          @csrf
          <div class="grid gap-4">
            <div>
              <label class="ti-form-label">English</label>
              <input type="text" name="add_or_update_translation[]" class="ti-form-input" placeholder="Enter English" required>
            </div>
            <div>
              <label class="ti-form-label">Yoruba</label>
              <input type="text" name="add_or_update_translation[]" class="ti-form-input" placeholder="Enter Yoruba">
            </div>
            <div>
              <label class="ti-form-label">Igbo</label>
              <input type="text" name="add_or_update_translation[]" class="ti-form-input" placeholder="Enter Igbo">
            </div>
            <div>
              <label class="ti-form-label">Hausa</label>
              <input type="text" name="add_or_update_translation[]" class="ti-form-input" placeholder="Enter Hausa">
            </div>
            <button type="submit" class="ti-btn ti-btn-primary w-full">Save Translation</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- ✅ End Modal -->


<!-- jQuery + AJAX Handler -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {
  $('.language_trans').on('submit', function (e) {
    e.preventDefault();
    let form = $(this);
    let responseSpan = form.next('.responseMessage');
    let submitBtn = form.find('.submit-btn');
    let originalText = submitBtn.attr('data-original-text');

    submitBtn.prop('disabled', true).text('Updating...');

    $.post(form.attr('action'), form.serialize())
      .done(response => {
        responseSpan.text(response.message).removeClass('text-red-600').addClass('text-green-600');
      })
      .fail(xhr => {
        let msg = xhr.responseJSON?.message || 'An error occurred.';
        responseSpan.text(msg).removeClass('text-green-600').addClass('text-red-600');
      })
      .always(() => submitBtn.prop('disabled', false).text(originalText));
  });
});
</script>

@endsection
