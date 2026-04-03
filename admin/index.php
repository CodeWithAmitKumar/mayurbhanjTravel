<?php
session_start();
include '../config.php';

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $sql = "SELECT * FROM admins WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['username'] = $row['username'];
            header('Location: welcome.php');
            exit();
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'Invalid username';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin Login - Mayurbhanj Tourism Planner</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body style="margin: 0; padding: 0; font-family: 'Nunito', sans-serif; background: linear-gradient(rgba(20, 20, 31, 0.9), rgba(20, 20, 31, 0.9)), url(img/bg-hero.jpg); background-size: cover; background-position: center; min-height: 100vh; display: flex; align-items: center; justify-content: center;">

    <div style="width: 100%; max-width: 420px; padding: 20px;">
        <div style="background: #ffffff; border-radius: 10px; box-shadow: 0 0 50px rgba(0, 0, 0, 0.3); overflow: hidden;">
            <!-- Header Section -->
            <div style="background: #86B817; padding: 30px 30px 25px; text-align: center;">
                <div style="width: 70px; height: 70px; background: #ffffff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
                    <i class="fa fa-map-marker-alt" style="color: #86B817; font-size: 30px;"></i>
                </div>
                <h2 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700;">Admin Panel</h2>
                <p style="color: rgba(255, 255, 255, 0.8); margin: 8px 0 0; font-size: 14px;">Mayurbhanj Tourism Planner</p>
            </div>

            <!-- Login Form -->
            <div style="padding: 35px 30px 30px;">
                <h4 style="color: #14141F; margin: 0 0 25px; font-size: 18px; font-weight: 600; text-align: center;">Welcome Back</h4>
                <p style="color: #666666; margin: 0 0 25px; font-size: 13px; text-align: center;">Please sign in to continue</p>

                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; border: 1px solid #f5c6cb;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" style="margin: 0;">
                    <!-- Username Field -->
                    <div style="margin-bottom: 20px;">
                        <label for="username" style="display: block; color: #14141F; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Username</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #86B817;">
                                <i class="fa fa-user"></i>
                            </span>
                            <input type="text" id="username" name="username" required 
                                style="width: 100%; padding: 12px 15px 12px 45px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 14px; font-family: 'Nunito', sans-serif; box-sizing: border-box; transition: all 0.3s ease; outline: none;"
                                onfocus="this.style.borderColor='#86B817'; this.style.boxShadow='0 0 0 3px rgba(134, 184, 23, 0.1)';"
                                onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div style="margin-bottom: 20px;">
                        <label for="password" style="display: block; color: #14141F; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Password</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #86B817;">
                                <i class="fa fa-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" required 
                                style="width: 100%; padding: 12px 15px 12px 45px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 14px; font-family: 'Nunito', sans-serif; box-sizing: border-box; transition: all 0.3s ease; outline: none;"
                                onfocus="this.style.borderColor='#86B817'; this.style.boxShadow='0 0 0 3px rgba(134, 184, 23, 0.1)';"
                                onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
                            <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #999999; cursor: pointer;" onclick="togglePassword()">
                                <i class="fa fa-eye" id="eyeIcon"></i>
                            </span>
                        </div>
                    </div>

                   

                    <!-- Login Button -->
                    <button type="submit" name="login" 
                        style="width: 100%; padding: 14px; background: #86B817; color: #ffffff; border: none; border-radius: 5px; font-size: 16px; font-weight: 700; font-family: 'Nunito', sans-serif; cursor: pointer; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px;"
                        onmouseover="this.style.background='#6a9612'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 20px rgba(134, 184, 23, 0.4)';"
                        onmouseout="this.style.background='#86B817'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        Sign In
                    </button>
                </form>

                <!-- Back to Home Link -->
                <div style="margin-top: 25px; text-align: center; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                    <a href="../index.php" style="color: #666666; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa fa-arrow-left" style="color: #86B817;"></i>
                        Back to Website
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Text -->
        <p style="text-align: center; margin-top: 20px; color: rgba(255, 255, 255, 0.6); font-size: 12px;">
            &copy; 2026 Mayurbhanj Tourism Planner. All Rights Reserved.
        </p>
    </div>

    <script>
        function togglePassword() {
            var passwordInput = document.getElementById('password');
            var eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>