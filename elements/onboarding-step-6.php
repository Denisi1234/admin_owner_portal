<!-- 6. Review & Submit -->
<div class="step-container" id="step-6">
    <h4 class="font-w600 text-dark mb-4">Review Your Property & Rooms</h4>
    
    <!-- Summary Container -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="border rounded p-3 bg-light">
                <h5 class="font-w600 text-dark mb-3">Lodge Information</h5>
                <div class="mb-3 text-center" id="summaryLodgeImage">
                    <span class="text-muted">No main image uploaded.</span>
                </div>
                <p class="mb-1"><strong>Name:</strong> <span id="sumLodgeName"></span></p>
                <p class="mb-1"><strong>Type:</strong> <span id="sumLodgeType"></span></p>
                <p class="mb-1"><strong>Contact:</strong> <span id="sumLodgeContact"></span></p>
                <p class="mb-1"><strong>Location:</strong> <span id="sumLodgeLocation"></span></p>
            </div>
        </div>
        <div class="col-md-8">
            <div class="border rounded p-3">
                <h5 class="font-w600 text-dark mb-3">Registered Rooms Inventory</h5>
                <div class="table-responsive">
                    <table class="table card-table display mb-0 table-responsive-md">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Type</th>
                                <th>Price (TSh)</th>
                                <th>Capacity</th>
                                <th>Photos</th>
                            </tr>
                        </thead>
                        <tbody id="sumRoomsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button class="btn btn-secondary light" onclick="goToStep(5)">Back</button>
        <button class="btn btn-success px-5 py-2" id="submitOnboardingBtn" onclick="submitOnboarding()">Submit for Admin Approval</button>
    </div>
</div>
