<?php
/**
 * StrideHub - PHP Sneaker Catalog
 * The primary entrance which acts as catalog and hero display.
 */
require_once __DIR__ . '/db.php';

// Safe query fetching
$shoes = [];
$brands = [];
$categories = [];

$filter_brand = isset($_GET['brand']) ? (int)$_GET['brand'] : null;
$filter_category = isset($_GET['category']) ? (int)$_GET['category'] : null;
$filter_gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$filter_limited = isset($_GET['limited']) ? (int)$_GET['limited'] : 0;
$filter_search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Load lists from DB
$db = getDBConnection();
$using_fallback = false;

if ($db) {
    try {
        // Fetch brands & categories
        $brands = $db->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();
        $categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

        // Build conditional shoe query
        $query_str = "SELECT s.*, b.name as brand_name, c.name as category_name,
                      (SELECT image_url FROM shoe_images WHERE shoe_id = s.id AND is_primary = TRUE LIMIT 1) as primary_image
                      FROM shoes s
                      LEFT JOIN brands b ON s.brand_id = b.id
                      LEFT JOIN categories c ON s.category_id = c.id
                      WHERE s.is_active = TRUE";
        
        $params = [];
        if ($filter_brand) {
            $query_str .= " AND s.brand_id = :brand_id";
            $params[':brand_id'] = $filter_brand;
        }
        if ($filter_category) {
            $query_str .= " AND s.category_id = :category_id";
            $params[':category_id'] = $filter_category;
        }
        if ($filter_gender) {
            $query_str .= " AND s.gender = :gender";
            $params[':gender'] = $filter_gender;
        }
        if ($filter_limited) {
            $query_str .= " AND s.featured = 1";
        }
        if ($filter_search) {
            $query_str .= " AND (s.name LIKE :search OR s.description LIKE :search)";
            $params[':search'] = '%' . $filter_search . '%';
        }

        switch ($sort_by) {
            case 'price_low_high':
                $query_str .= " ORDER BY COALESCE(s.discount_price, s.price) ASC";
                break;
            case 'price_high_low':
                $query_str .= " ORDER BY COALESCE(s.discount_price, s.price) DESC";
                break;
            case 'best_selling':
                $query_str .= " ORDER BY s.rating_average DESC";
                break;
            case 'newest':
            default:
                $query_str .= " ORDER BY s.created_at DESC";
                break;
        }

        $stmt = $db->prepare($query_str);
        $stmt->execute($params);
        $shoes = $stmt->fetchAll();

    } catch (PDOException $e) {
        $using_fallback = true;
    }
} else {
    $using_fallback = true;
}

