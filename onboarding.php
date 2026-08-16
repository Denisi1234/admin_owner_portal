<?php 
require_once __DIR__ . '/api/onboarding-handler.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Lodge Onboarding Wizard | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
    <?php include 'elements/meta.php';?>
    <link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
    <?php include 'elements/page-css.php'; ?>
    <!-- Mapbox GL JS CDN -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css" rel="stylesheet" />
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js"></script>
    <style>
        .step-container {
            display: none;
        }
        .step-container.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #eee;
            z-index: 1;
            transform: translateY(-50%);
        }
        .step-indicator .step {
            z-index: 2;
            background: #fff;
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid #eee;
            font-weight: 600;
            color: #888;
            transition: all 0.3s;
            font-size: 13px;
        }
        .step-indicator .step.active {
            border-color: #135846;
            color: #135846;
            box-shadow: 0 0 10px rgba(19, 88, 70, 0.15);
        }
        .step-indicator .step.completed {
            background: #135846;
            border-color: #135846;
            color: #fff;
        }
        .upload-dropzone {
            border: 2px dashed #135846;
            background: #f4f9f7;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-dropzone:hover {
            background: #eef7f3;
            border-color: #1a7e65;
        }
        .preview-card {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            height: 100px;
            background: #eee;
        }
        .preview-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-card .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .preview-card .upload-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: #135846;
            width: 0%;
        }
    </style>
</head>
<body>
    <?php include 'elements/pre-loader.php'; ?>

    <div id="main-wrapper">
        <?php include 'elements/nav-header.php'; ?>
        <?php include 'elements/chatbox.php'; ?>
        <?php include 'elements/header.php'; ?>
        <?php include 'elements/sidebar.php'; ?>

        <div class="content-body">
            <div class="container-fluid">
                <!-- Onboarding Card -->
                <div class="card shadow-sm border-0" style="border-radius: 16px;">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="fs-22 font-w600 text-black mb-1">Onboard Your Property</h3>
                            <p class="text-muted mb-0">Follow our simplified wizard to register your lodge and rooms.</p>
                        </div>
                        <span class="badge badge-success fs-14 py-2 px-3" id="wizardProgressBadge">Step 1 of 7</span>
                    </div>

                    <div class="card-body px-4 py-4">
                        <!-- Step Indicators -->
                        <div class="step-indicator d-none d-lg-flex mb-4">
                            <div class="step active" id="ind-1">1. Lodge Info</div>
                            <div class="step" id="ind-2">2. Details & Policies</div>
                            <div class="step" id="ind-3">3. Location</div>
                            <div class="step" id="ind-4">4. Photos & Amenities</div>
                            <div class="step" id="ind-5">5. Room Management</div>
                            <div class="step" id="ind-6">6. Review & Submit</div>
                        </div>

                        <!-- Onboarding Wizard Steps -->
                        <?php
                        include 'elements/onboarding-step-1.php';
                        include 'elements/onboarding-step-2.php';
                        include 'elements/onboarding-step-3.php';
                        include 'elements/onboarding-step-4.php';
                        include 'elements/onboarding-step-5.php';
                        include 'elements/onboarding-room-editor.php';
                        include 'elements/onboarding-step-6.php';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'elements/footer.php'; ?>
    </div>

    <?php include 'elements/onboarding-bulk-modal.php'; ?>

    <?php include 'elements/page-js.php'; ?>

    <script>
        window.ONBOARDING_CONFIG = {
            apiToken: <?php echo json_encode($_SESSION['api_token'] ?? ''); ?>
        };
    </script>
    <script src="assets/js/custom/onboarding-wizard.js"></script>
</body>
</html>
