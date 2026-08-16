<!-- Bulk Room Modal -->
<div class="modal fade" id="bulkRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0 bg-primary py-4 px-4 text-white">
                <h5 class="modal-title text-white font-w600 fs-18">Bulk Add Rooms</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-w500 text-dark">Room Number From</label>
                        <input type="number" id="bulkFrom" class="form-control style-1 border" placeholder="e.g. 101" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-w500 text-dark">Room Number To</label>
                        <input type="number" id="bulkTo" class="form-control style-1 border" placeholder="e.g. 120" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-w500 text-dark">Default Type</label>
                        <select id="bulkType" class="form-control default-select style-1 border">
                            <option value="Deluxe">Deluxe</option>
                            <option value="Standard">Standard</option>
                            <option value="Suite">Suite</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-w500 text-dark">Default Price (TSh)</label>
                        <input type="number" id="bulkPrice" class="form-control style-1 border" placeholder="e.g. 90000" value="90000">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-danger btn-md light rounded" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-md rounded px-4" onclick="generateBulkRooms()">Generate Rooms</button>
            </div>
        </div>
    </div>
</div>