// Highly detailed static fallback if SQL is empty/not connected
if ($using_fallback || empty($shoes)) {
    $brands = [
        ['id' => 1, 'name' => 'Nike'],
        ['id' => 2, 'name' => 'Adidas'],
        ['id' => 3, 'name' => 'Puma'],
        ['id' => 4, 'name' => 'New Balance'],
        ['id' => 5, 'name' => 'Jordan']
    ];

    $categories = [
        ['id' => 1, 'name' => 'Running'],
        ['id' => 2, 'name' => 'Basketball'],
        ['id' => 3, 'name' => 'Sneakers'],
        ['id' => 4, 'name' => 'Casual'],
        ['id' => 5, 'name' => 'Boots']
    ];

    $shoes = [
        [
            'id' => 1,
            'name' => 'Velocity Aether Max',
            'brand_id' => 1,
            'brand_name' => 'Nike',
            'category_id' => 1,
            'category_name' => 'Running',
            'description' => 'A clean, high-performance running shoe built for everyday movement, maximum comfort, and effortless style.',
            'price' => 189.00,
            'discount_price' => 159.00,
            'gender' => 'unisex',
            'color' => 'Crimson Red / Slate Gray',
            'material' => 'Woven Suede Mesh',
            'rating_average' => 4.80,
            'featured' => 1,
            'primary_image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
        ],
        [
            'id' => 2,
            'name' => 'Sonic Rush G-2',
            'brand_id' => 2,
            'brand_name' => 'Adidas',
            'category_id' => 1,
            'category_name' => 'Running',
            'description' => 'Ultralight everyday runners designed with breathable fabrics and an elegant silhouette for all-day comfort.',
            'price' => 145.00,
            'discount_price' => null,
            'gender' => 'men',
            'color' => 'Carbon Cyan / Core Purple',
            'material' => 'Prime-Knit Mesh',
            'rating_average' => 4.50,
            'primary_image' => 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=600&q=80',
        ],
        [
            'id' => 3,
            'name' => 'Shadow Pulse 1.0',
            'brand_id' => 5,
            'brand_name' => 'Jordan',
            'category_id' => 2,
            'category_name' => 'Basketball',
            'description' => 'Premium ankle-support basketball sneakers featuring enhanced traction, supportive wrap, and premium materials.',
            'price' => 210.00,
            'discount_price' => 185.00,
            'gender' => 'men',
            'color' => 'Vantablack / Stealth Orange',
            'material' => 'Nylon Grid Mesh',
            'rating_average' => 4.90,
            'featured' => 1,
            'primary_image' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=600&q=80',
        ],
        [
            'id' => 4,
            'name' => 'Lunar Drift High-Top',
            'brand_id' => 5,
            'brand_name' => 'Jordan',
            'category_id' => 2,
            'category_name' => 'Basketball',
            'description' => 'A timeless high-top silhouette crafted from premium suede and leather for an elegant, elevated everyday look.',
            'price' => 165.00,
            'discount_price' => null,
            'gender' => 'women',
            'color' => 'Desert Sand / Cosmic Tan',
            'material' => 'Micro-Fiber Carbonate Suede',
            'rating_average' => 4.70,
            'primary_image' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80',
        ],
        [
            'id' => 5,
            'name' => 'Carbon Elite X',
            'brand_id' => 4,
            'brand_name' => 'New Balance',
            'category_id' => 1,
            'category_name' => 'Running',
            'description' => 'Exquisite marathon shoes pairing a featherlight frame with a comfortable carbon plate for effortless movement.',
            'price' => 299.00,
            'discount_price' => 260.00,
            'gender' => 'unisex',
            'color' => 'Titanium Tint / Neon Glow',
            'material' => 'Aeron Mesh',
            'rating_average' => 5.00,
            'featured' => 1,
            'primary_image' => 'https://images.unsplash.com/photo-1514989940723-e8e51635b782?auto=format&fit=crop&w=600&q=80',
        ],
        [
            'id' => 6,
            'name' => 'Urban Street Glide',
            'brand_id' => 3,
            'brand_name' => 'Puma',
            'category_id' => 3,
            'category_name' => 'Sneakers',
            'description' => 'Ultra-minimal skate-inspired sneakers with a low-padded collar, lightweight structure, and a sleek modern finish.',
            'price' => 95.00,
            'discount_price' => null,
            'gender' => 'unisex',
            'color' => 'Chalk White / Charcoal Suede',
            'material' => 'Premium Suede Wrap',
            'rating_average' => 4.30,
            'primary_image' => 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=600&q=80',
        ]
    ];

    // Simulate in-memory filters for fallback mode
    if ($filter_brand) {
        $shoes = array_filter($shoes, fn($s) => $s['brand_id'] == $filter_brand);
    }
    if ($filter_category) {
        $shoes = array_filter($shoes, fn($s) => $s['category_id'] == $filter_category);
    }
    if ($filter_gender) {
        $shoes = array_filter($shoes, fn($s) => strcasecmp($s['gender'], $filter_gender) === 0);
    }
    if ($filter_limited) {
        $shoes = array_filter($shoes, fn($s) => !empty($s['featured']));
    }
    if ($filter_search) {
        $shoes = array_filter($shoes, fn($s) => stripos($s['name'], $filter_search) !== false || stripos($s['description'], $filter_search) !== false);
    }
    if ($sort_by === 'price_low_high') {
        usort($shoes, fn($a, $b) => ($a['discount_price'] ?? $a['price']) <=> ($b['discount_price'] ?? $b['price']));
    } elseif ($sort_by === 'price_high_low') {
        usort($shoes, fn($a, $b) => ($b['discount_price'] ?? $b['price']) <=> ($a['discount_price'] ?? $a['price']));
    }
}

require_once __DIR__ . '/header.php';

