@if(auth()->check() && auth()->user()->hasRole(['super-admin', 'admin']))
    {{-- MODAL IMPORT SAP EVALUATION DATA --}}
    <div class="modal fade" id="importSapModal" tabindex="-1" aria-labelledby="importSapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title h6 mb-0 d-flex align-items-center gap-2" id="importSapModalLabel">
                        <i class="bx bx-upload fs-5"></i>
                        <span>Update Evaluation Model (SAP Import)</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('purchasing.evaluationsupplier.import') }}" method="POST" enctype="multipart/form-data" id="import-sap-form">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-warning d-flex align-items-start gap-2 py-2 px-3 small mb-3 border-0 bg-warning bg-opacity-10 text-dark">
                            <i class="bx bx-info-circle text-warning fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong>Replace Strategy:</strong> Uploading will replace the existing records in the selected table with data from the new SAP export.
                            </div>
                        </div>

                        {{-- MODEL SELECTOR --}}
                        <div class="mb-3">
                            <label for="import_model_select" class="form-label fw-semibold small">Select Evaluation Model / Table</label>
                            <select name="model" id="import_model_select" class="form-select" required>
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
                            <div class="form-text text-muted small mt-1" id="expected_file_hint">
                                Expected SAP File: <span class="fw-semibold text-primary" id="expected_file_name">-</span>
                            </div>
                        </div>

                        {{-- FILE INPUT --}}
                        <div class="mb-3">
                            <label for="import_file_input" class="form-label fw-semibold small">SAP Export File (.xls / .txt / .csv)</label>
                            <input type="file" name="file" id="import_file_input" class="form-control" accept=".xls,.xlsx,.txt,.csv" required>
                            <div class="form-text text-muted small">
                                Supports direct SAP Unicode UTF-16 tab-delimited exports.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary d-flex align-items-center gap-1" id="btn-submit-import">
                            <i class="bx bx-check"></i>
                            <span id="btn-submit-text">Upload & Replace Data</span>
                        </button>
                    </div>
                </form>
            </div>
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
                        submitText.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importing...';
                    });
                }
            });

            // Helper to open modal with preselected model from Kriteria pages
            function openImportModal(preselectedModel) {
                const select = document.getElementById('import_model_select');
                if (select && preselectedModel) {
                    select.value = preselectedModel;
                    select.dispatchEvent(new Event('change'));
                }
                const modalEl = document.getElementById('importSapModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            }
        </script>
    @endpush
@endif
