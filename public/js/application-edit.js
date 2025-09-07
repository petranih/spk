// public/js/application-edit.js
// PERBAIKAN: JavaScript untuk menangani form edit aplikasi dengan submission yang benar

class ApplicationEdit {
    constructor() {
        this.applicationId = null;
        this.uploadUrl = null;
        this.submitUrl = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateSubmitButton();
        this.initializeUrls();
    }

    initializeUrls() {
        // Get URLs from DOM elements
        const submitBtn = document.getElementById('submitApplicationBtn');
        if (submitBtn) {
            this.submitUrl = submitBtn.dataset.url;
        }
        
        // Extract application ID from form action
        const form = document.getElementById('mainApplicationForm');
        if (form && form.action) {
            const matches = form.action.match(/application\/(\d+)/);
            if (matches) {
                this.applicationId = matches[1];
                this.uploadUrl = form.action.replace('/update', '') + '/upload';
            }
        }
    }

    setupEventListeners() {
        // Main form submission (untuk save data)
        const mainForm = document.getElementById('mainApplicationForm');
        if (mainForm) {
            mainForm.addEventListener('submit', (e) => {
                console.log('Main form submitted');
                this.showAlert('info', 'Menyimpan data...');
            });
        }

        // Upload document button
        const uploadBtn = document.getElementById('uploadBtn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleUpload();
            });
        }

        // Submit application button  
        const submitBtn = document.getElementById('submitApplicationBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }

        // Delete document buttons (event delegation)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-doc-btn')) {
                e.preventDefault();
                this.handleDelete(e.target.closest('.delete-doc-btn'));
            }
        });

        // Document type auto-fill
        const docTypeSelect = document.getElementById('document_type');
        if (docTypeSelect) {
            docTypeSelect.addEventListener('change', (e) => {
                this.autoFillDocumentName(e.target.value);
            });
        }

        // Criteria input changes
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('criteria-input')) {
                this.updateCriteriaChecklist();
                this.updateSubmitButton();
            }
        });
    }

    autoFillDocumentName(type) {
        const documentNames = {
            'ktp': 'KTP Orang Tua',
            'kk': 'Kartu Keluarga',
            'slip_gaji': 'Slip Gaji Orang Tua',
            'surat_keterangan': 'Surat Keterangan Tidak Mampu'
        };
        
        const nameInput = document.getElementById('document_name');
        if (nameInput && documentNames[type]) {
            nameInput.value = documentNames[type];
        }
    }

    handleUpload() {
        if (!this.validateUploadForm()) {
            return;
        }

        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        formData.append('document_type', document.getElementById('document_type').value);
        formData.append('document_name', document.getElementById('document_name').value);
        formData.append('file', document.getElementById('file').files[0]);

        this.setUploadLoading(true);

        fetch(this.uploadUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.handleUploadSuccess(data);
            } else {
                this.showAlert('danger', data.message || 'Upload gagal');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            this.showAlert('danger', 'Upload gagal. Silakan coba lagi.');
        })
        .finally(() => {
            this.setUploadLoading(false);
        });
    }

    validateUploadForm() {
        const documentType = document.getElementById('document_type').value;
        const documentName = document.getElementById('document_name').value;
        const file = document.getElementById('file').files[0];
        
        if (!documentType || !documentName || !file) {
            this.showAlert('danger', 'Mohon lengkapi semua field upload');
            return false;
        }
        
        if (file.size > 2048 * 1024) {
            this.showAlert('danger', 'Ukuran file maksimal 2MB');
            return false;
        }
        
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            this.showAlert('danger', 'Tipe file harus PDF, JPG, JPEG, atau PNG');
            return false;
        }
        
        return true;
    }

    handleUploadSuccess(response) {
        // Reset upload form
        document.getElementById('document_type').value = '';
        document.getElementById('document_name').value = '';
        document.getElementById('file').value = '';
        
        // Update documents table
        this.updateDocumentsTable(response.document, response);
        
        // Update checklist
        this.updateChecklist(response.document.document_type);
        
        // Update submit button status
        this.updateSubmitButton();
        
        this.showAlert('success', response.message);
    }

    setUploadLoading(loading) {
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        
        if (loading) {
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading...';
            uploadProgress.style.display = 'block';
        } else {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload';
            uploadProgress.style.display = 'none';
        }
    }

    handleDelete(button) {
        if (!confirm('Yakin hapus dokumen ini?')) {
            return;
        }
        
        const docId = button.dataset.docId;
        const deleteUrl = button.dataset.deleteUrl;
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.handleDeleteSuccess(docId, data.message);
            } else {
                this.showAlert('danger', data.message || 'Gagal menghapus dokumen');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            this.showAlert('danger', 'Gagal menghapus dokumen. Silakan coba lagi.');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-trash"></i>';
        });
    }

    handleDeleteSuccess(docId, message) {
        const row = document.getElementById(`doc_${docId}`);
        if (row) {
            row.style.transition = 'opacity 0.3s';
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();
                
                // Check if table is empty
                const tableBody = document.getElementById('documentsTableBody');
                if (tableBody && tableBody.children.length === 0) {
                    document.getElementById('documentsContainer').innerHTML = `
                        <div class="text-center text-muted" id="emptyDocuments">
                            <i class="fas fa-file-upload fa-3x mb-2"></i>
                            <p>Belum ada dokumen yang diupload</p>
                        </div>
                    `;
                }
            }, 300);
        }
        
        this.updateChecklistAfterDelete();
        this.updateSubmitButton();
        this.showAlert('success', message);
    }

    handleSubmit() {
        const submitBtn = document.getElementById('submitApplicationBtn');
        
        if (submitBtn.disabled) {
            this.showAlert('warning', 'Pastikan semua data sudah lengkap sebelum submit');
            return;
        }
        
        // Validation before submit
        const validationErrors = this.validateBeforeSubmit();
        if (validationErrors.length > 0) {
            this.showAlert('danger', 'Tidak dapat submit: ' + validationErrors.join('; '));
            return;
        }
        
        if (!confirm('Yakin ingin submit aplikasi?\n\nSetelah disubmit, Anda tidak dapat mengubah data lagi.\n\nPastikan semua data sudah benar!')) {
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        
        fetch(this.submitUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showAlert('success', data.message + ' Redirecting...');
                
                // Disable all form elements
                const allInputs = document.querySelectorAll('input, select, textarea, button');
                allInputs.forEach(input => input.disabled = true);
                
                setTimeout(() => {
                    window.location.href = data.redirect_url || '/student/dashboard';
                }, 3000);
            } else {
                this.showAlert('danger', data.message || 'Gagal submit aplikasi');
                
                if (data.errors && Array.isArray(data.errors)) {
                    const errorList = data.errors.map(err => `• ${err}`).join('<br>');
                    this.showAlert('danger', `Gagal submit aplikasi:<br>${errorList}`);
                }
                
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Aplikasi';
            }
        })
        .catch(error => {
            console.error('Submit error:', error);
            this.showAlert('danger', 'Gagal submit aplikasi. Silakan coba lagi.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Aplikasi';
        });
    }

    validateBeforeSubmit() {
        const errors = [];
        
        // Check personal data
        const requiredFields = ['full_name', 'nisn', 'school', 'class', 'birth_date', 'birth_place', 'gender', 'phone', 'address'];
        requiredFields.forEach(field => {
            const input = document.querySelector(`[name="${field}"]`);
            if (!input || !input.value || input.value.trim() === '') {
                errors.push(`Field ${field.replace('_', ' ')} belum diisi`);
            }
        });
        
        // Check criteria values
        const checkedCriteria = document.querySelectorAll('.criteria-input:checked');
        if (checkedCriteria.length === 0) {
            errors.push('Belum ada kriteria yang dipilih');
        }
        
        // Check required documents
        const requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        const missingDocs = [];
        
        requiredDocs.forEach(docType => {
            const docRow = document.querySelector(`tr[data-doc-type="${docType}"]`);
            if (!docRow) {
                const docNames = {
                    'ktp': 'KTP Orang Tua',
                    'kk': 'Kartu Keluarga',
                    'slip_gaji': 'Slip Gaji',
                    'surat_keterangan': 'Surat Keterangan'
                };
                missingDocs.push(docNames[docType]);
            }
        });
        
        if (missingDocs.length > 0) {
            errors.push(`Dokumen belum lengkap: ${missingDocs.join(', ')}`);
        }
        
        return errors;
    }

    updateDocumentsTable(document, response) {
        let documentsContainer = document.getElementById('documentsContainer');
        let table = document.getElementById('documentsTable');
        
        if (!table) {
            documentsContainer.innerHTML = `
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
            `;
        }
        
        // Remove existing row with same document type
        const existingRow = document.querySelector(`tr[data-doc-type="${document.document_type}"]`);
        if (existingRow) {
            existingRow.remove();
        }
        
        const badgeClass = this.getBadgeClass(document.document_type);
        const newRow = document.createElement('tr');
        newRow.id = `doc_${document.id}`;
        newRow.setAttribute('data-doc-type', document.document_type);
        newRow.innerHTML = `
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
        `;
        
        document.getElementById('documentsTableBody').appendChild(newRow);
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
        const checkElement = document.getElementById(`${documentType}-check`);
        if (checkElement) {
            checkElement.classList.add('completed');
            const icon = checkElement.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-check-circle text-success';
            }
        }
    }

    updateCriteriaChecklist() {
        const checkedCriteria = document.querySelectorAll('.criteria-input:checked');
        const criteriaCheck = document.getElementById('criteria-check');
        
        if (criteriaCheck) {
            const icon = criteriaCheck.querySelector('i');
            if (checkedCriteria.length > 0) {
                criteriaCheck.classList.add('completed');
                if (icon) {
                    icon.className = 'fas fa-check-circle text-success';
                }
            } else {
                criteriaCheck.classList.remove('completed');
                if (icon) {
                    icon.className = 'fas fa-circle text-muted';
                }
            }
        }
    }

    updateChecklistAfterDelete() {
        const docTypes = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        
        docTypes.forEach(type => {
            const hasDoc = document.querySelector(`tr[data-doc-type="${type}"]`) !== null;
            const checkElement = document.getElementById(`${type}-check`);
            
            if (checkElement) {
                const icon = checkElement.querySelector('i');
                if (hasDoc) {
                    checkElement.classList.add('completed');
                    if (icon) {
                        icon.className = 'fas fa-check-circle text-success';
                    }
                } else {
                    checkElement.classList.remove('completed');
                    if (icon) {
                        icon.className = 'fas fa-circle text-muted';
                    }
                }
            }
        });
    }

    updateSubmitButton() {
        const requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        const allDocsUploaded = requiredDocs.every(type => 
            document.querySelector(`tr[data-doc-type="${type}"]`) !== null
        );
        
        const hasCriteriaValues = document.querySelectorAll('.criteria-input:checked').length > 0;
        const canSubmit = allDocsUploaded && hasCriteriaValues;
        
        const submitBtn = document.getElementById('submitApplicationBtn');
        const submitHelp = document.getElementById('submitHelp');
        
        if (submitBtn) {
            if (canSubmit) {
                submitBtn.disabled = false;
                submitBtn.className = submitBtn.className.replace('btn-secondary', 'btn-success');
                if (submitHelp) submitHelp.style.display = 'none';
            } else {
                submitBtn.disabled = true;
                submitBtn.className = submitBtn.className.replace('btn-success', 'btn-secondary');
                
                if (submitHelp) {
                    submitHelp.style.display = 'block';
                    
                    const missingItems = [];
                    if (!hasCriteriaValues) missingItems.push('pilih kriteria');
                    if (!allDocsUploaded) {
                        const missingDocs = requiredDocs.filter(type => 
                            document.querySelector(`tr[data-doc-type="${type}"]`) === null
                        );
                        if (missingDocs.length > 0) {
                            missingItems.push(`upload ${missingDocs.length} dokumen`);
                        }
                    }
                    
                    if (missingItems.length > 0) {
                        submitHelp.textContent = 'Lengkapi: ' + missingItems.join(', ');
                    }
                }
            }
        }
    }

    showAlert(type, message) {
        const alertIcons = {
            'success': 'fa-check-circle',
            'danger': 'fa-exclamation-triangle',
            'warning': 'fa-exclamation-circle',
            'info': 'fa-info-circle'
        };
        
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas ${alertIcons[type] || 'fa-info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert-dismissible');
        existingAlerts.forEach(alert => alert.remove());
        
        // Add new alert
        const stepsProgress = document.querySelector('.steps-progress');
        if (stepsProgress) {
            const card = stepsProgress.closest('.card');
            if (card) {
                card.insertAdjacentHTML('afterend', alertHtml);
            }
        }
        
        // Auto dismiss for success/info
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert-dismissible');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.3s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 5000);
        }
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.applicationEdit = new ApplicationEdit();
    console.log('ApplicationEdit initialized');
});