// Fetch wishlisted item IDs if currentUser logged in
$user_wishlist_ids = [];
if ($currentUser && $db) {
    try {
        $w_stmt = $db->prepare("SELECT shoe_id FROM wishlist WHERE user_id = :uid");
        $w_stmt->execute([':uid' => $currentUser['id']]);
        $user_wishlist_ids = $w_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (PDOException $e) {}
}
?>

<!-- HERO ROW DROP AREA -->
<section class="relative bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-neutral-900 via-black to-black border-b border-white/5 py-16 md:py-24 px-6 overflow-hidden">
    <!-- Design Ambient Noise Grid -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#111_1px,transparent_1px),linear-gradient(to_bottom,#111_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] opacity-35"></div>
    
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between relative gap-12">
        <div class="max-w-2xl z-10">
            <span class="text-[10px] font-black tracking-[0.3em] text-[#FF4E00] uppercase mb-4 block">LIMITED RELEASE</span>
            <h1 class="text-5xl md:text-7xl font-black italic uppercase leading-none tracking-tighter mb-4 text-white">
                MOVE WITH<br/>
                <span class="text-transparent" style="-webkit-text-stroke: 1px rgba(255,255,255,0.2)">CONFIDENCE</span>
            </h1>
            <p class="text-white/65 text-sm max-w-md mb-8 leading-relaxed">
                Premium comfort, everyday movement, and effortless style. Designed for those who appreciate fine craftsmanship and timeless aesthetics.
            </p>
            <div class="flex items-center gap-6">
                <a href="#catalog-grid" class="bg-[#FF4E00] text-white font-black uppercase text-xs tracking-wider px-8 py-4 hover:bg-[#FF5D14] transition-all rounded">
                    EXPLORE DROP
                </a>
                <div class="flex flex-col">
                    <span class="text-white/30 text-[9px] uppercase font-bold tracking-widest">EXCLUSIVE PRICE</span>
                    <span class="text-2xl font-black tracking-tight text-white">$159.00 <span class="text-xs text-white/40 line-through font-normal">$189.00</span></span>
                </div>
            </div>
        </div>

        <!-- Hero graphic model display -->
        <div class="relative w-full max-w-md md:w-1/2 flex justify-center items-center">
            <div class="absolute w-72 h-72 bg-[#FF4E00]/10 rounded-full blur-3xl z-0"></div>
            <!-- Showcase Image -->
            <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80" alt="Featured sneakers" class="w-80 h-auto object-contain transform hover:scale-105 active:scale-95 duration-500 z-10 filter drop-shadow-[0_25px_25px_rgba(255,78,0,0.15)] pointer-events-none">
        </div>
    </div>
</section>

<!-- THE LIVE CATALOG SEGMENT -->
<section id="catalog-grid" class="max-w-7xl mx-auto px-6 py-12">
    
    <!-- COHESIVE TOP UTILITY CONTROL BAR -->
    <div class="mb-8 p-4 bg-white/[0.01] border-b border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
        <!-- Filter Toggle & Count Status -->
        <div class="flex items-center gap-4 w-full sm:w-auto">
            <button id="toggle-filter-btn" class="flex items-center gap-2 px-5 py-2.5 bg-black border border-white/10 hover:border-white text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-[#FF4E00]" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 18h4v-2h-4v2zM3 12v2h18v-2H3zm3-7v2h12V5H6z"/>
                </svg>
                <span>FILTERS</span>
                <span id="toggle-filter-arrow" class="text-[10px] text-white/60 ml-1">&gt;</span>
            </button>
            
            <div class="h-5 w-[1.5px] bg-[#FF4E00]"></div>
            
            <span class="text-xs font-bold tracking-[0.2em] uppercase text-white font-mono">
                SHOES CATALOG
            </span>
            
            <?php 
            $active_count = 0;
            if ($filter_brand) $active_count++;
            if ($filter_category) $active_count++;
            if ($filter_gender) $active_count++;
            if ($filter_limited) $active_count++;
            if ($filter_search) $active_count++;
            if ($active_count > 0):
            ?>
                <span class="text-[9px] bg-[#FF4E00]/15 border border-[#FF4E00]/30 text-[#FF4E00] px-2.5 py-1 rounded font-black tracking-widest uppercase ml-2 animate-pulse">
                    <?= $active_count ?> ACTIVE
                </span>
            <?php endif; ?>
        </div>

        <!-- Quick Sort Dropdown aligned right -->
        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <span class="text-[10px] text-white/40 uppercase font-black tracking-widest hidden lg:block">Sort By</span>
            <form action="index.php#catalog-grid" method="GET" class="flex items-center w-full sm:w-auto">
                <?php if ($filter_brand): ?><input type="hidden" name="brand" value="<?= $filter_brand ?>"><?php endif; ?>
                <?php if ($filter_category): ?><input type="hidden" name="category" value="<?= $filter_category ?>"><?php endif; ?>
                <?php if ($filter_gender): ?><input type="hidden" name="gender" value="<?= $filter_gender ?>"><?php endif; ?>
                <?php if ($filter_limited): ?><input type="hidden" name="limited" value="<?= $filter_limited ?>"><?php endif; ?>
                <?php if ($filter_search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($filter_search) ?>"><?php endif; ?>
                <select 
                    name="sort" 
                    onchange="this.form.submit()"
                    class="bg-[#111] border border-white/5 text-white text-xs px-3.5 py-2 rounded-lg focus:outline-none focus:border-[#FF4E00] uppercase font-bold tracking-wider cursor-pointer w-full sm:w-auto"
                >
                    <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                    <option value="price_low_high" <?= $sort_by === 'price_low_high' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_high_low" <?= $sort_by === 'price_high_low' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="best_selling" <?= $sort_by === 'best_selling' ? 'selected' : '' ?>>Best Sellers</option>
                </select>
            </form>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">
        <!-- FILTER SIDEBAR with id for smooth toggling -->
        <aside id="filter-sidebar" class="w-full lg:w-64 flex-shrink-0 bg-black/40 border border-white/5 rounded-2xl p-6 self-start space-y-8 transition-all duration-300">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <span class="text-xs font-black uppercase tracking-widest text-[#FF4E00] flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                        <path d="M10 18h4v-2h-4v2zM3 12v2h18v-2H3zm3-7v2h12V5H6z"/>
                    </svg>
                    FILTERS
                </span>
                <div class="flex items-center gap-2">
                    <a href="?brand=&category=&gender=&limited=0&q=&sort=<?= $sort_by ?>#catalog-grid" class="text-[10px] font-black text-white/50 hover:text-[#FF4E00] uppercase tracking-widest transition-colors">
                        RESET
                    </a>
                    <button onclick="document.getElementById('toggle-filter-btn').click()" class="text-white/45 hover:text-white pb-0.5 font-bold pl-2 border-l border-white/10 select-none cursor-pointer">
                        ✕
                    </button>
                </div>
            </div>

            <!-- BRANDS -->
            <div class="space-y-3">
                <h3 class="text-[10px] uppercase tracking-widest font-black text-white/40">Brands</h3>
                <div class="flex flex-wrap gap-1.5">
                    <a href="?brand=&category=<?= $filter_category ?>&gender=<?= $filter_gender ?>&limited=<?= $filter_limited ?>&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                       class="text-[9px] font-black uppercase px-2.5 py-1.5 rounded transition-all border <?= !$filter_brand ? 'border-[#FF4E00] bg-[#FF4E00] text-white' : 'border-white/5 bg-[#111] hover:bg-neutral-900 text-white/70' ?>">
                        ALL BRANDS
                    </a>
                    <?php foreach ($brands as $b): ?>
                        <a href="?brand=<?= $b['id'] ?>&category=<?= $filter_category ?>&gender=<?= $filter_gender ?>&limited=<?= $filter_limited ?>&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                           class="text-[9px] font-black uppercase px-2.5 py-1.5 rounded transition-all border <?= $filter_brand == $b['id'] ? 'border-[#FF4E00] bg-[#FF4E00] text-white' : 'border-white/5 bg-[#111] hover:bg-neutral-900 text-white/70' ?>">
                            <?= htmlspecialchars($b['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ATHLETIC CATEGORIES -->
            <div class="space-y-3">
                <h3 class="text-[10px] uppercase tracking-widest font-black text-white/40">Category</h3>
                <div class="flex flex-wrap gap-1.5">
                    <a href="?brand=<?= $filter_brand ?>&category=&gender=<?= $filter_gender ?>&limited=<?= $filter_limited ?>&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                       class="text-[9px] font-black uppercase px-2.5 py-1.5 rounded transition-all border <?= !$filter_category ? 'border-[#FF4E00] bg-[#FF4E00] text-white' : 'border-white/5 bg-[#111] hover:bg-neutral-900 text-white/70' ?>">
                        ALL CATEGORIES
                    </a>
                    <?php foreach ($categories as $c): ?>
                        <a href="?brand=<?= $filter_brand ?>&category=<?= $c['id'] ?>&gender=<?= $filter_gender ?>&limited=<?= $filter_limited ?>&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                           class="text-[9px] font-black uppercase px-2.5 py-1.5 rounded transition-all border <?= $filter_category == $c['id'] ? 'border-[#FF4E00] bg-[#FF4E00] text-white' : 'border-white/5 bg-[#111] hover:bg-neutral-900 text-white/70' ?>">
                            <?= htmlspecialchars($c['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- GENDER -->
            <div class="space-y-3">
                <h3 class="text-[10px] uppercase tracking-widest font-black text-white/40">Gender</h3>
                <div class="flex flex-wrap gap-1.5">
                    <a href="?brand=<?= $filter_brand ?>&category=<?= $filter_category ?>&gender=&limited=<?= $filter_limited ?>&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                       class="text-[9px] font-black uppercase px-2.5 py-1.5 rounded transition-all border <?= !$filter_gender ? 'border-[#FF4E00] bg-[#FF4E00] text-white' : 'border-white/5 bg-[#111] hover:bg-neutral-900 text-white/70' ?>">
                        ALL
                    </a>
                    <a href="?brand=<?= $filter_brand ?>&category=<?= $filter_category ?>&gender=men&limited=<?= $filter_limited ?>&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                       class="text-[9px] font-black uppercase px-2.5 py-1.5 rounded transition-all border <?= $filter_gender === 'men' ? 'border-[#FF4E00] bg-[#FF4E00] text-white' : 'border-white/5 bg-[#111] hover:bg-neutral-900 text-white/70' ?>">
                        MEN
                    </a>
                    <a href="?brand=<?= $filter_brand ?>&category=<?= $filter_category ?>&gender=women&limited=<?= $filter_limited ?>&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                       class="text-[9px] font-black uppercase px-2.5 py-1.5 rounded transition-all border <?= $filter_gender === 'women' ? 'border-[#FF4E00] bg-[#FF4E00] text-white' : 'border-white/5 bg-[#111] hover:bg-neutral-900 text-white/70' ?>">
                        WOMEN
                    </a>
                    <a href="?brand=<?= $filter_brand ?>&category=<?= $filter_category ?>&gender=unisex&limited=<?= $filter_limited ?>&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                       class="text-[9px] font-black uppercase px-2.5 py-1.5 rounded transition-all border <?= $filter_gender === 'unisex' ? 'border-[#FF4E00] bg-[#FF4E00] text-white' : 'border-white/5 bg-[#111] hover:bg-neutral-900 text-white/70' ?>">
                        UNISEX
                    </a>
                </div>
            </div>

            <!-- COLLECTION -->
            <div class="space-y-3">
                <h3 class="text-[10px] uppercase tracking-widest font-black text-white/40">Collection</h3>
                <div class="flex flex-col gap-1.5">
                    <a href="?brand=<?= $filter_brand ?>&category=<?= $filter_category ?>&gender=<?= $filter_gender ?>&limited=0&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                       class="text-[9px] font-black uppercase px-3 py-2 rounded transition-all border text-center <?= !$filter_limited ? 'border-[#FF4E00] bg-[#FF4E00] text-white' : 'border-white/5 bg-[#111] hover:bg-neutral-900 text-white/70' ?>">
                        STANDARD RELEASES
                    </a>
                    <a href="?brand=<?= $filter_brand ?>&category=<?= $filter_category ?>&gender=<?= $filter_gender ?>&limited=1&q=<?= urlencode($filter_search) ?>&sort=<?= $sort_by ?>#catalog-grid" 
                       class="text-[9px] font-black uppercase px-3 py-2 rounded transition-all border flex items-center justify-center gap-1.5 <?= $filter_limited ? 'border-amber-500 bg-amber-500 text-black' : 'border-white/5 bg-[#111] hover:bg-[#161616] text-white/70' ?>">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse <?= $filter_limited ? 'bg-black' : '' ?>"></span>
                        LIMITED EDITION
                    </a>
                </div>
            </div>
        </aside>

        <!-- SNEAKER CARDS GRID -->
        <div class="flex-grow">
            <?php if (empty($shoes)): ?>
                <div class="text-center py-24 bg-black/40 border border-white/5 rounded-2xl">
                    <p class="text-xs uppercase font-black text-white/35 tracking-widest">No Premium sneakers match your selection.</p>
                </div>
            <?php else: ?>
                <!-- Gri container has id to scale to grid-cols-4 when filter sidebar is toggled hide -->
                <div id="sneaker-grid-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 transition-all duration-300">
                    <?php foreach ($shoes as $shoe): ?>
                        <div onclick="if (!event.target.closest('.wishlist-toggle-btn')) { window.location.href='product.php?id=<?= $shoe['id'] ?>'; }" class="premium-card cursor-pointer bg-black/80 border border-white/10 rounded-2xl overflow-hidden flex flex-col group relative">
                            <!-- Overlay Brand Name tag -->
                            <div class="absolute top-4 left-4 z-10 bg-black/60 border border-white/10 text-[9px] px-2.5 py-1 rounded font-bold uppercase tracking-widest text-[#FF4E00]">
                                <?= htmlspecialchars($shoe['brand_name'] ?? 'StrideHub') ?>
                            </div>

                            <!-- Wishlist / Like Toggle Button (Top-Right) -->
                            <?php if ($currentUser): ?>
                            <button 
                                type="button" 
                                data-shoe-id="<?= $shoe['id'] ?>" 
                                class="wishlist-toggle-btn absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-black/40 border border-white/10 text-white/50 hover:text-[#FF4E00] flex items-center justify-center transition-all duration-300 focus:outline-none pointer-events-auto"
                                aria-label="Add to Wishlist"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300 hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </button>
                            <?php endif; ?>

                            <!-- Clickable Product details image container -->
                            <div class="w-full bg-neutral-900 border-b border-white/5 overflow-hidden relative aspect-square p-6 flex items-center justify-center">
                                <img 
                                    src="<?= htmlspecialchars($shoe['primary_image'] ?? 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80') ?>" 
                                    alt="<?= htmlspecialchars($shoe['name']) ?>" 
                                    class="premium-card-image max-h-48 max-w-full object-contain duration-500 filter drop-shadow-[0_15px_15px_rgba(0,0,0,0.5)] animate-fade-in"
                                    referrerpolicy="no-referrer"
                                >
                            </div>

                            <!-- Sneaker Specs Details card body -->
                            <div class="p-5 flex-grow flex flex-col justify-between">
                                <div>
                                    <span class="text-[9px] font-bold text-white/30 uppercase tracking-widest block mb-1">
                                        <?= htmlspecialchars($shoe['category_name'] ?? 'Footwear') ?> &bull; <?= htmlspecialchars($shoe['gender']) ?>
                                    </span>
                                    <h2 class="text-base font-black uppercase tracking-wide group-hover:text-[#FF4E00] transition-colors leading-tight mb-2">
                                        <?= htmlspecialchars($shoe['name']) ?>
                                    </h2>
                                    <p class="text-[11px] text-white/50 leading-relaxed line-clamp-2 h-8 mb-4">
                                        <?= htmlspecialchars($shoe['description'] ?? 'Curated sneaker limited custom line.') ?>
                                    </p>
                                </div>

                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex flex-col">
                                        <?php if (!empty($shoe['discount_price'])): ?>
                                            <span class="text-sm font-bold text-white/35 line-through font-normal">$<?= number_format($shoe['price'], 2) ?></span>
                                            <span class="text-lg font-black text-[#FF4E00] tracking-tight">$<?= number_format($shoe['discount_price'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-lg font-black text-white tracking-tight">$<?= number_format($shoe['price'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="bg-white/5 group-hover:bg-[#FF4E00] border border-white/10 group-hover:border-[#FF4E00]/20 text-white/70 group-hover:text-white p-2.5 rounded-full transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- LOCAL SIDEBAR TOGGLE & WISHLIST MECHANISM -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggle-filter-btn');
    const sidebar = document.getElementById('filter-sidebar');
    const gridContainer = document.getElementById('sneaker-grid-container');
    const toggleArrow = document.getElementById('toggle-filter-arrow');

    if (toggleBtn && sidebar) {
        // Retrieve persistent preference state from user session local storage
        const isHidden = localStorage.getItem('stridehub_filters_hidden') === 'true';
        if (isHidden) {
            applyHiddenState();
        } else {
            applyShownState();
        }

        toggleBtn.addEventListener('click', () => {
            const currentlyHidden = sidebar.classList.contains('hidden');
            if (currentlyHidden) {
                applyShownState();
                localStorage.setItem('stridehub_filters_hidden', 'false');
            } else {
                applyHiddenState();
                localStorage.setItem('stridehub_filters_hidden', 'true');
            }
        });
    }

    function applyHiddenState() {
        if (sidebar) sidebar.classList.add('hidden');
        if (gridContainer) {
            gridContainer.classList.remove('lg:grid-cols-3');
            gridContainer.classList.add('lg:grid-cols-4');
        }
        if (toggleArrow) toggleArrow.textContent = '>';
    }

    function applyShownState() {
        if (sidebar) sidebar.classList.remove('hidden');
        if (gridContainer) {
            gridContainer.classList.remove('lg:grid-cols-4');
            gridContainer.classList.add('lg:grid-cols-3');
        }
        if (toggleArrow) toggleArrow.textContent = '<';
    }

    // --- Dynamic Database Wishlist / Likes System ---
    const wishlistButtons = document.querySelectorAll('.wishlist-toggle-btn');
    const isUserLoggedIn = <?php echo json_encode($currentUser !== null); ?>;
    
    // Loaded directly from DB query
    let storedWishlist = <?php echo json_encode($user_wishlist_ids); ?>;

    function updateHeartsUI() {
        if (!isUserLoggedIn) {
            // Guests don't show filled hearts to prevent confusing state
            return;
        }
        wishlistButtons.forEach(btn => {
            const shoeId = parseInt(btn.getAttribute('data-shoe-id'), 10);
            const isLiked = storedWishlist.includes(shoeId);
            const svg = btn.querySelector('svg');
            
            if (isLiked) {
                btn.classList.remove('text-white/50', 'bg-black/40', 'border-white/10');
                btn.classList.add('text-[#FF4E00]', 'bg-rose-950/20', 'border-[#FF4E00]/65');
                svg.setAttribute('fill', 'currentColor');
                svg.classList.add('text-[#FF4E00]');
            } else {
                btn.classList.add('text-white/50', 'bg-black/40', 'border-white/10');
                btn.classList.remove('text-[#FF4E00]', 'bg-rose-950/20', 'border-[#FF4E00]/65');
                svg.setAttribute('fill', 'none');
                svg.classList.remove('text-[#FF4E00]');
            }
        });
    }

    updateHeartsUI();

    wishlistButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            
            if (!isUserLoggedIn) {
                window.location.href = 'login.php?msg=' + encodeURIComponent('Please sign in or register to like shoes and build your wishlist.');
                return;
            }
            
            const shoeId = parseInt(btn.getAttribute('data-shoe-id'), 10);
            
            // Toggle in database via AJAX
            fetch('toggle_wishlist.php?shoe_id=' + shoeId, { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'removed') {
                            const idx = storedWishlist.indexOf(shoeId);
                            if (idx > -1) {
                                storedWishlist.splice(idx, 1);
                            }
                        } else {
                            if (!storedWishlist.includes(shoeId)) {
                                storedWishlist.push(shoeId);
                            }
                            
                            // High fidelity heartbeat animation triggering
                            btn.classList.add('animate-heart-blast');
                            setTimeout(() => {
                                btn.classList.remove('animate-heart-blast');
                            }, 510);
                        }
                        updateHeartsUI();
                    } else {
                        console.error(data.error || 'Wishlist update failed.');
                    }
                })
                .catch(err => {
                    console.error('Network failure updating wishlist:', err);
                });
        });
    });
});
</script>

<!-- CURATED BRAND PARTNERS SHOWCASE STRIP -->
<section class="max-w-7xl mx-auto px-6 py-8">
    <div class="bg-white/[0.01] border border-white/5 p-6 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wide">CURATED BRAND SELECTION</h4>
            <p class="text-[10px] text-white/40 mt-0.5 uppercase">Direct authentic lines from the world's leading footwear houses.</p>
        </div>
        <div class="flex flex-wrap gap-8 items-center justify-center">
            <span class="text-xs font-black italic tracking-widest text-white/20 select-none uppercase">NIKE</span>
            <span class="text-xs font-black italic tracking-widest text-white/20 select-none uppercase">ADIDAS</span>
            <span class="text-xs font-black italic tracking-widest text-white/20 select-none uppercase">PUMA</span>
            <span class="text-xs font-black italic tracking-widest text-white/20 select-none">NEW BALANCE</span>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
