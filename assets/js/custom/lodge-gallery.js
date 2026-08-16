document.addEventListener('DOMContentLoaded', function() {
    const config = window.LODGE_GALLERY_CONFIG || {};
    const descInput = document.getElementById('lodgeDescriptionInput');
    const charCount = document.getElementById('desc-char-count');
    const btnGenerate = document.getElementById('btn-generate-desc');

    const realGeneratedDesc = config.realGeneratedDesc || '';
    const propertyId = config.propertyId || null;
    const apiToken = config.apiToken || '';

    function updateCounter() {
        if (!descInput || !charCount) return;
        const len = descInput.value.length;
        charCount.textContent = `${len.toLocaleString()} / 2,000 chars`;
    }

    if (descInput) {
        descInput.addEventListener('input', updateCounter);
        updateCounter();
    }

    if (btnGenerate) {
        btnGenerate.addEventListener('click', async function() {
            if (!propertyId) return;
            btnGenerate.disabled = true;
            btnGenerate.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';

            try {
                const res = await fetch(`http://127.0.0.1:8000/api/properties/${propertyId}/generate-description`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${apiToken}`
                    }
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.description) {
                        descInput.value = data.description;
                        updateCounter();
                        descInput.focus();
                    }
                } else {
                    descInput.value = realGeneratedDesc;
                    updateCounter();
                }
            } catch (e) {
                descInput.value = realGeneratedDesc;
                updateCounter();
            } finally {
                btnGenerate.disabled = false;
                btnGenerate.innerHTML = '<i class="fas fa-magic me-1"></i> Auto-Generate Description';
            }
        });
    }

    // ==================== PHOTO GALLERY MANAGEMENT SCRIPT ====================
    const dropzoneArea = document.getElementById('dropzone-area');
    const fileInput = document.getElementById('photoFileInput');
    const btnTriggerUpload = document.getElementById('btn-trigger-upload');
    const galleryGrid = document.getElementById('gallery-grid');
    const emptyGalleryState = document.getElementById('empty-gallery-state');
    const coverInput = document.getElementById('coverImageUrlInput');
    const photoCountBadge = document.getElementById('photo-count-badge');
    const progressContainer = document.getElementById('upload-progress-container');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressPercent = document.getElementById('upload-progress-percent');
    const errorAlert = document.getElementById('upload-error-alert');
    const successAlert = document.getElementById('upload-success-alert');

    let photos = [];
    if (coverInput) {
        const initialImageVal = coverInput.value.trim();
        if (initialImageVal) {
            photos = initialImageVal.split(',').map(s => s.trim()).filter(Boolean);
        }
    }

    function renderGallery() {
        if (!galleryGrid || !photoCountBadge) return;
        galleryGrid.innerHTML = '';
        photoCountBadge.textContent = `${photos.length} Photo${photos.length === 1 ? '' : 's'}`;

        if (photos.length === 0) {
            if (emptyGalleryState) emptyGalleryState.classList.remove('d-none');
        } else {
            if (emptyGalleryState) emptyGalleryState.classList.add('d-none');
            photos.forEach((url, idx) => {
                const isCover = (idx === 0);
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 col-lg-3';
                col.setAttribute('draggable', 'true');
                col.dataset.index = idx;

                col.innerHTML = `
                    <div class="card border ${isCover ? 'border-primary border-2 shadow' : 'border-light'} rounded-3 overflow-hidden h-100 position-relative group-hover">
                        <div style="height: 160px; overflow: hidden; background: #eef2f0; position: relative;">
                            <img src="${url}" class="w-100 h-100" style="object-fit: cover;" alt="Lodge photo ${idx + 1}">
                            ${isCover ? '<span class="badge bg-primary position-absolute top-0 start-0 m-2 font-w700 shadow-sm"><i class="fas fa-star me-1"></i> Cover Photo</span>' : ''}
                            <div class="position-absolute top-0 end-0 m-2 d-flex gap-1">
                                <button type="button" class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center btn-remove-photo" data-index="${idx}" style="width: 28px; height: 28px;" title="Remove Photo">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-footer bg-white p-2 d-flex justify-content-between align-items-center">
                            <small class="text-muted font-w600">Photo #${idx + 1}</small>
                            ${!isCover ? `
                                <button type="button" class="btn btn-outline-primary btn-xs font-w600 btn-set-cover" data-index="${idx}">
                                    Make Cover
                                </button>
                            ` : '<span class="text-primary font-w700 small">Main Banner</span>'}
                        </div>
                    </div>
                `;
                galleryGrid.appendChild(col);
            });
        }

        if (coverInput) {
            coverInput.value = photos.join(',');
        }
        attachGalleryEvents();
    }

    function attachGalleryEvents() {
        // Remove photo buttons
        document.querySelectorAll('.btn-remove-photo').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const idx = parseInt(this.dataset.index);
                photos.splice(idx, 1);
                renderGallery();
            };
        });

        // Set Cover photo buttons
        document.querySelectorAll('.btn-set-cover').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const idx = parseInt(this.dataset.index);
                const selected = photos.splice(idx, 1)[0];
                photos.unshift(selected); // Move to first position as main banner
                renderGallery();
            };
        });

        // Simple drag and drop reordering
        let dragSrcIndex = null;
        document.querySelectorAll('#gallery-grid > div').forEach(col => {
            col.addEventListener('dragstart', function(e) {
                dragSrcIndex = parseInt(this.dataset.index);
                e.dataTransfer.effectAllowed = 'move';
            });
            col.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });
            col.addEventListener('drop', function(e) {
                e.preventDefault();
                const targetIndex = parseInt(this.dataset.index);
                if (dragSrcIndex !== null && dragSrcIndex !== targetIndex) {
                    const moved = photos.splice(dragSrcIndex, 1)[0];
                    photos.splice(targetIndex, 0, moved);
                    renderGallery();
                }
            });
        });
    }

    if (dropzoneArea && fileInput && btnTriggerUpload) {
        // Click triggers file picker
        btnTriggerUpload.addEventListener('click', () => fileInput.click());
        dropzoneArea.addEventListener('click', (e) => {
            if (e.target !== btnTriggerUpload && !btnTriggerUpload.contains(e.target)) {
                fileInput.click();
            }
        });

        // Drag and drop zone events
        ['dragenter', 'dragover'].forEach(evt => {
            dropzoneArea.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzoneArea.classList.add('bg-white', 'border-success');
            });
        });
        ['dragleave', 'drop'].forEach(evt => {
            dropzoneArea.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzoneArea.classList.remove('bg-white', 'border-success');
            });
        });

        dropzoneArea.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                handleFiles(files);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                handleFiles(this.files);
            }
        });
    }

    async function handleFiles(fileList) {
        if (errorAlert) errorAlert.classList.add('d-none');
        if (successAlert) successAlert.classList.add('d-none');
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        const maxSizeBytes = 10 * 1024 * 1024; // 10MB

        const uploadQueue = [];
        for (let i = 0; i < fileList.length; i++) {
            const f = fileList[i];
            if (!validTypes.includes(f.type.toLowerCase())) {
                showError(`"${f.name}" is an unsupported file format. Please upload JPG, PNG, or WEBP images.`);
                return;
            }
            if (f.size > maxSizeBytes) {
                showError(`"${f.name}" exceeds the maximum 10MB file size limit.`);
                return;
            }
            uploadQueue.push(f);
        }

        if (uploadQueue.length === 0) return;

        // Show progress bar
        if (progressContainer) progressContainer.classList.remove('d-none');
        if (progressBar) progressBar.style.width = '0%';
        if (progressPercent) progressPercent.textContent = '0%';

        let uploadedCount = 0;

        for (let i = 0; i < uploadQueue.length; i++) {
            const file = uploadQueue[i];
            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch('http://127.0.0.1:8000/api/upload', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${apiToken}`
                    },
                    body: formData
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.url) {
                        photos.push(data.url);
                        uploadedCount++;
                    }
                } else {
                    showError(`Failed to upload ${file.name}.`);
                }
            } catch (err) {
                showError(`Network error while uploading ${file.name}.`);
            }

            const pct = Math.round(((i + 1) / uploadQueue.length) * 100);
            if (progressBar) progressBar.style.width = `${pct}%`;
            if (progressPercent) progressPercent.textContent = `${pct}%`;
        }

        if (progressContainer) progressContainer.classList.add('d-none');
        if (fileInput) fileInput.value = '';

        if (uploadedCount > 0) {
            showSuccess(`Successfully uploaded and compressed ${uploadedCount} photo${uploadedCount > 1 ? 's' : ''}!`);
            renderGallery();
        }
    }

    function showError(msg) {
        if (errorAlert) {
            errorAlert.textContent = msg;
            errorAlert.classList.remove('d-none');
        }
    }

    function showSuccess(msg) {
        if (successAlert) {
            successAlert.textContent = msg;
            successAlert.classList.remove('d-none');
            setTimeout(() => successAlert.classList.add('d-none'), 5000);
        }
    }

    // Initial render
    renderGallery();
});
