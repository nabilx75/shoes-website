<?php
/**
 * StrideHub - Registration
 */
require_once __DIR__ . '/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Passcode must be at least 6 characters.';
    } else {
        $conn = getDBConnection();
        if ($conn) {
            try {
                // Check if already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                
                if ($stmt->fetch()) {
                    $error = 'An account with this email address already exists.';
                } else {
                    // Safe hash Password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Insert
                    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, role, status) VALUES (:full_name, :email, :password, :phone, 'customer', 'active')");
                    $stmt->execute([
                        ':full_name' => htmlspecialchars($full_name),
                        ':email' => htmlspecialchars($email),
                        ':password' => $hashed_password,
                        ':phone' => htmlspecialchars($phone)
                    ]);
                    
                    header("Location: login.php?msg=Registration complete! Please sign in with your credentials.");
                    exit();
                }
            } catch (PDOException $e) {
                $error = 'A database error occurred: ' . $e->getMessage();
            }
        } else {
            $error = 'Could not connect to the database.';
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="max-w-md mx-auto my-16 p-8 bg-black border border-white/10 rounded-2xl relative shadow-2xl">
    <div class="border-l-2 border-[#FF4E00] pl-4 mb-6">
        <h2 class="text-2xl font-black tracking-wider uppercase text-white">Create Your Account</h2>
        <p class="text-[10px] text-white/40 uppercase tracking-wider mt-1">Join StrideHub and discover curated premium footwear</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 bg-red-950/20 border border-red-500/20 text-red-500 text-[11px] font-bold px-4 py-3 rounded uppercase tracking-wider">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST" class="space-y-5">
        <div>
            <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">Full Name</label>
            <input 
                type="text" 
                name="full_name" 
                placeholder="e.g. John Doe" 
                required 
                class="w-full bg-white/[0.03] border border-white/10 text-white placeholder-white/20 px-4 py-3 text-xs uppercase tracking-wide focus:outline-none focus:border-[#FF4E00] rounded transition-all"
                value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>"
            >
        </div>

        <div>
            <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">Email Passport</label>
            <input 
                type="email" 
                name="email" 
                placeholder="e.g. johndoe@example.com" 
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
                placeholder="Minimum 6 characters" 
                required 
                class="w-full bg-white/[0.03] border border-white/10 text-white placeholder-white/20 px-4 py-3 text-xs uppercase tracking-wide focus:outline-none focus:border-[#FF4E00] rounded transition-all"
            >
        </div>

        <div>
            <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">Phone Number (Optional)</label>
            <input 
                type="tel" 
                name="phone" 
                placeholder="e.g. +1 555-0199" 
                class="w-full bg-white/[0.03] border border-white/10 text-white placeholder-white/20 px-4 py-3 text-xs uppercase tracking-wide focus:outline-none focus:border-[#FF4E00] rounded transition-all"
                value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
            >
        </div>

        <button 
            type="submit" 
            class="w-full bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-bold text-xs py-4 rounded tracking-widest uppercase transition-all duration-300 transform hover:-translate-y-0.5"
        >
            JOIN STRIDEHUB
        </button>
    </form>

    <div class="mt-8 pt-6 border-t border-white/10 text-center text-xs">
        <span class="text-white/40 font-bold uppercase tracking-wider">Already a member?</span> 
        <a href="login.php" class="text-[#FF4E00] font-black hover:underline ml-1 uppercase tracking-wider">Sign In</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
