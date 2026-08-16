document.addEventListener('DOMContentLoaded', function() {
    const config = window.ROOM_EDITOR_CONFIG || {};
    const dropzoneArea = document.getElementById('dropzoneArea');
    const fileInput = document.getElementById('fileInput');
    const previewsContainer = document.getElementById('imagePreviewsContainer');
    
    let uploadedUrls = [];
    const isEdit = config.isEdit || false;
    const roomId = config.roomId || null;
    const initialPhotos = config.initialPhotos || [];
    const apiToken = config.apiToken || '';

    // Render initial photos
    initialPhotos.forEach((url, i) => {
        const cardId = 'card-exist-' + i;
        const card = document.createElement('div');
        card.className = 'col-md-3 col-sm-6 mb-2';
        card.id = cardId;
        card.innerHTML = `
            <div class="preview-card">
                <img src="${url}" alt="Preview">
                <button type="button" class="remove-btn">&times;</button>
            </div>
        `;
        previewsContainer.appendChild(card);
        uploadedUrls.push(url);
        card.querySelector('.remove-btn').addEventListener('click', () => {
            card.remove();
            uploadedUrls = uploadedUrls.filter(u => u !== url);
        });
    });

    // Drag and drop zone events
    ['dragenter', 'dragover'].forEach(evt => {
        if (dropzoneArea) {
            dropzoneArea.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzoneArea.classList.add('bg-white', 'border-success');
            });
        }
    });
    ['dragleave', 'drop'].forEach(evt => {
        if (dropzoneArea) {
            dropzoneArea.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzoneArea.classList.remove('bg-white', 'border-success');
            });
        }
    });

    if (dropzoneArea) {
        dropzoneArea.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                handleFiles(files);
            }
        });

        dropzoneArea.addEventListener('click', (e) => {
            if (e.target !== fileInput && fileInput) fileInput.click();
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                handleFiles(this.files);
            }
        });
    }

    function handleFiles(fileList) {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        Array.from(fileList).forEach(file => {
            if (!validTypes.includes(file.type.toLowerCase())) return;
            const cardId = 'card-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            const card = document.createElement('div');
            card.className = 'col-md-3 col-sm-6 mb-2';
            card.id = cardId;
            card.innerHTML = `
                <div class="preview-card">
                    <img src="${URL.createObjectURL(file)}" alt="Preview">
                    <button type="button" class="remove-btn">&times;</button>
                    <div class="upload-progress" id="progress-${cardId}"></div>
                </div>
            `;
            if (previewsContainer) previewsContainer.appendChild(card);

            uploadFile(file, cardId);
        });
    }

    function uploadFile(file, cardId) {
        const formData = new FormData();
        formData.append('file', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'http://127.0.0.1:8000/api/upload', true);
        if (apiToken) xhr.setRequestHeader('Authorization', 'Bearer ' + apiToken);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                const bar = document.getElementById('progress-' + cardId);
                if (bar) bar.style.width = percent + '%';
            }
        });

        xhr.onload = function() {
            let finalUrl = '';
            if (xhr.status === 200) {
                const res = JSON.parse(xhr.responseText);
                finalUrl = res.url;
            } else {
                alert('Image upload failed. Please verify storage backend.');
                const cardToRemove = document.getElementById(cardId);
                if (cardToRemove) cardToRemove.remove();
                return;
            }
            const card = document.getElementById(cardId);
            if (card) {
                card.setAttribute('data-url', finalUrl);
                uploadedUrls.push(finalUrl);
                card.querySelector('.remove-btn').addEventListener('click', () => {
                    card.remove();
                    uploadedUrls = uploadedUrls.filter(u => u !== finalUrl);
                });
            }
        };
        xhr.send(formData);
    }

    // Expose saveRoom globally for the form submit handler
    window.saveRoom = function(e) {
        e.preventDefault();
        
        if (uploadedUrls.length < 4) {
            const error = document.getElementById('uploadErrorMsg');
            if (error) {
                error.classList.remove('d-none');
                error.scrollIntoView({ behavior: 'smooth' });
            }
            return;
        }

        const propertyId = document.getElementById('roomPropertyId').value;
        const room_number = document.getElementById('roomNum').value.trim();
        const room_type_id = document.getElementById('roomType').value;
        const floor = document.getElementById('roomFloor').value.trim();
        const price = parseFloat(document.getElementById('roomPrice').value) || 0;
        const status = document.getElementById('roomStatus').value;
        const description = document.getElementById('roomDesc').value.trim();
        const max_adults = parseInt(document.getElementById('roomAdults').value) || 2;
        const max_children = parseInt(document.getElementById('roomChildren').value) || 0;
        const bed_configuration = document.getElementById('roomBeds').value.trim();
        const room_size = document.getElementById('roomSize').value.trim();

        const amenities = [];
        document.querySelectorAll('.room-amenity:checked').forEach(cb => {
            amenities.push(cb.value);
        });

        const roomData = {
            room_number,
            room_type_id,
            price,
            floor,
            status,
            description,
            max_adults,
            max_children,
            capacity: max_adults + max_children,
            bed_configuration,
            number_of_beds: 1,
            room_size,
            amenities,
            photos: uploadedUrls
        };

        const apiUrl = isEdit 
            ? `http://127.0.0.1:8000/api/rooms/${roomId}`
            : `http://127.0.0.1:8000/api/properties/${propertyId}/rooms`;
            
        const method = isEdit ? 'PUT' : 'POST';

        fetch(apiUrl, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + apiToken
            },
            body: JSON.stringify(roomData)
        })
        .then(res => {
            if (res.status === 201 || res.status === 200) {
                window.location.href = 'room-list.php';
            } else {
                return res.json().then(err => { throw new Error(err.message || 'Database error occurred') });
            }
        })
        .catch(err => {
            alert('Error saving room details: ' + err.message);
        });
    };
});
