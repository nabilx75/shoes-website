<?php
/**
 * StrideHub - Shared Header Template
 * Connects session, database, and yields a polished, premium aesthetic interface.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

$db = getDBConnection();
$db_connected = ($db !== null);

// Safe helper for current logged-in user
$currentUser = null;
if (isset($_SESSION['user_id']) && $db_connected) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
        // If user was blocked, force log out
        if ($currentUser && $currentUser['status'] === 'blocked') {
            session_destroy();
            $currentUser = null;
            header("Location: login.php?msg=Your account has been suspended.");
            exit();
        }
    } catch (PDOException $e) {}
}

// Fetch number of items in cart
$cartCount = 0;
if ($currentUser && $db_connected) {
    try {
        $stmt = $db->prepare("SELECT SUM(quantity) as qty FROM cart WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $currentUser['id']]);
        $res = $stmt->fetch();
        $cartCount = $res['qty'] ?? 0;
    } catch (PDOException $e) {}
} else {
    // Session based cart for guests
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cartCount += $item['quantity'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StrideHub | Premium Footwear</title>
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Space Grotesk for Headings, Inter for Body text -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #050505;
            color: #ffffff;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #000;
        }
        ::-webkit-scrollbar-thumb {
            background: #222;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #FF4E00;
        }

        /* High-Fidelity Micro-animations */
        @keyframes heartBlast {
            0% { transform: scale(1); }
            15% { transform: scale(1.35); }
            30% { transform: scale(0.95); }
            45% { transform: scale(1.45); }
            70% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        .animate-heart-blast {
            animation: heartBlast 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
        }

        .premium-card {
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .premium-card:hover {
            transform: translateY(-8px);
            border-color: rgba(255, 78, 0, 0.35) !important;
            box-shadow: 0 30px 60px -15px rgba(255, 78, 0, 0.15) !important;
            background-color: rgba(255, 255, 255, 0.015) !important;
        }
        .premium-card-image {
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .premium-card:hover .premium-card-image {
            transform: scale(1.08) rotate(1deg);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

<!-- NAVIGATION HEADER -->
<header class="sticky top-0 z-50 bg-black/95 border-b border-white/5 backdrop-blur-md px-6 py-4">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
        <!-- LOGO -->
        <a href="index.php" class="flex items-center gap-1.5 flex-shrink-0">
            <span class="text-xl font-extrabold italic tracking-tighter uppercase text-white">STRIDE<span class="text-[#FF4E00]">HUB</span></span>
        </a>

        <!-- NAVIGATION LINKS -->
        <nav class="hidden md:flex items-center gap-8 text-[11px] font-extrabold uppercase tracking-widest text-[#999] flex-shrink-0">
            <a href="index.php?gender=men#catalog-grid" class="text-white/80 hover:text-white transition-colors">Men</a>
            <a href="index.php?gender=women#catalog-grid" class="text-white/80 hover:text-white transition-colors">Women</a>
            <a href="index.php?limited=1#catalog-grid" class="text-white/80 hover:text-white transition-colors flex items-center gap-1">
                Limited Releases
            </a>
            <?php if ($currentUser): ?>
            <a href="wishlist.php" class="hover:text-[#FF4E00] transition-colors flex items-center gap-1.5 text-white/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-[#FF4E00]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg> 
                Wishlist
            </a>
            <?php endif; ?>
            <a href="orders.php" class="hover:text-white transition-colors">Orders</a>
            <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                <a href="admin.php" class="hover:text-amber-500 transition-colors text-amber-500 font-black">Admin</a>
            <?php endif; ?>
        </nav>

        <!-- RIGHT OPTIONS -->
        <div class="flex items-center gap-5 justify-end flex-grow md:flex-grow-0">
            <!-- INTEGRATED HEADER SEARCH BAR -->
            <form action="index.php#catalog-grid" method="GET" class="relative hidden sm:flex items-center w-40 md:w-56">
                <?php if (isset($filter_brand) && $filter_brand): ?><input type="hidden" name="brand" value="<?= $filter_brand ?>"><?php endif; ?>
                <?php if (isset($filter_category) && $filter_category): ?><input type="hidden" name="category" value="<?= $filter_category ?>"><?php endif; ?>
                <?php if (isset($filter_gender) && $filter_gender): ?><input type="hidden" name="gender" value="<?= $filter_gender ?>"><?php endif; ?>
                <?php if (isset($filter_limited) && $filter_limited): ?><input type="hidden" name="limited" value="<?= $filter_limited ?>"><?php endif; ?>
                
                <input 
                    type="text" 
                    name="q" 
                    placeholder="SEARCH KICKS..." 
                    value="<?= isset($filter_search) ? htmlspecialchars($filter_search) : '' ?>"
                    class="w-full bg-[#111] hover:bg-[#161616] border border-white/5 text-white placeholder-white/30 px-4 py-2 pl-9 text-[10px] uppercase font-bold tracking-wider focus:outline-none focus:border-[#FF4E00] rounded-full transition-all"
                >
                <div class="absolute left-3.5 text-white/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </form>

            <!-- SHOPPING CART -->
            <a href="cart.php" class="relative group p-1.5 hover:bg-white/5 rounded-full transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white/80 group-hover:text-[#FF4E00] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <?php if ($cartCount > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-[#FF4E00] text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                        <?= $cartCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- AUTH ACCOUNT ACTIONS -->
            <?php if ($currentUser): ?>
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-[9px] text-white/40 uppercase font-black tracking-widest">USER</span>
                        <span class="text-[11px] text-[#FF4E00] font-bold lowercase leading-none mt-0.5"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                    </div>
                    <a href="logout.php" class="border border-white/10 hover:border-red-500/50 hover:bg-red-950/20 text-white/70 hover:text-red-500 text-[10px] font-bold px-3.5 py-1.5 uppercase tracking-widest rounded-full transition-all">
                        Exit
                    </a>
                </div>
            <?php else: ?>
                <div class="flex items-center gap-2">
                    <a href="login.php" class="text-white/80 hover:text-white border border-white/20 text-[10px] font-bold px-4 py-2 uppercase tracking-widest rounded-full hover:bg-white/5 transition-all">
                        SIGN IN
                    </a>
                    <a href="register.php" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white text-[10px] font-bold px-4 py-2 uppercase tracking-widest rounded-full shadow-lg shadow-[#FF4E00]/20 transition-all">
                        SIGN UP
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (!$db_connected): ?>
    <!-- DATABASE CONNECTION ASSISTANCE GUIDANCE PANEL -->
    <div id="db-guide-banner" class="bg-amber-500/10 border-b border-amber-500/20 px-6 py-4 transition-all duration-300">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="bg-amber-500/15 border border-amber-500/35 text-amber-500 text-[9px] px-2.5 py-1 rounded font-black uppercase tracking-wider">
                        DATABASE OFFLINE (STATIC MODE ACTIVE)
                    </span>
                    <span class="text-[11px] text-white/50 font-medium">To connect your live XAMPP MySQL database, follow these quick steps:</span>
                </div>
                <ol class="list-decimal pl-4 text-white/70 space-y-1.5 mt-2 font-mono text-[11px] normal-case tracking-tight">
                    <li>Launch the <span class="text-amber-400 font-bold">XAMPP Control Panel</span> and click <span class="text-amber-400 font-bold">Start</span> next to both <span class="text-white underline">Apache</span> and <span class="text-white underline">MySQL</span>.</li>
                    <li>Navigate to your database dashboard on <a href="http://localhost/phpmyadmin/" target="_blank" class="text-amber-500 hover:underline font-bold text-xs uppercase bg-white/5 px-1.5 py-0.5 rounded">localhost/phpmyadmin</a>.</li>
                    <li>Create a new database exactly named <code class="text-amber-400 font-bold">stridehub</code>.</li>
                    <li>Click on the <strong class="text-white">Import</strong> tab at the top, select the <code class="text-amber-400 font-bold">database.sql</code> file located inside this directory, and click <strong class="text-white">Go</strong> or <strong class="text-white">Import</strong> at the bottom.</li>
                </ol>
            </div>
            <button onclick="document.getElementById('db-guide-banner').remove()" class="bg-white/5 hover:bg-white/10 text-white/60 hover:text-white border border-white/10 text-[9px] font-bold px-4 py-2 uppercase tracking-widest rounded transition-all whitespace-nowrap self-start md:self-center">
                Dismiss Guide ×
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- MAIN APP CANVAS BACKGROUND -->
<main class="flex-grow">
