@if(auth()->check() && auth()->user()->hasRole(['super-admin', 'admin']))
    {{-- TAILWIND MODAL IMPORT SAP EVALUATION DATA --}}
    <div id="importSapModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity" role="dialog" aria-modal="true" aria-labelledby="importSapModalTitle">
        
        {{-- BACKDROP CLICK LISTENER --}}
        <div class="fixed inset-0" onclick="closeImportModal()"></div>

        {{-- MODAL CONTAINER --}}
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all z-10">
            
            {{-- HEADER --}}
            <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                        <i class="bx bx-upload text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800" id="importSapModalTitle">Update Evaluation Model</h3>
                        <p class="text-xs text-slate-500 font-medium">SAP Data Import & Full Replacement</p>
                    </div>
                </div>
                <button type="button" onclick="closeImportModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors">
                    <i class="bx bx-x text-2xl"></i>
                </button>
            </div>

            {{-- FORM --}}
            <form action="{{ route('purchasing.evaluationsupplier.import') }}" method="POST" enctype="multipart/form-data" id="import-sap-form">
                @csrf

                <div class="p-6 space-y-5">
                    {{-- WARNING BANNER --}}
                    <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200/80 flex items-start gap-3">
                        <i class="bx bx-error-circle text-amber-600 text-lg flex-shrink-0 mt-0.5"></i>
                        <div class="text-xs text-amber-900 leading-relaxed">
                            <span class="font-bold">Replace Strategy:</span> Uploading will replace existing records in the selected table with data from the new SAP export file.
                        </div>
                    </div>

                    {{-- MODEL SELECTOR --}}
                    <div>
                        <label for="import_model_select" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Select Evaluation Model / Table <span class="text-rose-500">*</span>
                        </label>
                        <select name="model" id="import_model_select" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" required>
                            <option value="" disabled selected>-- Choose Model to Update --</option>
                            <option value="po_list" data-file="(EVALUASI) LIST GRPO.xls">Master PO / GRPO List (purchasing_list_po)</option>
                            <option value="kriteria1" data-file="(EVALUASI) VENDOR CLAIM.xls">Kriteria 1 - Vendor Claim (purchasing_vendor_claim)</option>
                            <option value="kriteria2" data-file="(EVALUASI) VENDOR ACCURACY GOOD.xls">Kriteria 2 - Accuracy Good (purchasing_vendor_accuracy_good)</option>
                            <option value="kriteria3" data-file="(EVALUASI) VENDOR ONTIME DELIVERY.xls">Kriteria 3 - Ontime Delivery (purchasing_vendor_ontime_delivery)</option>
                            <option value="kriteria4" data-file="(EVALUASI) VENDOR URGENT REQUEST.xls">Kriteria 4 - Urgent Request (purchasing_vendor_urgent_request)</option>
                            <option value="kriteria5" data-file="(EVALUASI) VENDOR CLAIM RESPON.xls">Kriteria 5 - Claim Response (purchasing_vendor_claim_response)</option>
                            <option value="kriteria6" data-file="(EVALUASI) LIST VENDOR.xls">Kriteria 6 - Vendor Certificate (purchasing_vendor_list_certificate)</option>
                            <option value="purchasing_contact" data-file="(EVALUASI) PURCHASING CONTACT.xls">Purchasing Contacts & PIC (purchasing_contacts)</option>
                        </select>
                        <div class="mt-1.5 text-xs text-slate-500 flex items-center gap-1.5">
                            <span>Expected File:</span>
                            <span class="font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md" id="expected_file_name">-</span>
                        </div>
                    </div>

                    {{-- FILE INPUT --}}
                    <div>
                        <label for="import_file_input" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            SAP Export File (.xls / .txt / .csv) <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" name="file" id="import_file_input" accept=".xls,.xlsx,.txt,.csv" required
                            class="w-full px-3 py-2 text-xs text-slate-600 rounded-xl border border-slate-200 file:mr-3 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer bg-slate-50/50">
                        <p class="mt-1 text-[11px] text-slate-400">
                            Supports direct SAP Unicode UTF-16 tab-delimited exports.
                        </p>
                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeImportModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100 transition-all">
                        Cancel
                    </button>
                    <button type="submit" id="btn-submit-import" class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/20 transition-all">
                        <i class="bx bx-check text-base"></i>
                        <span id="btn-submit-text">Upload & Replace Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modelSelect = document.getElementById('import_model_select');
                const expectedFileName = document.getElementById('expected_file_name');
                const form = document.getElementById('import-sap-form');
                const submitBtn = document.getElementById('btn-submit-import');
                const submitText = document.getElementById('btn-submit-text');

                if (modelSelect && expectedFileName) {
                    function updateHint() {
                        const selectedOption = modelSelect.options[modelSelect.selectedIndex];
                        const expected = selectedOption ? selectedOption.getAttribute('data-file') : null;
                        expectedFileName.textContent = expected || 'Select a model above';
                    }

                    modelSelect.addEventListener('change', updateHint);
                    updateHint();
                }

                if (form && submitBtn) {
                    form.addEventListener('submit', function() {
                        submitBtn.disabled = true;
                        submitText.innerHTML = '<i class="bx bx-loader-alt animate-spin text-sm me-1"></i> Importing...';
                    });
                }

                // Close on ESC
                window.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeImportModal();
                    }
                });
            });

            function openImportModal(preselectedModel) {
                const select = document.getElementById('import_model_select');
                if (select && preselectedModel) {
                    select.value = preselectedModel;
                    select.dispatchEvent(new Event('change'));
                }
                const modalEl = document.getElementById('importSapModal');
                if (modalEl) {
                    modalEl.classList.remove('hidden');
                    modalEl.classList.add('flex');
                }
            }

            function closeImportModal() {
                const modalEl = document.getElementById('importSapModal');
                if (modalEl) {
                    modalEl.classList.add('hidden');
                    modalEl.classList.remove('flex');
                }
            }
        </script>
    @endpush
@endif
