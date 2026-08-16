<!-- 3. Location -->
<div class="step-container" id="step-3">
    <h4 class="font-w600 text-dark mb-4">Lodge Location</h4>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label font-w500">City</label>
            <input type="text" id="lodgeCity" class="form-control style-1 border" placeholder="e.g. Arusha" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label font-w500">Area</label>
            <input type="text" id="lodgeArea" class="form-control style-1 border" placeholder="e.g. Sekei" required>
        </div>
    </div>
     <div class="mb-3">
         <label class="form-label font-w500">Full Address</label>
         <input type="text" id="lodgeAddress" class="form-control style-1 border" placeholder="e.g. 45 Sekei Road, Arusha">
     </div>
     
     <div class="mb-4">
         <div class="d-flex justify-content-between align-items-center mb-2">
             <label class="form-label font-w500 mb-0">Select Coordinates on Map</label>
             <span id="geocodeStatus" class="badge bg-light text-dark fs-12 font-w400">Type address above to auto-pin location</span>
         </div>
         <div id="onboardingMap" style="height: 400px; border-radius: 12px; border: 1px solid #e0e0e0; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,0.08);"></div>
         <input type="hidden" id="lodgeLatitude" value="-3.3730">
         <input type="hidden" id="lodgeLongitude" value="36.6850">
         <div class="d-flex justify-content-between align-items-center mt-2">
             <span class="fs-12 text-muted"><i class="fas fa-info-circle me-1"></i> You can type City, Area & Full Address above to auto-center the map, or click/drag the marker manually.</span>
             <span class="fs-12 font-w600 text-primary" id="coordDisplay">Lat: -3.3730, Lng: 36.6850</span>
         </div>
     </div>

     <div class="d-flex justify-content-between">
         <button class="btn btn-secondary light" onclick="goToStep(2)">Back</button>
         <button class="btn btn-primary px-4 py-2" onclick="goToStep(4)">Next: Photos & Amenities</button>
     </div>
</div>
