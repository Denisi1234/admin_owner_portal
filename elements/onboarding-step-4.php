<!-- 4. Photos, Documents & Amenities -->
<div class="step-container" id="step-4">
    <h4 class="font-w600 text-dark mb-4">Lodge Photos, Verification Documents & Amenities</h4>
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <label class="form-label font-w500">Main Property Photo</label>
            <div class="upload-dropzone text-center p-4" id="mainLodgeDropzone">
                <i class="flaticon-381-picture fs-30 text-primary mb-2"></i>
                <p class="mb-0 text-muted" id="mainLodgeText">Click or Drag main image here</p>
                <input type="file" id="mainLodgeInput" class="d-none" accept="image/*">
                <input type="hidden" id="mainLodgeUrl" value="">
            </div>
            <div id="mainLodgePreview" class="mt-3 text-center d-none">
                <img src="" alt="Main Lodge Image" class="rounded shadow-sm" style="max-height: 150px; object-fit: cover;">
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label font-w500">Business Registration / Tourism License (PDF or Image)</label>
            <div class="upload-dropzone text-center p-4" id="licenseDropzone">
                <i class="flaticon-381-file fs-30 text-primary mb-2"></i>
                <p class="mb-0 text-muted" id="licenseText">Click to upload Business License / Certificate</p>
                <input type="file" id="licenseInput" class="d-none" accept="image/*,.pdf">
                <input type="hidden" id="licenseUrl" value="">
            </div>
            <div id="licensePreview" class="mt-2 text-center d-none">
                <span class="badge bg-success py-2 px-3"><i class="fas fa-check me-1"></i> Document Uploaded</span>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between">
        <button class="btn btn-secondary light" onclick="goToStep(3)">Back</button>
        <button class="btn btn-primary px-4 py-2" onclick="goToStep(5)">Next: Room Management</button>
    </div>
</div>
