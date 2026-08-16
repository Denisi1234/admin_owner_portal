<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$name = $_SESSION['user_name'] ?? 'User';
$email = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <title>Authentication Successful | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
    <?php include 'elements/meta.php';?>
    <link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
    <?php include 'elements/page-css.php'; ?>
    <style>
        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #eaf6f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #135846;
            font-size: 40px;
            box-shadow: 0 4px 15px rgba(19, 88, 70, 0.15);
            animation: bounceIn 0.8s ease;
        }
        .redirect-spinner {
            border: 3px solid rgba(19, 88, 70, 0.1);
            border-radius: 50%;
            border-top: 3px solid #135846;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); opacity: 0.8; }
            70% { transform: scale(0.9); opacity: 0.9; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="vh-100">
    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-5">
                    <div class="authincation-content" style="border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form p-5 text-center">
                                    <div class="success-checkmark">
                                        <i class="fa fa-check"></i>
                                    </div>
                                    <h3 class="font-w600 text-dark mb-2">Success!</h3>
                                    <h4 class="text-muted fs-16 mb-4">Welcome back, <?php echo htmlspecialchars($name); ?>!</h4>
                                    <p class="text-muted mb-4 fs-14">Your authentication was successful. We are routing you to your property management dashboard.</p>
                                    
                                    <div class="mt-4">
                                        <span class="redirect-spinner"></span>
                                        <span class="text-primary font-w500 fs-13">Redirecting to Dashboard...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Required vendors -->
    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/custom.min.js"></script>
    <script src="./js/dlabnav-init.js"></script>
    <script>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 2500);
    </script>
</body>
</html>
