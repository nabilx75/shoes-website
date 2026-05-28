<?php
/**
 * StrideHub - PHP Product Detail and Review Section
 * Displays size configuration, description, and client review feed.
 */
require_once __DIR__ . '/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$shoe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$shoe_id) {
    header("Location: index.php");
    exit();
}

$shoe = null;
$reviews = [];
$sizes = [
    ['id' => 101, 'size' => '40', 'stock_quantity' => 15],
    ['id' => 102, 'size' => '41', 'stock_quantity' => 20],
    ['id' => 103, 'size' => '42', 'stock_quantity' => 24],
    ['id' => 104, 'size' => '43', 'stock_quantity' => 8],
    ['id' => 105, 'size' => '44', 'stock_quantity' => 0] // Sold out example
];

$db = getDBConnection();
$using_fallback = false;

if ($db) {
    try {
        // Fetch detailed shoe info matching tables
        $stmt = $db->prepare("SELECT s.*, b.name as brand_name, c.name as category_name 
                              FROM shoes s
                              LEFT JOIN brands b ON s.brand_id = b.id
                              LEFT JOIN categories c ON s.category_id = c.id
                              WHERE s.id = :id AND s.is_active = TRUE LIMIT 1");
        $stmt->execute([':id' => $shoe_id]);
        $shoe = $stmt->fetch();

        if ($shoe) {
            // Fetch reviews
            $rev_stmt = $db->prepare("SELECT r.*, u.full_name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.shoe_id = :shoe_id ORDER BY r.created_at DESC");
            $rev_stmt->execute([':shoe_id' => $shoe_id]);
            $reviews = $rev_stmt->fetchAll();

            // Fetch shoe sizes
            $sz_stmt = $db->prepare("SELECT * FROM shoe_sizes WHERE shoe_id = :shoe_id ORDER BY size ASC");
            $sz_stmt->execute([':shoe_id' => $shoe_id]);
            $db_sizes = $sz_stmt->fetchAll();
            if (!empty($db_sizes)) {
                $sizes = $db_sizes;
            }

            // Fetch multiple shoe images
            $img_stmt = $db->prepare("SELECT image_url, is_primary FROM shoe_images WHERE shoe_id = :shoe_id ORDER BY is_primary DESC, id ASC");
            $img_stmt->execute([':shoe_id' => $shoe_id]);
            $sho_imgs = $img_stmt->fetchAll();
            $shoe_images_arr = [];
            foreach ($sho_imgs as $img) {
                $shoe_images_arr[] = $img['image_url'];
            }
            if (empty($shoe_images_arr)) {
                $shoe_images_arr[] = $shoe['primary_image'] ?? 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80';
            }
            $shoe['images'] = $shoe_images_arr;
        } else {
            $using_fallback = true;
        }
    } catch (PDOException $e) {
        $using_fallback = true;
    }
} else {
    $using_fallback = true;
}

// Fallback dummy shoe data matching details
if ($using_fallback || !$shoe) {
    // Basic static fallback catalog lookup
    $static_catalog = [
        1 => [
            'id' => 1,
            'name' => 'Velocity Aether Max',
            'brand_name' => 'Nike',
            'category_name' => 'Running',
            'description' => 'A clean, high-performance running shoe built for everyday movement, maximum comfort, and effortless style.',
            'price' => 189.00,
            'discount_price' => 159.00,
            'gender' => 'unisex',
            'color' => 'Crimson Red / Slate Gray',
            'material' => 'Woven Suede Mesh',
            'rating_average' => 4.80,
            'primary_image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=600&q=80'
            ]
        ],
        2 => [
            'id' => 2,
            'name' => 'Sonic Rush G-2',
            'brand_name' => 'Adidas',
            'category_name' => 'Running',
            'description' => 'Ultralight everyday runners designed with breathable fabrics and an elegant silhouette for all-day comfort.',
            'price' => 145.00,
            'discount_price' => null,
            'gender' => 'men',
            'color' => 'Carbon Cyan / Core Purple',
            'material' => 'Prime-Knit Mesh',
            'rating_average' => 4.50,
            'primary_image' => 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=600&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=600&q=80'
            ]
        ],
        3 => [
            'id' => 3,
            'name' => 'Shadow Pulse 1.0',
            'brand_name' => 'Jordan',
            'category_name' => 'Basketball',
            'description' => 'Premium ankle-support basketball sneakers featuring enhanced traction, supportive wrap, and premium materials.',
            'price' => 210.00,
            'discount_price' => 185.00,
            'gender' => 'men',
            'color' => 'Vantablack / Stealth Orange',
            'material' => 'Nylon Grid Mesh',
            'rating_average' => 4.90,
            'primary_image' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=600&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=600&q=80'
            ]
        ],
        4 => [
            'id' => 4,
            'name' => 'Lunar Drift High-Top',
            'brand_name' => 'Jordan',
            'category_name' => 'Basketball',
            'description' => 'A timeless high-top silhouette crafted from premium suede and leather for an elegant, elevated everyday look.',
            'price' => 165.00,
            'discount_price' => null,
            'gender' => 'women',
            'color' => 'Desert Sand / Cosmic Tan',
            'material' => 'Micro-Fiber Carbonate Suede',
            'rating_average' => 4.70,
            'primary_image' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=600&q=80'
            ]
        ],
        5 => [
            'id' => 5,
            'name' => 'Carbon Elite X',
            'brand_name' => 'New Balance',
            'category_name' => 'Running',
            'description' => 'Exquisite marathon shoes pairing a featherlight frame with a comfortable carbon plate for effortless movement.',
            'price' => 299.00,
            'discount_price' => 260.00,
            'gender' => 'unisex',
            'color' => 'Titanium Tint / Neon Glow',
            'material' => 'Aeron Mesh',
            'rating_average' => 5.00,
            'primary_image' => 'https://images.unsplash.com/photo-1514989940723-e8e51635b782?auto=format&fit=crop&w=600&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1514989940723-e8e51635b782?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80'
            ]
        ],
        6 => [
            'id' => 6,
            'name' => 'Urban Street Glide',
            'brand_name' => 'Puma',
            'category_name' => 'Sneakers',
            'description' => 'Ultra-minimal skate-inspired sneakers with a low-padded collar, lightweight structure, and a sleek modern finish.',
            'price' => 95.00,
            'discount_price' => null,
            'gender' => 'unisex',
            'color' => 'Chalk White / Charcoal Suede',
            'material' => 'Premium Suede Wrap',
            'rating_average' => 4.30,
            'primary_image' => 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=600&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=600&q=80',
                'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80'
            ]
        ]
    ];

    $shoe = isset($static_catalog[$shoe_id]) ? $static_catalog[$shoe_id] : $static_catalog[1];
    
    // Fallback static reviews
    $reviews = [
        ['id' => 1, 'user_name' => 'Marcus S.', 'rating' => 5, 'comment' => 'Exquisite craftsmanship. Combining look with everyday versatility. Fully recommend!', 'created_at' => '2026-05-15'],
        ['id' => 2, 'user_name' => 'Sarah McC.', 'rating' => 4, 'comment' => 'Very comfortable padding around ankles. Perfect fit for daily exercise walks.', 'created_at' => '2026-05-20']
    ];
}

