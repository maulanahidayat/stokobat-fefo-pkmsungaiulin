<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Obat FEFO PKM Sungai Ulin</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
     <!--   body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            position: relative;
            overflow: hidden;
        }
		-->
	body {
    font-family: 'Poppins', sans-serif;

    background-image:
        linear-gradient(
            rgba(17, 153, 142, 0.5),
            rgba(56, 239, 125, 0.5)
        ),
        url('assets/img/foto_bersama.jpg');

    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    background-attachment: fixed;

    width: 100%;
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0;
    padding: 20px;
    box-sizing: border-box;

    overflow-x: hidden;
}
        /* Background shapes */
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        .shape-1 { width: 300px; height: 300px; top: -100px; left: -100px; animation-delay: 0s; }
        .shape-2 { width: 400px; height: 400px; bottom: -150px; right: -100px; animation-delay: 2s; }
        .shape-3 { width: 150px; height: 150px; top: 30%; right: 10%; animation-delay: 4s; }
        
        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 15px;
            z-index: 10;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            padding: 40px 30px;
            position: relative;
        }
        .brand-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .brand-logo-icon {
            font-size: 45px;
            color: #11998e;
            background: #e8f5e9;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 10px 20px rgba(17, 153, 142, 0.15);
            margin-bottom: 15px;
        }
        .login-card h3 {
            font-weight: 700;
            color: #2d3748;
            font-size: 24px;
            margin-bottom: 5px;
            text-align: center;
        }
        .login-card p.subtitle {
            text-align: center;
            font-size: 14px;
            color: #718096;
            margin-bottom: 30px;
        }
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        .input-group-custom .form-control {
            padding: 14px 14px 14px 45px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 15px;
            font-weight: 400;
            color: #4a5568;
            transition: all 0.3s ease;
        }
        .input-group-custom .form-control:focus {
            border-color: #11998e;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(17, 153, 142, 0.1);
        }
        .input-group-custom i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 18px;
            transition: color 0.3s ease;
            pointer-events: none;
        }
        .input-group-custom .form-control:focus + i {
            color: #11998e;
        }
        .btn-login {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 8px 20px rgba(17, 153, 142, 0.3);
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(17, 153, 142, 0.4);
            color: #ffffff;
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .alert {
            border-radius: 12px;
            font-size: 14px;
            border: none;
            background-color: #fef2f2;
            color: #dc2626;
            display: flex;
            align-items: center;
            padding: 12px 15px;
        }
        .copyright {
            text-align: center;
            margin-top: 15px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Decorative background elements -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand-logo">
                <div class="brand-logo-icon">
                   <!-- <i class="fas fa-pills"></i> -->
				   <img src="assets/img/pkm_seul.png" alt="Logo" width="80">
                </div>
            </div>
            <h3>SIM Stok Obat</h3>
            <p class="subtitle">Sistem Manajemen Stok Obat FEFO Puskesmas Sungai Ulin</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert mb-4 pb-2 pt-2" role="alert">
                    <i class="fas fa-exclamation-circle me-2 fs-5"></i>
                    <div><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                </div>
            <?php endif; ?>

            <form action="auth.php" method="POST">
                
                <div class="input-group-custom">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan Username" required autocomplete="off">
                    <i class="fas fa-user"></i>
                </div>
                
                <div class="input-group-custom">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan Password" required>
                    <i class="fas fa-lock"></i>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Masuk Sekarang
                </button>
            </form>
        </div>
        <div class="copyright">
            &copy; <?= date('Y') ?> Puskesmas Sungai Ulin Persediaan FEFO Pengendalian Kadaluarsa Obat
        </div>
    </div>

    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
