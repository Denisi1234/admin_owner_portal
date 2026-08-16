<!-- 1. Basic Lodge Information -->
<div class="step-container active" id="step-1">
    <h4 class="font-w600 text-dark mb-4">Lodge Basic Information</h4>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label font-w500">Lodge / Property Name</label>
            <input type="text" id="lodgeName" class="form-control style-1 border" placeholder="e.g. Sunrise Lodge" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label font-w500">Property Type</label>
            <select id="lodgeType" class="form-control default-select style-1 border">
                <option value="Lodge">Lodge</option>
                <option value="Hotel">Hotel</option>
                <option value="Apartment">Apartment</option>
                <option value="Resort">Resort</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label font-w500">Contact Email Address</label>
            <input type="email" id="lodgeEmail" class="form-control style-1 border" placeholder="e.g. contact@sunriselodge.com">
        </div>
        <div class="col-md-6 mb-4">
            <label class="form-label font-w500">Contact Phone Number</label>
            <input type="text" id="lodgePhone" class="form-control style-1 border" placeholder="e.g. +255 712 345 678">
        </div>
    </div>
    <div class="text-end">
        <button class="btn btn-primary px-4 py-2" onclick="goToStep(2)">Next: Lodge Details</button>
    </div>
</div>
