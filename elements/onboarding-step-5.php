<!-- 5. Room Management -->
<div class="step-container" id="step-5">
    <div class="row align-items-center mb-4">
        <div class="col">
            <h4 class="font-w600 text-dark mb-1">Rooms Inventory</h4>
            <p class="text-muted mb-0" id="roomCountText">Lodge rooms: 0 registered</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#bulkRoomModal">+ Add Multiple Rooms</button>
            <button class="btn btn-primary btn-sm" onclick="openRoomEditor(null)">+ Add Room</button>
        </div>
    </div>

    <div class="table-responsive mb-4">
        <table class="table card-table display mb-4 shadow-hover table-responsive-lg">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Type</th>
                    <th>Floor</th>
                    <th>Price (TSh)</th>
                    <th>Status</th>
                    <th>Photos</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="roomsTableBody">
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No rooms added yet. Create rooms individually or in bulk.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between">
        <button class="btn btn-secondary light" onclick="goToStep(4)">Back</button>
        <button class="btn btn-primary px-4 py-2" onclick="goToStep(6)">Next: Review & Submit</button>
    </div>
</div>
