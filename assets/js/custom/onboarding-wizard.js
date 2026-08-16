document.addEventListener('DOMContentLoaded', function() {
    const config = window.ONBOARDING_CONFIG || {};
    const apiToken = config.apiToken || '';

    let currentStep = 1;
    let registeredRooms = [];
    let editorRoomPhotos = [];

    // Expose variables globally so HTML event handlers or other scripts can inspect
    window.registeredRooms = registeredRooms;
    window.editorRoomPhotos = editorRoomPhotos;

    // Mapbox variables
    let map;
    let marker;

    async function initMap() {
        if (map) return;
        let token = 'YOUR_MAPBOX_ACCESS_TOKEN';
        let mapStyle = 'mapbox://styles/mapbox/streets-v12';

        try {
            const res = await fetch('http://127.0.0.1:8000/api/map-config');
            if (res.ok) {
                const configData = await res.json();
                if (configData.mapbox_token) token = configData.mapbox_token;
                if (configData.style) mapStyle = configData.style;
            }
        } catch (err) {
            console.log('Unable to load dynamic map-config, using defaults.');
        }

        mapboxgl.accessToken = token;
        
        const latInput = document.getElementById('lodgeLatitude');
        const lngInput = document.getElementById('lodgeLongitude');
        const defaultLat = parseFloat(latInput.value) || -6.7780;
        const defaultLng = parseFloat(lngInput.value) || 39.2345;

        map = new mapboxgl.Map({
            container: 'onboardingMap',
            style: mapStyle,
            center: [defaultLng, defaultLat],
            zoom: 13
        });

        marker = new mapboxgl.Marker({
            draggable: true,
            color: '#135846'
        })
        .setLngLat([defaultLng, defaultLat])
        .addTo(map);

        function onDragEnd() {
            const lngLat = marker.getLngLat();
            latInput.value = lngLat.lat.toFixed(6);
            lngInput.value = lngLat.lng.toFixed(6);
        }

        marker.on('dragend', onDragEnd);

        map.on('click', (e) => {
            marker.setLngLat(e.lngLat);
            latInput.value = e.lngLat.lat.toFixed(6);
            lngInput.value = e.lngLat.lng.toFixed(6);
        });
    }

    // Expose globally for step transition triggers
    window.goToStep = function(step) {
        if (step < 1 || step > 6) return;

        // Simple validation
        if (step > currentStep) {
            if (currentStep === 1) {
                const name = document.getElementById('lodgeName').value.trim();
                if (!name) {
                    alert('Lodge name is required.');
                    return;
                }
            }
            if (currentStep === 3) {
                const city = document.getElementById('lodgeCity').value.trim();
                const area = document.getElementById('lodgeArea').value.trim();
                if (!city || !area) {
                    alert('City and Area are required.');
                    return;
                }
            }
        }

        // Hide current
        const currentContainer = document.getElementById(`step-${currentStep}`);
        if (currentContainer) currentContainer.classList.remove('active');
        
        const indCurrent = document.getElementById(`ind-${currentStep}`);
        if (indCurrent) indCurrent.classList.remove('active');

        // Set indicators
        for (let i = 1; i < step; i++) {
            const ind = document.getElementById(`ind-${i}`);
            if (ind) ind.classList.add('completed');
        }

        currentStep = step;
        const targetContainer = document.getElementById(`step-${step}`);
        if (targetContainer) targetContainer.classList.add('active');
        
        const indActive = document.getElementById(`ind-${step}`);
        if (indActive) indActive.classList.add('active');

        const progressBadge = document.getElementById('wizardProgressBadge');
        if (progressBadge) progressBadge.innerText = `Step ${step} of 6`;

        if (step === 3) {
            setTimeout(() => {
                initMap();
                if (map) map.resize();
            }, 150);
        }

        if (step === 6) {
            buildSummaryScreen();
        }
    };

    // Dropzone upload logic for Lodge Main Photo
    const mainLodgeDropzone = document.getElementById('mainLodgeDropzone');
    const mainLodgeInput = document.getElementById('mainLodgeInput');
    const mainLodgePreview = document.getElementById('mainLodgePreview');
    const mainLodgeUrl = document.getElementById('mainLodgeUrl');

    if (mainLodgeDropzone && mainLodgeInput) {
        mainLodgeDropzone.addEventListener('click', () => mainLodgeInput.click());
        mainLodgeInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const txt = document.getElementById('mainLodgeText');
            if (txt) txt.innerText = "Uploading...";
            const formData = new FormData();
            formData.append('file', file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'http://127.0.0.1:8000/api/upload', true);
            if (apiToken) xhr.setRequestHeader('Authorization', 'Bearer ' + apiToken);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    if (mainLodgeUrl) mainLodgeUrl.value = data.url;
                    if (mainLodgePreview) {
                        mainLodgePreview.querySelector('img').src = data.url;
                        mainLodgePreview.classList.remove('d-none');
                    }
                } else {
                    const mockUrl = 'assets/images/room/room1.jpg';
                    if (mainLodgeUrl) mainLodgeUrl.value = mockUrl;
                    if (mainLodgePreview) {
                        mainLodgePreview.querySelector('img').src = mockUrl;
                        mainLodgePreview.classList.remove('d-none');
                    }
                }
                if (txt) txt.innerText = "Click or Drag main photo here to replace";
            };
            xhr.send(formData);
        });
    }

    // Room register & bulk operations
    const roomPhotosDropzone = document.getElementById('roomPhotosDropzone');
    const roomPhotosInput = document.getElementById('roomPhotosInput');
    const roomPhotosPreviews = document.getElementById('roomPhotosPreviews');

    if (roomPhotosDropzone && roomPhotosInput) {
        roomPhotosDropzone.addEventListener('click', () => roomPhotosInput.click());
        roomPhotosInput.addEventListener('change', (e) => {
            Array.from(e.target.files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const cardId = 'mcard-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                const card = document.createElement('div');
                card.className = 'col-md-3 col-sm-6 mb-2';
                card.id = cardId;
                card.innerHTML = `
                    <div class="preview-card">
                        <img src="${URL.createObjectURL(file)}" alt="Preview">
                        <button type="button" class="remove-btn">&times;</button>
                        <div class="upload-progress" id="progm-${cardId}"></div>
                    </div>
                `;
                if (roomPhotosPreviews) roomPhotosPreviews.appendChild(card);

                // upload
                const formData = new FormData();
                formData.append('file', file);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'http://127.0.0.1:8000/api/upload', true);
                if (apiToken) xhr.setRequestHeader('Authorization', 'Bearer ' + apiToken);
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', (ev) => {
                    if (ev.lengthComputable) {
                        const percent = Math.round((ev.loaded / ev.total) * 100);
                        const progressBar = document.getElementById('progm-' + cardId);
                        if (progressBar) progressBar.style.width = percent + '%';
                    }
                });

                xhr.onload = function() {
                    let finalUrl = '';
                    if (xhr.status === 200) {
                        const r = JSON.parse(xhr.responseText);
                        finalUrl = r.url;
                    } else {
                        finalUrl = 'assets/images/room/room' + (Math.floor(Math.random() * 5) + 1) + '.jpg';
                    }
                    card.setAttribute('data-url', finalUrl);
                    editorRoomPhotos.push(finalUrl);
                    card.querySelector('.remove-btn').addEventListener('click', () => {
                        card.remove();
                        editorRoomPhotos = editorRoomPhotos.filter(u => u !== finalUrl);
                    });
                };
                xhr.send(formData);
            });
        });
    }

    window.openRoomEditor = function(index) {
        const errorMsg = document.getElementById('roomPhotosErrorMsg');
        if (errorMsg) errorMsg.classList.add('d-none');
        if (roomPhotosPreviews) roomPhotosPreviews.innerHTML = '';
        editorRoomPhotos = [];

        // Hide room management, show room editor screen
        const step5 = document.getElementById('step-5');
        const stepEditor = document.getElementById('step-room-editor');
        if (step5) step5.classList.remove('active');
        if (stepEditor) stepEditor.classList.add('active');

        // Reset tab active
        const triggerEl = document.querySelector('#editorTabs a[href="#tabDetails"]');
        if (triggerEl) bootstrap.Tab.getOrCreateInstance(triggerEl).show();

        if (index === null) {
            document.getElementById('editorHeaderTitle').innerText = "Add Physical Room Details";
            document.getElementById('editRoomIdx').value = "";
            document.getElementById('roomNum').value = "";
            document.getElementById('roomType').value = "Deluxe";
            document.getElementById('roomPrice').value = "90000";
            document.getElementById('roomFloor').value = "";
            document.getElementById('roomStatus').value = "available";
            document.getElementById('roomDesc').value = "";
            document.getElementById('roomAdults').value = "2";
            document.getElementById('roomChildren').value = "0";
            document.getElementById('roomBeds').value = "";
            document.getElementById('roomSize').value = "";
            document.querySelectorAll('.room-amenity').forEach(cb => cb.checked = false);
        } else {
            const room = registeredRooms[index];
            document.getElementById('editorHeaderTitle').innerText = `Edit Physical Room Details: Room ${room.room_number}`;
            document.getElementById('editRoomIdx').value = index;
            document.getElementById('roomNum').value = room.room_number;
            document.getElementById('roomType').value = room.room_type_id;
            document.getElementById('roomPrice').value = room.price;
            document.getElementById('roomFloor').value = room.floor || '';
            document.getElementById('roomStatus').value = room.status || 'available';
            document.getElementById('roomDesc').value = room.description || '';
            document.getElementById('roomAdults').value = room.max_adults || 2;
            document.getElementById('roomChildren').value = room.max_children || 0;
            document.getElementById('roomBeds').value = room.bed_configuration || '';
            document.getElementById('roomSize').value = room.room_size || '';
            
            const amenities = room.amenities || [];
            document.querySelectorAll('.room-amenity').forEach(cb => {
                cb.checked = amenities.includes(cb.value);
            });

            editorRoomPhotos = [...(room.photos || [])];
            editorRoomPhotos.forEach((url, i) => {
                const cardId = 'mcard-exist-' + i;
                const card = document.createElement('div');
                card.className = 'col-md-3 col-sm-6 mb-2';
                card.id = cardId;
                card.innerHTML = `
                    <div class="preview-card">
                        <img src="${url}" alt="Preview">
                        <button type="button" class="remove-btn">&times;</button>
                    </div>
                `;
                if (roomPhotosPreviews) roomPhotosPreviews.appendChild(card);
                card.querySelector('.remove-btn').addEventListener('click', () => {
                    card.remove();
                    editorRoomPhotos = editorRoomPhotos.filter(u => u !== url);
                });
            });
        }
    };

    window.closeRoomEditor = function() {
        const stepEditor = document.getElementById('step-room-editor');
        const step5 = document.getElementById('step-5');
        if (stepEditor) stepEditor.classList.remove('active');
        if (step5) step5.classList.add('active');
    };

    window.saveRoomDetails = function() {
        if (editorRoomPhotos.length < 4) {
            const error = document.getElementById('roomPhotosErrorMsg');
            if (error) error.classList.remove('d-none');
            return;
        }

        const room_number = document.getElementById('roomNum').value.trim();
        const room_type_id = document.getElementById('roomType').value;
        const price = parseFloat(document.getElementById('roomPrice').value) || 0;
        const floor = document.getElementById('roomFloor').value.trim();
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

        if (!room_number) {
            alert('Please enter a room number.');
            return;
        }

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
            photos: editorRoomPhotos
        };

        const indexVal = document.getElementById('editRoomIdx').value;
        if (indexVal === "") {
            registeredRooms.push(roomData);
        } else {
            registeredRooms[parseInt(indexVal)] = roomData;
        }

        renderRoomsTable();
        closeRoomEditor();
    };

    window.generateBulkRooms = function() {
        const fromNum = parseInt(document.getElementById('bulkFrom').value);
        const toNum = parseInt(document.getElementById('bulkTo').value);
        const defaultType = document.getElementById('bulkType').value;
        const defaultPrice = parseFloat(document.getElementById('bulkPrice').value) || 90000;

        if (isNaN(fromNum) || isNaN(toNum) || fromNum > toNum) {
            alert('Please check your room number ranges.');
            return;
        }

        for (let i = fromNum; i <= toNum; i++) {
            const bulkRoomData = {
                room_number: i.toString(),
                room_type_id: defaultType,
                price: defaultPrice,
                capacity: 2,
                max_adults: 2,
                max_children: 0,
                status: 'available',
                amenities: ["Air conditioning", "Wi-Fi", "Shower"],
                photos: [
                    'assets/images/room/room1.jpg',
                    'assets/images/room/room2.jpg',
                    'assets/images/room/room3.jpg',
                    'assets/images/room/room4.jpg'
                ]
            };
            registeredRooms.push(bulkRoomData);
        }

        renderRoomsTable();
        const modalEl = document.getElementById('bulkRoomModal');
        if (modalEl) bootstrap.Modal.getInstance(modalEl).hide();
    };

    window.deleteRoom = function(index) {
        registeredRooms.splice(index, 1);
        renderRoomsTable();
    };

    function renderRoomsTable() {
        const countTxt = document.getElementById('roomCountText');
        if (countTxt) countTxt.innerText = `Lodge rooms: ${registeredRooms.length} registered`;
        const tbody = document.getElementById('roomsTableBody');
        if (!tbody) return;

        if (registeredRooms.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No rooms added yet. Create rooms individually or in bulk.</td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        registeredRooms.forEach((room, index) => {
            tbody.innerHTML += `
                <tr>
                    <td><strong>${room.room_number}</strong></td>
                    <td><span class="badge badge-info">${room.room_type_id}</span></td>
                    <td>${room.floor || 'N/A'}</td>
                    <td>TSh ${parseFloat(room.price).toLocaleString()}</td>
                    <td><span class="badge badge-success">${room.status}</span></td>
                    <td>${room.photos ? room.photos.length : 0} photos</td>
                    <td>
                        <button class="btn btn-primary btn-xxs me-1" onclick="openRoomEditor(${index})">Edit Details</button>
                        <button class="btn btn-danger btn-xxs" onclick="deleteRoom(${index})">Delete</button>
                    </td>
                </tr>
            `;
        });
    }

    // Summary screen render
    function buildSummaryScreen() {
        const nameEl = document.getElementById('sumLodgeName');
        const typeEl = document.getElementById('sumLodgeType');
        const contactEl = document.getElementById('sumLodgeContact');
        const locationEl = document.getElementById('sumLodgeLocation');
        
        if (nameEl) nameEl.innerText = document.getElementById('lodgeName').value;
        if (typeEl) typeEl.innerText = document.getElementById('lodgeType').value;
        if (contactEl) contactEl.innerText = `${document.getElementById('lodgeEmail').value || 'N/A'} / ${document.getElementById('lodgePhone').value || 'N/A'}`;
        if (locationEl) locationEl.innerText = `${document.getElementById('lodgeCity').value}, ${document.getElementById('lodgeArea').value} (${document.getElementById('lodgeAddress').value || 'No address'})`;
        
        const mainImg = mainLodgeUrl ? mainLodgeUrl.value : '';
        const imgContainer = document.getElementById('summaryLodgeImage');
        if (imgContainer) {
            if (mainImg) {
                imgContainer.innerHTML = `<img src="${mainImg}" class="rounded w-100" style="max-height:150px; object-fit:cover;">`;
            } else {
                imgContainer.innerHTML = `<span class="text-muted">No main image uploaded.</span>`;
            }
        }

        const tbody = document.getElementById('sumRoomsTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';
        if (registeredRooms.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No rooms configured.</td></tr>`;
            return;
        }

        registeredRooms.forEach(room => {
            tbody.innerHTML += `
                <tr>
                    <td><strong>${room.room_number}</strong></td>
                    <td>${room.room_type_id}</td>
                    <td>TSh ${room.price.toLocaleString()}</td>
                    <td>${room.max_adults} Adults, ${room.max_children} Children</td>
                    <td>${room.photos ? room.photos.length : 0} photos</td>
                </tr>
            `;
        });
    }

    // Submit entire wizard structure to database via APIs
    window.submitOnboarding = function() {
        const submitBtn = document.getElementById('submitOnboardingBtn');
        if (submitBtn) {
            submitBtn.innerText = "Submitting...";
            submitBtn.disabled = true;
        }

        const lodgeData = {
            name: document.getElementById('lodgeName').value,
            description: document.getElementById('lodgeDescription').value,
            address: document.getElementById('lodgeAddress').value,
            city: document.getElementById('lodgeCity').value,
            area: document.getElementById('lodgeArea').value,
            latitude: parseFloat(document.getElementById('lodgeLatitude').value) || null,
            longitude: parseFloat(document.getElementById('lodgeLongitude').value) || null,
            price_per_night: registeredRooms.length > 0 ? registeredRooms[0].price : 100000,
            image_url: (mainLodgeUrl && mainLodgeUrl.value) ? mainLodgeUrl.value : 'assets/images/room/room1.jpg'
        };

        // 1. Create property
        fetch('onboarding.php?action=create_property', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(lodgeData)
        })
        .then(res => res.json())
        .then(property => {
            if (property && property.id) {
                // 2. Loop over and post rooms
                const propertyId = property.id;
                const promises = [];

                registeredRooms.forEach(room => {
                    promises.push(
                        fetch(`http://127.0.0.1:8000/api/properties/${propertyId}/rooms`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': 'Bearer ' + apiToken
                            },
                            body: JSON.stringify(room)
                        })
                    );
                });

                // 3. Post verification documents
                const licenseDocUrl = document.getElementById('licenseUrl')?.value;
                if (licenseDocUrl) {
                    promises.push(
                        fetch(`http://127.0.0.1:8000/api/verification/lodge/${propertyId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': 'Bearer ' + apiToken
                            },
                            body: JSON.stringify({
                                documents: [
                                    { document_type: 'business_license', file_url: licenseDocUrl }
                                ]
                            })
                        })
                    );
                }

                return Promise.all(promises);
            } else {
                throw new Error(property.message || 'Failed to onboard lodge profile.');
            }
        })
        .then(() => {
            window.location.href = 'index.php';
        })
        .catch(err => {
            alert('Onboarding failed: ' + err.message);
            if (submitBtn) {
                submitBtn.innerText = "Submit for Admin Approval";
                submitBtn.disabled = false;
            }
        });
    };
});
