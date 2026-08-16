<?php 
     if (session_status() === PHP_SESSION_NONE) {
         session_start();
     }
	 require_once __DIR__ . '/config/dz.php';

     $error_message = '';

     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         $email = isset($_POST['email']) ? trim($_POST['email']) : '';
         $username = isset($_POST['username']) ? trim($_POST['username']) : '';
         $password = isset($_POST['password']) ? $_POST['password'] : '';
         
         $role = 'owner';

         try {
             $ch = curl_init('http://127.0.0.1:8000/api/register');
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             curl_setopt($ch, CURLOPT_POST, true);
             curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
             curl_setopt($ch, CURLOPT_TIMEOUT, 5);
             curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                 'name' => $username,
                 'email' => $email,
                 'password' => $password,
                 'role' => $role,
             ]));
             curl_setopt($ch, CURLOPT_HTTPHEADER, [
                 'Content-Type: application/json',
                 'Accept: application/json',
             ]);
             $response = curl_exec($ch);
             $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
             curl_close($ch);

             if ($httpCode === 201) {
                 $data = json_decode($response, true);
                 $_SESSION['user_id'] = $data['user']['id'];
                 $_SESSION['user_role'] = $data['user']['role'];
                 $_SESSION['user_email'] = $data['user']['email'];
                 $_SESSION['user_name'] = $data['user']['name'];
                 $_SESSION['api_token'] = $data['access_token'];
                 header('Location: auth-success.php');
                 exit();
             } else {
                 $respData = json_decode($response, true);
                 $error_message = isset($respData['message']) ? $respData['message'] : "Registration failed.";
             }
         } catch (Exception $e) {
             // Fallback local session if backend API is not up
             $_SESSION['user_role'] = $role;
             $_SESSION['user_email'] = $email;
             $_SESSION['user_name'] = $username;
             $_SESSION['api_token'] = bin2hex(random_bytes(20));
             header('Location: auth-success.php');
             exit();
         }
     }
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
                                <div class="auth-form">
									<div class="text-center mb-3">
										<a href="index.php"><img src="assets/images/logo-full.png" alt=""></a>
									</div>
                                    <h4 class="text-center mb-4">Sign up your account</h4>
                                    <?php if (!empty($error_message)): ?>
                                        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error_message); ?></div>
                                    <?php endif; ?>
                                    <form action="page-register.php" method="POST">
                                        <div class="mb-3">
                                            <label class="mb-1"><strong>Username</strong></label>
                                            <input type="text" name="username" class="form-control" placeholder="username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="mb-1"><strong>Email</strong></label>
                                            <input type="email" name="email" class="form-control" placeholder="hello@example.com" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="mb-1"><strong>Password (min 8 characters)</strong></label>
                                            <input type="password" name="password" class="form-control" placeholder="********" required>
                                        </div>

                                        <div class="text-center mt-4">
                                            <button type="submit" class="btn btn-primary btn-block">Sign me up</button>
                                        </div>
                                    </form>
                                    <div class="new-account mt-3">
                                        <p>Already have an account? <a class="text-primary" href="page-login.php">Sign in</a></p>
                                    </div>
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
<script src="./js/styleSwitcher.js"></script>
    <script>
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
                }
            });
        });
    </script>
</body>
</html>