// Process new review submission
$review_error = '';
$review_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $review_error = 'Please sign in to write a review.';
    } else {
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');
        $user_id = $_SESSION['user_id'];

        if ($rating < 1 || $rating > 5) {
            $review_error = 'Invalid rating range (1-5stars).';
        } else {
            $db = getDBConnection();
            if ($db) {
                try {
                    // Check duplicate
                    $chk = $db->prepare("SELECT id FROM reviews WHERE user_id = :u AND shoe_id = :s LIMIT 1");
                    $chk->execute([':u' => $user_id, ':s' => $shoe_id]);
                    if ($chk->fetch()) {
                        $review_error = 'You have already reviewed this sneaker model.';
                    } else {
                        $stmt = $db->prepare("INSERT INTO reviews (user_id, shoe_id, rating, comment) VALUES (:u, :s, :r, :c)");
                        $stmt->execute([
                            ':u' => $user_id,
                            ':s' => $shoe_id,
                            ':r' => $rating,
                            ':c' => htmlspecialchars($comment)
                        ]);
                        $review_success = 'Review posted successfully. Thank you for your feedback.';
                        // Refresh reviews list
                        $rev_stmt = $db->prepare("SELECT r.*, u.full_name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.shoe_id = :shoe_id ORDER BY r.created_at DESC");
                        $rev_stmt->execute([':shoe_id' => $shoe_id]);
                        $reviews = $rev_stmt->fetchAll();
                    }
                } catch (PDOException $e) {
                    $review_error = 'Error saving review: ' . $e->getMessage();
                }
            } else {
                $review_error = 'Writing reviews to live DB skipped in static fallback mode.';
            }
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-12">
    <!-- Back to Catalog Link button -->
    <a href="index.php" class="inline-flex items-center gap-2 text-xs font-black uppercase text-white/50 hover:text-[#FF4E00] tracking-widest mb-10 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        BACK TO CATALOG
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- PRODUCT IMAGES VIEW WITH GALLERY ON THE LEFT -->
        <div class="space-y-6">
            <div class="flex flex-row gap-4 items-start">
                <!-- Left-side thumbnails list -->
                <?php 
                $images = isset($shoe['images']) ? $shoe['images'] : [$shoe['primary_image']];
                ?>
                <div class="flex flex-col gap-2.5 w-16 md:w-20 shrink-0">
                    <?php foreach ($images as $idx => $img_url): ?>
                        <div 
                            onmouseover="swapProductImage('<?= htmlspecialchars($img_url) ?>', this)"
                            onclick="swapProductImage('<?= htmlspecialchars($img_url) ?>', this)"
                            class="thumbnail-container border rounded-xl overflow-hidden aspect-square flex items-center justify-center bg-neutral-950 cursor-pointer p-1.5 transition-all duration-200 <?= $idx === 0 ? 'border-[#FF4E00] ring-1 ring-[#FF4E00]' : 'border-white/10 hover:border-white/30' ?>"
                        >
                            <img 
                                src="<?= htmlspecialchars($img_url) ?>" 
                                alt="Visual thumbnail <?= $idx + 1 ?>" 
                                class="max-h-full max-w-full object-contain filter drop-shadow hover:scale-105 transition-all"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Main dynamic image element on the right -->
                <div class="flex-1 bg-neutral-900 border border-white/5 rounded-3xl flex items-center justify-center p-8 aspect-square relative overflow-hidden group">
                    <div class="absolute w-72 h-72 bg-[#FF4E00]/5 rounded-full blur-3xl z-0"></div>
                    <img 
                        id="main_product_image"
                        src="<?= htmlspecialchars($images[0] ?? 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80') ?>" 
                        alt="<?= htmlspecialchars($shoe['name']) ?>" 
                        class="max-h-72 max-w-full object-contain filter drop-shadow-[0_20px_20px_rgba(0,0,0,0.6)] group-hover:scale-105 duration-500 z-10"
                    >
                </div>
            </div>

            <!-- SIZING GUIDE ADVICE CARD -->
            <div class="p-5 bg-white/[0.01] border border-white/5 rounded-2xl text-xs space-y-2">
                <span class="font-bold uppercase tracking-wider text-[#FF4E00] block">SIZING GUIDE</span>
                <p class="text-white/40 leading-relaxed normal-case text-xs">
                    StrideHub fits correspond perfectly to standard European athletic specifications. We recommend selecting your true size, or sizing up half a size if you prefer a wider, more relaxed fit.
                </p>
            </div>
        </div>

        <!-- PRODUCT SPECIFICS FORM INFO -->
        <div class="space-y-8">
            <div>
                <span class="text-xs font-bold text-white/40 tracking-widest uppercase block mb-1">
                    <?= htmlspecialchars($shoe['category_name'] ?? 'FOOTWEAR') ?> &bull; <?= htmlspecialchars($shoe['gender']) ?>'S
                </span>
                <h1 class="text-3xl md:text-5xl font-black uppercase tracking-tight text-white leading-tight">
                    <?= htmlspecialchars($shoe['name']) ?>
                </h1>
                <p class="text-xs text-white/50 mt-2 flex items-center gap-1.5 capitalize">
                    <span>Colorway: <?= htmlspecialchars($shoe['color']) ?></span>
                    <span class="text-white/20">&bull;</span>
                    <span>Material: <?= htmlspecialchars($shoe['material'] ?? 'Premium Mesh Yarn') ?></span>
                </p>
            </div>

            <!-- SNEAKER PRICES -->
            <div class="flex items-center gap-4">
                <?php if (!empty($shoe['discount_price'])): ?>
                    <span class="text-3xl font-black text-[#FF4E00] tracking-tight">$<?= number_format($shoe['discount_price'], 2) ?></span>
                    <span class="text-sm font-semibold text-white/30 line-through mt-2">$<?= number_format($shoe['price'], 2) ?></span>
                    <span class="bg-[#FF4E00]/10 border border-[#FF4E00]/25 text-[#FF4E00] text-[9px] font-bold px-2.5 py-0.5 rounded uppercase tracking-widest mt-1">Limited Promotion</span>
                <?php else: ?>
                    <span class="text-3xl font-black text-white tracking-tight">$<?= number_format($shoe['price'], 2) ?></span>
                <?php endif; ?>
            </div>

            <!-- DESCRIPTION -->
            <p class="text-xs text-white/70 leading-relaxed uppercase tracking-wide normal-case bg-black border border-white/5 p-4 rounded-xl leading-relaxed">
                <?= htmlspecialchars($shoe['description'] ?? 'Exclusive StrideHub footwear crafted with performance foam cushions and premium materials for lightweight propulsive actions.') ?>
            </p>

            <!-- SHOPPING CART ADD FORM -->
            <form action="cart.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="shoe_id" value="<?= $shoe['id'] ?>">

                <!-- SIZE SELECTOR Grid cards -->
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-white/40 block mb-3">SELECT SIZE</span>
                    <div class="grid grid-cols-4 md:grid-cols-5 gap-3">
                        <?php foreach ($sizes as $idx => $sz): ?>
                            <?php $inStock = $sz['stock_quantity'] > 0; ?>
                            <label class="relative block">
                                <input 
                                    type="radio" 
                                    name="size_id" 
                                    value="<?= $sz['id'] ?>" 
                                    <?= $inStock ? 'required' : 'disabled' ?>
                                    class="peer sr-only"
                                >
                                <div class="cursor-pointer border select-none text-xs text-center py-3.5 font-bold uppercase transition-all rounded duration-150
                                    <?= $inStock 
                                        ? 'border-white/15 hover:border-white/50 text-white hover:bg-white/5 peer-checked:bg-[#FF4E00] peer-checked:border-[#FF4E00] peer-checked:text-white' 
                                        : 'border-white/5 text-white/15 bg-black cursor-not-allowed line-through' ?>"
                                >
                                    <?= htmlspecialchars($sz['size']) ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SUBMIT PURCHASE QUANTITY -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                    <div class="flex items-center border border-white/10 rounded overflow-hidden">
                        <span class="text-[9px] font-black tracking-widest pl-4 text-white/40 uppercase">QTY</span>
                        <input 
                            type="number" 
                            name="quantity" 
                            value="1" 
                            min="1" 
                            max="5"
                            required
                            class="bg-transparent text-white font-bold text-center w-16 py-3.5 focus:outline-none"
                        >
                    </div>

                    <?php if ($currentUser): ?>
                        <button 
                            type="submit" 
                            class="flex-grow bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-black text-xs py-4 rounded tracking-widest uppercase transition-all flex items-center justify-center gap-3 transform hover:-translate-y-0.5 active:translate-y-0 shadow-lg shadow-[#FF4E00]/10"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            ADD TO BAG
                        </button>
                    <?php else: ?>
                        <a 
                            href="login.php?msg=<?= urlencode("Please sign in or register to add items to your shopping bag.") ?>"
                            class="flex-grow bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-black text-xs py-4 rounded tracking-widest uppercase transition-all flex items-center justify-center gap-3 transform hover:-translate-y-0.5 active:translate-y-0 text-center shadow-lg shadow-[#FF4E00]/10"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            SIGN IN TO ADD TO BAG
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- PEER REVIEW INVENTORY FEED -->
            <div class="border-t border-white/10 pt-8 mt-12 space-y-6">
                <span class="text-xs font-black uppercase tracking-widest text-[#FF4E00] flex items-center gap-1.5 mb-4">
                    ★ REVIEWS
                </span>

                <div class="space-y-4 max-h-72 overflow-y-auto pr-2">
                    <?php if (empty($reviews)): ?>
                        <p class="text-[10px] text-white/30 uppercase font-semibold">No reviews yet.</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                            <div class="p-4 border border-white/5 bg-black/60 rounded-xl relative space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="text-[11px] font-bold text-white/80"><?= htmlspecialchars($rev['user_name'] ?? 'Buyer') ?></span>
                                    <span class="text-amber-500 text-[10px]"><?= str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']) ?></span>
                                </div>
                                <p class="text-xs text-white/70 italic leading-relaxed normal-case">
                                    "<?= htmlspecialchars($rev['comment']) ?>"
                                </p>
                                <span class="text-[8px] text-white/35 font-bold uppercase block"><?= date('Y-m-d', strtotime($rev['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- SUBMIT NEW REVIEW -->
                <div class="pt-6 border-t border-white/5">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form action="product.php?id=<?= $shoe['id'] ?>" method="POST" class="space-y-4">
                            <h3 class="text-[10px] uppercase tracking-widest font-black text-white/40 block">Write a Review</h3>

                            <?php if ($review_error): ?>
                                <p class="text-[9px] font-bold text-red-500 uppercase tracking-widest"><?= $review_error ?></p>
                            <?php endif; ?>
                            <?php if ($review_success): ?>
                                <p class="text-[9px] font-bold text-emerald-400 uppercase tracking-widest"><?= $review_success ?></p>
                            <?php endif; ?>

                            <div class="flex items-center gap-1">
                                <span class="text-[10px] text-white/40 font-bold uppercase tracking-wider mr-2">Rating:</span>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="<?= $i ?>" required <?= $i == 5 ? 'checked' : '' ?> class="sr-only peer">
                                        <span class="text-white/20 hover:text-amber-500 peer-checked:text-amber-500 active:text-amber-400 font-bold text-lg select-none">★</span>
                                    </label>
                                <?php endfor; ?>
                            </div>

                            <textarea 
                                name="comment" 
                                placeholder="Write your review here..." 
                                required 
                                rows="3"
                                class="w-full bg-white/[0.02] border border-white/15 text-white placeholder-white/20 p-3.5 text-xs focus:outline-none focus:border-[#FF4E00] rounded-lg transition-all"
                            ></textarea>

                            <button 
                                type="submit" 
                                name="submit_review"
                                class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-bold text-[9px] tracking-widest px-6 py-2.5 rounded uppercase transition-colors"
                            >
                                POST REVIEW
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="text-[10px] text-[#FF4E00] font-black uppercase tracking-widest leading-loose text-center py-2 border border-white/5 bg-[#FF4E00]/5 rounded">
                            Sign in to write a review.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function swapProductImage(src, element) {
    const mainImg = document.getElementById('main_product_image');
    if (mainImg) {
        mainImg.src = src;
    }
    // Highlight thumbnail
    document.querySelectorAll('.thumbnail-container').forEach(thumb => {
        thumb.classList.remove('border-[#FF4E00]', 'ring-1', 'ring-[#FF4E00]');
        thumb.classList.add('border-white/10');
    });
    if (element) {
        element.classList.add('border-[#FF4E00]', 'ring-1', 'ring-[#FF4E00]');
        element.classList.remove('border-white/10');
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
