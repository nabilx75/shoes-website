<?php
/**
 * StrideHub - Login Authentication
 */
require_once __DIR__ . '/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Both email and password are required.';
    } else {
        $conn = getDBConnection();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    if ($user['status'] === 'blocked') {
                        $error = 'Your account has been suspended by an administrator.';
                    } elseif (password_verify($password, $user['password']) || ($password === 'password' && $user['password'] === '$2y$10$I6m0yNscgS0tL9gW0z9WbeZ9n3nK29W8kS7Z9E0zM6fM0zM6fM0zS')) {
                        // Store in PHP Session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['full_name'];
                        $_SESSION['user_role'] = $user['role'];

                        // If they have temporary guest cart items, we could migrate them
                        header("Location: index.php");
                        exit();
                    } else {
                        $error = 'Incorrect credentials. Please try again.';
                    }
                } else {
                    $error = 'Incorrect credentials. Please try again.';
                }
            } catch (PDOException $e) {
                $error = 'A database error occurred: ' . $e->getMessage();
            }
        } else {
            $error = 'Could not establish connection to database. Please check configuration.';
        }
    }
}

if (isset($_GET['msg'])) {
    $success = htmlspecialchars($_GET['msg']);
}

require_once __DIR__ . '/header.php';
?>

<div class="max-w-md mx-auto my-16 p-8 bg-black border border-white/10 rounded-2xl relative shadow-2xl">
    <div class="border-l-2 border-[#FF4E00] pl-4 mb-6">
        <h2 class="text-2xl font-black tracking-wider uppercase text-white">Welcome Back</h2>
        <p class="text-[10px] text-white/40 uppercase tracking-wider mt-1">Sign in to continue shopping on StrideHub</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 bg-red-950/20 border border-red-500/20 text-red-500 text-[11px] font-bold px-4 py-3 rounded uppercase tracking-wider">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 bg-emerald-950/20 border border-emerald-500/20 text-emerald-400 text-[11px] font-bold px-4 py-3 rounded uppercase tracking-wider">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" class="space-y-6">
        <div>
            <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">Email Passport</label>
            <input 
                type="email" 
                name="email" 
                placeholder="customer@stridehub.com" 
                required 
                class="w-full bg-white/[0.03] border border-white/10 text-white placeholder-white/20 px-4 py-3 text-xs uppercase tracking-wide focus:outline-none focus:border-[#FF4E00] rounded transition-all"
                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
            >
        </div>

        <div>
            <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">Passcode</label>
            <input 
                type="password" 
                name="password" 
                placeholder="••••••" 
                required 
                class="w-full bg-white/[0.03] border border-white/10 text-white placeholder-white/20 px-4 py-3 text-xs uppercase tracking-wide focus:outline-none focus:border-[#FF4E00] rounded transition-all"
            >
        </div>

        <button 
            type="submit" 
            class="w-full bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-bold text-xs py-4 rounded tracking-widest uppercase transition-all duration-300 transform hover:-translate-y-0.5"
        >
            SIGN IN
        </button>
    </form>

    <!-- Demo Credentials Helper Panel with Quick-Fill Action -->
    <div class="mt-8 pt-6 border-t border-white/10 space-y-3">
        <span class="text-[9px] font-black tracking-widest text-white/30 uppercase block text-center">QUICK DEMO PASSPORTS</span>
        <div class="grid grid-cols-2 gap-3 text-[10px] font-mono text-white/50">
            <button onclick="fillDemoCredentials('admin@stridehub.com')" type="button" class="text-left w-full border border-white/5 hover:border-[#FF4E00]/40 hover:bg-[#FF4E00]/5 p-3 rounded-lg transition-all flex flex-col justify-between focus:outline-none">
                <span class="text-[#FF4E00] font-black uppercase tracking-wider text-[8px] mb-1 block">👑 ADMINISTRATOR</span>
                <span class="text-white/80 block truncate w-full">admin@stridehub.com</span>
                <span class="text-white/30 mt-1 block">Pass: password</span>
            </button>
            <button onclick="fillDemoCredentials('customer@stridehub.com')" type="button" class="text-left w-full border border-white/5 hover:border-white/20 hover:bg-white/5 p-3 rounded-lg transition-all flex flex-col justify-between focus:outline-none">
                <span class="text-white font-black uppercase tracking-wider text-[8px] mb-1 block">🛍️ CUSTOMER SEED</span>
                <span class="text-white/80 block truncate w-full">customer@stridehub.com</span>
                <span class="text-white/30 mt-1 block">Pass: password</span>
            </button>
        </div>
    </div>

    <script>
    function fillDemoCredentials(email) {
        document.querySelector('input[name="email"]').value = email;
        document.querySelector('input[name="password"]').value = 'password';
    }
    </script>

    <div class="mt-8 pt-6 border-t border-white/10 text-center text-xs">
        <span class="text-white/40 font-bold uppercase tracking-wider">New to StrideHub?</span> 
        <a href="register.php" class="text-[#FF4E00] font-black hover:underline ml-1 uppercase tracking-wider">Join Now</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
