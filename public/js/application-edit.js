// public/js/application-edit.js
// PERBAIKAN: JavaScript untuk menangani upload dokumen tanpa reset form utama

class ApplicationEdit {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateSubmitButton();
    }

    setupEventListeners() {
        // PERBAIKAN: Upload menggunakan button click, bukan form submit
        $('#uploadBtn').on('click', (e) => {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            this.handleUpload();
        });

        // AJAX Delete Document
        $(document).on('click', '.delete-doc-btn', (e) => {
            e.preventDefault();
            this.handleDelete(e.target);
        });

        // Check submit button status on criteria change
        $('input[name^="criteria_values"]').on('change', () => {
            this.updateSubmitButton();
        });

        // Auto-fill document name based on document type
        $('#document_type').on('change', (e) => {
            const type = e.target.value;
            const documentNames = {
                'ktp': 'KTP Orang Tua',
                'kk': 'Kartu Keluarga',
                'slip_gaji': 'Slip Gaji Orang Tua',
                'surat_keterangan': 'Surat Keterangan Tidak Mampu'
            };
            
            if (documentNames[type]) {
                $('#document_name').val(documentNames[type]);
            }
        });

        // PERBAIKAN: Prevent main form submit when upload button is clicked
        $('#mainForm').on('submit', (e) => {
            // Jika yang diklik adalah upload button, prevent submit
            if ($(document.activeElement).attr('id') === 'uploadBtn') {
                e.preventDefault();
                return false;
            }
            
            // Validasi form sebelum submit
            if (!this.validateForm()) {
                e.preventDefault();
                this.showAlert('warning', 'Mohon lengkapi semua data yang diperlukan');
            }
        });
    }

    handleUpload() {
        // Validasi input upload
        const documentType = $('#document_type').val();
        const documentName = $('#document_name').val();
        const fileInput = $('#file')[0];
        const file = fileInput.files[0];
        
        if (!documentType || !documentName || !file) {
            this.showAlert('danger', 'Mohon lengkapi semua field upload');
            return;
        }
        
        // Validasi file size (2MB = 2048KB)
        if (file.size > 2048 * 1024) {
            this.showAlert('danger', 'Ukuran file maksimal 2MB');
            return;
        }
        
        // Validasi file type
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            this.showAlert('danger', 'Tipe file harus PDF, JPG, JPEG, atau PNG');
            return;
        }
        
        // Buat FormData untuk upload
        const formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('document_type', documentType);
        formData.append('document_name', documentName);
        formData.append('file', file);
        
        // Show loading state
        this.setUploadLoading(true);
        
        $.ajax({
            url: $('#mainForm').attr('action') + '/upload', // Dynamic URL berdasarkan main form
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: (response) => {
                if (response.success) {
                    this.handleUploadSuccess(response);
                } else {
                    this.showAlert('danger', response.message || 'Upload gagal');
                }
            },
            error: (xhr) => {
                this.handleUploadError(xhr);
            },
            complete: () => {
                this.setUploadLoading(false);
            }
        });
    }

    handleUploadSuccess(response) {
        // PERBAIKAN: Reset HANYA form upload, bukan seluruh form
        $('#document_type').val('');
        $('#document_name').val('');
        $('#file').val('');
        
        // Update documents table
        this.updateDocumentsTable(response.document, response);
        
        // Update checklist
        this.updateChecklist(response.document.document_type);
        
        // Update submit button status
        this.updateSubmitButton();
        
        // Show success message
        this.showAlert('success', response.message);
    }

    handleUploadError(xhr) {
        let message = 'Upload gagal. Silakan coba lagi.';
        
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            // Validation errors
            const errors = Object.values(xhr.responseJSON.errors).flat();
            message = errors.join(', ');
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }
        
        this.showAlert('danger', message);
    }

    handleDelete(target) {
        if (!confirm('Yakin hapus dokumen ini?')) {
            return;
        }
        
        const btn = $(target).closest('.delete-doc-btn');
        const docId = btn.data('doc-id');
        const deleteUrl = btn.data('delete-url');
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: deleteUrl,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: (response) => {
                if (response.success) {
                    this.handleDeleteSuccess(docId, response.message);
                } else {
                    this.showAlert('danger', response.message || 'Gagal menghapus dokumen');
                }
            },
            error: () => {
                this.showAlert('danger', 'Gagal menghapus dokumen. Silakan coba lagi.');
            },
            complete: () => {
                btn.prop('disabled', false);
                btn.html('<i class="fas fa-trash"></i>');
            }
        });
    }

    handleDeleteSuccess(docId, message) {
        // Remove row from table with animation
        $(`#doc_${docId}`).fadeOut(300, function() {
            $(this).remove();
            
            // Check if table is empty
            if ($('#documentsTableBody tr').length === 0) {
                $('#documentsContainer').html(`
                    <div class="text-center text-muted" id="emptyDocuments">
                        <i class="fas fa-file-upload fa-3x mb-2"></i>
                        <p>Belum ada dokumen yang diupload</p>
                    </div>
                `);
            }
        });
        
        // Update checklist and submit button
        this.updateChecklistAfterDelete();
        this.updateSubmitButton();
        
        this.showAlert('success', message);
    }

    setUploadLoading(loading) {
        const uploadBtn = $('#uploadBtn');
        const uploadProgress = $('#uploadProgress');
        
        if (loading) {
            uploadBtn.prop('disabled', true);
            uploadBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Uploading...');
            uploadProgress.show();
        } else {
            uploadBtn.prop('disabled', false);
            uploadBtn.html('<i class="fas fa-upload me-1"></i>Upload');
            uploadProgress.hide();
        }
    }

    updateDocumentsTable(document, response) {
        // Create table if it doesn't exist
        if ($('#documentsTable').length === 0) {
            $('#documentsContainer').html(`
                <div class="table-responsive">
                    <table class="table table-sm" id="documentsTable">
                        <thead>
                            <tr>
                                <th>Jenis Dokumen</th>
                                <th>Nama</th>
                                <th>Ukuran</th>
                                <th>Tanggal Upload</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="documentsTableBody"></tbody>
                    </table>
                </div>
            `);
        }
        
        // Remove existing row with same document type (replace)
        $(`tr[data-doc-type="${document.document_type}"]`).remove();
        
        // Add new row
        const badgeClass = this.getBadgeClass(document.document_type);
        const newRow = `
            <tr id="doc_${document.id}" data-doc-type="${document.document_type}">
                <td><span class="badge ${badgeClass}">${response.document_type_display}</span></td>
                <td>${document.document_name}</td>
                <td>${response.file_size_display}</td>
                <td>${response.created_at_display}</td>
                <td>
                    <a href="${response.view_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-doc-btn" 
                            data-doc-id="${document.id}" 
                            data-delete-url="${response.delete_url}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#documentsTableBody').append(newRow);
    }

    getBadgeClass(documentType) {
        const badges = {
            'ktp': 'bg-primary',
            'kk': 'bg-info', 
            'slip_gaji': 'bg-success',
            'surat_keterangan': 'bg-warning'
        };
        return badges[documentType] || 'bg-secondary';
    }

    updateChecklist(documentType) {
        const checkElement = $(`#${documentType}-check`);
        checkElement.addClass('completed');
        checkElement.find('i')
            .removeClass('fa-circle text-muted')
            .addClass('fa-check-circle text-success');
    }

    updateChecklistAfterDelete() {
        const docTypes = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        
        docTypes.forEach(type => {
            const hasDoc = $(`tr[data-doc-type="${type}"]`).length > 0;
            const checkElement = $(`#${type}-check`);
            
            if (hasDoc) {
                checkElement.addClass('completed');
                checkElement.find('i')
                    .removeClass('fa-circle text-muted')
                    .addClass('fa-check-circle text-success');
            } else {
                checkElement.removeClass('completed');
                checkElement.find('i')
                    .removeClass('fa-check-circle text-success')
                    .addClass('fa-circle text-muted');
            }
        });
    }

    updateSubmitButton() {
        // Check if all requirements are met
        const requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        const allDocsUploaded = requiredDocs.every(type => 
            $(`tr[data-doc-type="${type}"]`).length > 0
        );
        
        const hasCriteriaValues = $('input[name^="criteria_values"]:checked').length > 0;
        const canSubmit = allDocsUploaded && hasCriteriaValues;
        
        const submitBtn = $('#submitBtn');
        const submitHelp = $('#submitHelp');
        
        if (canSubmit) {
            submitBtn.prop('disabled', false);
            submitBtn.removeClass('btn-secondary').addClass('btn-success');
            if (submitHelp.length) submitHelp.hide();
        } else {
            submitBtn.prop('disabled', true);
            submitBtn.removeClass('btn-success').addClass('btn-secondary');
            if (submitHelp.length) submitHelp.show();
        }
    }

    validateForm() {
        // Check personal data
        const requiredFields = ['full_name', 'nisn', 'school', 'class', 'birth_date', 'birth_place', 'gender', 'address', 'phone'];
        let isValid = true;
        
        requiredFields.forEach(field => {
            const input = $(`[name="${field}"]`);
            if (!input.val() || input.val().trim() === '') {
                input.addClass('is-invalid');
                isValid = false;
            } else {
                input.removeClass('is-invalid');
            }
        });

        return isValid;
    }

    showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas ${this.getAlertIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert-dismissible').remove();
        
        // Add new alert at top of content
        $('.steps-progress').closest('.card').after(alertHtml);
        
        // Auto dismiss after 5 seconds for success/info
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                $('.alert-dismissible').fadeOut();
            }, 5000);
        }
        
        // Scroll to top to show alert
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    getAlertIcon(type) {
        const icons = {
            'success': 'fa-check-circle',
            'danger': 'fa-exclamation-triangle',
            'warning': 'fa-exclamation-circle',
            'info': 'fa-info-circle'
        };
        return icons[type] || 'fa-info-circle';
    }

    // PERBAIKAN: Utility method to handle upload specifically
    validateUploadForm() {
        const documentType = $('#document_type').val();
        const documentName = $('#document_name').val();
        const file = $('#file')[0].files[0];
        
        if (!documentType || !documentName || !file) {
            this.showAlert('danger', 'Mohon lengkapi semua field upload');
            return false;
        }
        
        // Validate file size
        if (file.size > 2048 * 1024) {
            this.showAlert('danger', 'Ukuran file maksimal 2MB');
            return false;
        }
        
        // Validate file type
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            this.showAlert('danger', 'Tipe file harus PDF, JPG, JPEG, atau PNG');
            return false;
        }
        
        return true;
    }

    // Method untuk handle upload dengan validasi yang tepat
    performUpload() {
        if (!this.validateUploadForm()) {
            return;
        }
        
        const formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('document_type', $('#document_type').val());
        formData.append('document_name', $('#document_name').val());
        formData.append('file', $('#file')[0].files[0]);
        
        // Get upload URL dari route
        const uploadUrl = $('#mainForm').attr('action') + '/upload';
        
        this.setUploadLoading(true);
        
        $.ajax({
            url: uploadUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: (response) => {
                if (response.success) {
                    this.handleUploadSuccess(response);
                } else {
                    this.showAlert('danger', response.message || 'Upload gagal');
                }
            },
            error: (xhr) => {
                this.handleUploadError(xhr);
            },
            complete: () => {
                this.setUploadLoading(false);
            }
        });
    }
}

// Initialize when document is ready
$(document).ready(function() {
    window.applicationEdit = new ApplicationEdit();
    
    // PERBAIKAN: Override default form behavior untuk upload
    $('#uploadBtn').on('click', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        
        if (window.applicationEdit) {
            window.applicationEdit.performUpload();
        }
    });
    
    // PERBAIKAN: Prevent any form submission dari dalam upload area
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Handle file change untuk preview
    $('#file').on('change', function() {
        const file = this.files[0];
        if (file) {
            // Optional: Show file preview atau info
            console.log('File selected:', file.name, 'Size:', formatFileSize(file.size));
        }
    });
});

// Additional utility functions
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}