<?php 
	 require_once __DIR__ . '/config/dz.php';
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
   <!-- PAGE TITLE HERE -->
	<title><?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
	<?php include 'elements/meta.php';?>
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
	<?php include 'elements/page-css.php'; ?>
</head>

<body class="vh-100">
    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form" id="forgot-password-step1">
									<div class="text-center mb-3">
										<a href="index.php"><img src="assets/images/logo-full.png" alt=""></a>
									</div>
                                    <h4 class="text-center mb-4">Forgot Password</h4>
                                    <form id="forgot-form" action="javascript:void(0);">
                                        <div class="mb-3">
                                            <label><strong>Email Address</strong></label>
                                            <input type="email" id="forgot-email" class="form-control" placeholder="delivered@resend.dev" required>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary btn-block" id="btn-send-code">SEND RESET CODE</button>
                                        </div>
                                    </form>
                                    <div class="new-account mt-3 text-center">
                                        <p>Remember your password? <a class="text-primary" href="page-login.php">Sign in</a></p>
                                    </div>
                                </div>

                                <div class="auth-form" id="forgot-password-step2" style="display: none;">
									<div class="text-center mb-3">
										<a href="index.php"><img src="assets/images/logo-full.png" alt=""></a>
									</div>
                                    <h4 class="text-center mb-4">Reset Password</h4>
                                    <p class="text-center text-muted" style="font-size: 13px;">Please enter the 6-digit code sent to your email.</p>
                                    <form id="reset-form" action="javascript:void(0);">
                                        <div class="mb-3">
                                            <label><strong>Reset Code</strong></label>
                                            <input type="text" id="reset-token" class="form-control" placeholder="123456" required>
                                        </div>
                                        <div class="mb-3">
                                            <label><strong>New Password</strong></label>
                                            <input type="password" id="reset-password" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label><strong>Confirm New Password</strong></label>
                                            <input type="password" id="reset-password-confirm" class="form-control" required>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary btn-block" id="btn-reset-pw">RESET PASSWORD</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/custom.min.js"></script>
    <script src="./js/dlabnav-init.js"></script>
    
    <script>
        const API_BASE = 'local-api-reset.php?action=';
        let savedEmail = '';

        document.getElementById('forgot-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('forgot-email').value.trim();
            const btn = document.getElementById('btn-send-code');
            
            // UX: Show spinner
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending Code...';

            try {
                const res = await fetch(`${API_BASE}forgot-password`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email })
                });
                const data = await res.json();
                
                if (res.ok) {
                    savedEmail = email;
                    
                    // Smooth transition to step 2
                    document.getElementById('forgot-password-step1').style.display = 'none';
                    const step2 = document.getElementById('forgot-password-step2');
                    step2.style.opacity = 0;
                    step2.style.display = 'block';
                    setTimeout(() => step2.style.opacity = 1, 50);
                    
                } else {
                    // UX: Show friendly error
                    alert('Could not send code: ' + (data.message || 'Please check if your email is registered.'));
                }
            } catch (err) {
                alert('Network error. Our database servers might be temporarily busy, please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'SEND RESET CODE';
            }
        });

        document.getElementById('reset-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const token = document.getElementById('reset-token').value.trim();
            const password = document.getElementById('reset-password').value;
            const password_confirmation = document.getElementById('reset-password-confirm').value;
            const btn = document.getElementById('btn-reset-pw');

            if (password !== password_confirmation) {
                alert('Passwords do not match');
                return;
            }

            // UX: Show spinner
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Resetting...';

            try {
                const res = await fetch(`${API_BASE}reset-password`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        email: savedEmail,
                        token,
                        password,
                        password_confirmation
                    })
                });
                const data = await res.json();
                
                if (res.ok) {
                    // UX: Success alert and redirect
                    alert('Success! Your password has been reset. You can now log in.');
                    window.location.href = 'page-login.php';
                } else {
                    alert('Error: ' + (data.message || 'Invalid token or request.'));
                }
            } catch (err) {
                alert('Network error. Our database servers might be temporarily busy, please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'RESET PASSWORD';
            }
        });
    </script>
	<script src="./js/styleSwitcher.js"></script>
</body>
</html>