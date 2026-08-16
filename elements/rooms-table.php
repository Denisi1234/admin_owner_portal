<?php
// Reusable room table component
$table_id = isset($table_id) ? $table_id : 'guestTable-default';
$rooms_to_show = isset($rooms_to_show) ? $rooms_to_show : [];
?>
<div class="table-responsive">
    <table class="table card-table display mb-4 shadow-hover table-responsive-lg" id="<?php echo htmlspecialchars($table_id); ?>">
        <thead>
            <tr>
                <th class="bg-none">
                    <div class="form-check style-1">
                      <input class="form-check-input" type="checkbox" value="" id="checkAll-<?php echo htmlspecialchars($table_id); ?>">
                    </div>
                </th>
                <th>Room Name</th>
                <th>Bed Type</th>
                <th>Room Floor</th>
                <th>Facilities</th>
                <th>Rate</th>
                <th>Status</th>
                <th class="bg-none"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rooms_to_show)): ?>
                <tr>
                    <td colspan="8" class="text-center">No rooms found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rooms_to_show as $room): ?>
                    <?php 
                        $amenities_arr = !empty($room['amenities']) ? json_decode($room['amenities'], true) : [];
                        if (!is_array($amenities_arr)) {
                            $amenities_arr = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];
                        }
                        $amenities_str = implode(', ', array_map('htmlspecialchars', $amenities_arr));
                        
                        $photos_arr = !empty($room['photos']) ? json_decode($room['photos'], true) : [];
                        $photo = (is_array($photos_arr) && !empty($photos_arr)) ? $photos_arr[0] : 'assets/images/room/room4.jpg';
                    ?>
                    <tr>
                        <td>
                            <div class="form-check style-1">
                              <input class="form-check-input" type="checkbox" value="">
                            </div>
                        </td>
                        <td>
                            <div class="room-list-bx d-flex align-items-center">
                                <img class="me-3 rounded" src="<?php echo htmlspecialchars($photo); ?>" alt="" style="width:50px; height:50px; object-fit:cover;">
                                <div>
                                    <span class="text-secondary fs-14 d-block">#R-<?php echo htmlspecialchars($room['id']); ?></span>
                                    <span class="fs-16 font-w500 text-nowrap"><?php echo htmlspecialchars($room['room_number']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="fs-16 font-w500 text-nowrap"><?php echo htmlspecialchars($room['room_type_id'] ?? 'Standard'); ?></span>
                        </td>
                        <td>
                            <div>
                                <span class="fs-16 font-w500"><?php echo htmlspecialchars($room['property_name']); ?></span>
                            </div>
                        </td>
                        <td class="facility">
                            <div>
                                <span class="fs-16 comments"><?php echo $amenities_str ? $amenities_str : 'Standard Amenities'; ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="">
                                <span class="mb-2">Rate</span>	
                                <span class="font-w500">TSh <?php echo number_format($room['price'] ?? 0); ?><small class="fs-14 ms-2">/night</small></span>
                            </div>
                        </td>
                        <td>
                            <span class="btn <?php echo (strtolower($room['status']) === 'available') ? 'btn-success' : 'btn-danger'; ?> btn-md text-uppercase"><?php echo htmlspecialchars($room['status']); ?></span>
                        </td>
                        <td>
                            <div class="dropdown dropend">
                                <a href="javascript:void(0);" class="btn-link" data-bs-toggle="dropdown" aria-expanded="false">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11 12C11 12.5523 11.4477 13 12 13C12.5523 13 13 12.5523 13 12C13 11.4477 12.5523 11 12 11C11.4477 11 11 11.4477 11 12Z" stroke="#262626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M18 12C18 12.5523 18.4477 13 19 13C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11C18.4477 11 18 11.4477 18 12Z" stroke="#262626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M4 12C4 12.5523 4.44772 13 5 13C5.55228 13 6 12.5523 6 12C6 11.4477 5.55228 11 5 11C4.44772 11 4 11.4477 4 12Z" stroke="#262626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="add-room.php?id=<?php echo $room['id']; ?>">Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
