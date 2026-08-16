<!-- Reusable Room Editor (Custom Screen) -->
<div class="step-container" id="step-room-editor">
    <div class="row align-items-center mb-4">
        <div class="col">
            <h4 class="font-w600 text-dark mb-1" id="editorHeaderTitle">Configure Room</h4>
            <p class="text-muted mb-0">Set room-level information, custom pricing, amenities, and photos.</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-secondary btn-sm" onclick="closeRoomEditor()">Back to Room Management</button>
        </div>
    </div>

    <!-- Tabs inside one room editor -->
    <div class="card shadow-none border">
        <div class="card-header bg-light pb-0 pt-3 border-0">
            <ul class="nav nav-tabs" id="editorTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tabDetails">Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tabPhotosAmenities">Photos & Amenities</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tabPricing">Pricing & Availability</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Tab: Details -->
                <div class="tab-pane fade show active" id="tabDetails">
                    <input type="hidden" id="editRoomIdx" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500">Room Number / Room Name</label>
                            <input type="text" id="roomNum" class="form-control style-1 border" placeholder="e.g. Room 101" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500">Room Type</label>
                            <select id="roomType" class="form-control default-select style-1 border">
                                <option value="Deluxe">Deluxe</option>
                                <option value="Standard">Standard</option>
                                <option value="Suite">Suite</option>
                                <option value="Executive">Executive</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label font-w500">Floor</label>
                            <input type="text" id="roomFloor" class="form-control style-1 border" placeholder="e.g. 1st Floor">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label font-w500">Bed Configuration</label>
                            <input type="text" id="roomBeds" class="form-control style-1 border" placeholder="e.g. 1 King Bed">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label font-w500">Room Size (m²)</label>
                            <input type="text" id="roomSize" class="form-control style-1 border" placeholder="e.g. 28">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500">Max Adults</label>
                            <input type="number" id="roomAdults" class="form-control style-1 border" value="2">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500">Max Children</label>
                            <input type="number" id="roomChildren" class="form-control style-1 border" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-w500">Unique Description</label>
                        <textarea id="roomDesc" class="form-control style-1 border" rows="4" placeholder="Spacious room overlooking the garden with luxury decor..."></textarea>
                    </div>
                </div>

                <!-- Tab: Photos & Amenities -->
                <div class="tab-pane fade" id="tabPhotosAmenities">
                    <div class="mb-4">
                        <label class="form-label font-w600 text-dark">Room Photos (Minimum 4 images required)</label>
                        <div class="row g-2 mb-3" id="roomPhotosPreviews"></div>
                        <div class="upload-dropzone text-center p-4" id="roomPhotosDropzone">
                            <i class="flaticon-381-picture-1 fs-30 text-primary mb-2 d-block"></i>
                            <span class="fs-14 font-w600 text-primary d-block">Click or Drop Room Photos</span>
                            <input type="file" id="roomPhotosInput" class="d-none" multiple accept="image/*">
                        </div>
                        <div class="alert alert-danger py-2 mt-2 d-none" id="roomPhotosErrorMsg">Please upload at least 4 photos for this room.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-w600 text-dark mb-1">Room Amenities</label>
                        <div class="row">
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input room-amenity" type="checkbox" value="Air conditioning" id="am-ac">
                                    <label class="form-check-label" for="am-ac">Air conditioning</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input room-amenity" type="checkbox" value="Wi-Fi" id="am-wifi">
                                    <label class="form-check-label" for="am-wifi">Wi-Fi</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input room-amenity" type="checkbox" value="LED TV" id="am-tv">
                                    <label class="form-check-label" for="am-tv">LED TV</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input room-amenity" type="checkbox" value="Balcony" id="am-balcony">
                                    <label class="form-check-label" for="am-balcony">Balcony</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input room-amenity" type="checkbox" value="Shower" id="am-shower">
                                    <label class="form-check-label" for="am-shower">Shower</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Pricing -->
                <div class="tab-pane fade" id="tabPricing">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500">Room-Specific Price (TSh)</label>
                            <input type="number" id="roomPrice" class="form-control style-1 border" placeholder="e.g. 95000" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500">Status</label>
                            <select id="roomStatus" class="form-control default-select style-1 border">
                                <option value="available">Active / Available</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-end">
                <button class="btn btn-secondary light me-2" onclick="closeRoomEditor()">Cancel</button>
                <button class="btn btn-primary px-4" onclick="saveRoomDetails()">Save Room</button>
            </div>
        </div>
    </div>
</div>
