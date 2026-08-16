<!-- 2. Lodge Details -->
<div class="step-container" id="step-2">
    <h4 class="font-w600 text-dark mb-4">Lodge Details & Policies</h4>
    <div class="mb-3">
        <label class="form-label font-w500">General Description</label>
        <textarea id="lodgeDescription" class="form-control style-1 border" rows="4" placeholder="Describe your property's unique characteristics, atmosphere, and amenities..."></textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label font-w500">Check-in Policies</label>
            <input type="text" id="lodgeCheckIn" class="form-control style-1 border" placeholder="e.g. From 14:00 PM">
        </div>
        <div class="col-md-6 mb-4">
            <label class="form-label font-w500">Check-out Policies</label>
            <input type="text" id="lodgeCheckOut" class="form-control style-1 border" placeholder="e.g. Before 11:00 AM">
        </div>
    </div>
    <div class="d-flex justify-content-between">
        <button class="btn btn-secondary light" onclick="goToStep(1)">Back</button>
        <button class="btn btn-primary px-4 py-2" onclick="goToStep(3)">Next: Location</button>
    </div>
</div>
