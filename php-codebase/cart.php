<?php
/**
 * StrideHub - Shopping Bag
 * Handles items addition, quantity deletion, and coupon discount promo codes.
 */
require_once __DIR__ . '/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure the standard Session Cart exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$db = getDBConnection();
$currentUser = null;
if (isset($_SESSION['user_id']) && $db) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
    } catch (PDOException $e) {}
}

$toast_message = '';

// ACTION PREPARATION
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$currentUser) {
        header("Location: login.php?msg=" . urlencode("Please sign in or register to add items to your shopping bag."));
        exit();
    }
    $shoe_id = (int)$_POST['shoe_id'];
    $size_id = (int)$_POST['size_id'];
    $qty = (int)$_POST['quantity'];
    if ($qty < 1) $qty = 1;

    // Fetch details to record
    $shoe_name = 'StrideHub Sneaker';
    $shoe_price = 159.00;
    $shoe_discount = null;
    $shoe_size_label = '42';
    $shoe_image_url = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80';

    if ($db) {
        try {
            // Check original DB items
            $sh_stmt = $db->prepare("SELECT s.*, (SELECT image_url FROM shoe_images WHERE shoe_id = s.id AND is_primary = TRUE LIMIT 1) as primary_image FROM shoes s WHERE s.id = :id");
            $sh_stmt->execute([':id' => $shoe_id]);
            $sh = $sh_stmt->fetch();
            if ($sh) {
                $shoe_name = $sh['name'];
                $shoe_price = $sh['price'];
                $shoe_discount = $sh['discount_price'];
                if ($sh['primary_image']) $shoe_image_url = $sh['primary_image'];
            }
            $sz_stmt = $db->prepare("SELECT size FROM shoe_sizes WHERE id = :size_id");
            $sz_stmt->execute([':size_id' => $size_id]);
            $sz = $sz_stmt->fetch();
            if ($sz) $shoe_size_label = $sz['size'];

            // Insert to DB cart if user is authenticated
            if ($currentUser) {
                // Check if already exist
                $chk = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = :u AND shoe_id = :s AND size_id = :z LIMIT 1");
                $chk->execute([':u' => $currentUser['id'], ':s' => $shoe_id, ':z' => $size_id]);
                $chk_row = $chk->fetch();

                if ($chk_row) {
                    $new_qty = $chk_row['quantity'] + $qty;
                    $up = $db->prepare("UPDATE cart SET quantity = :qty WHERE id = :id");
                    $up->execute([':qty' => $new_qty, ':id' => $chk_row['id']]);
                } else {
                    $ins = $db->prepare("INSERT INTO cart (user_id, shoe_id, size_id, quantity) VALUES (:u, :s, :z, :qty)");
                    $ins->execute([':u' => $currentUser['id'], ':s' => $shoe_id, ':z' => $size_id, ':qty' => $qty]);
                }
            }
        } catch (PDOException $e) {}
    }

    // Always maintain Session bag fallback for quick client use cases
    $cart_key = $shoe_id . '-' . $size_id;
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['quantity'] += $qty;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'shoe_id' => $shoe_id,
            'size_id' => $size_id,
            'name' => $shoe_name,
            'price' => $shoe_price,
            'discount_price' => $shoe_discount,
            'size' => $shoe_size_label,
            'image' => $shoe_image_url,
            'quantity' => $qty
        ];
    }
    $toast_message = "Sneaker added to your bag!";
}

// REMOVE ROW
if ($action === 'delete') {
    $cart_key = isset($_GET['key']) ? $_GET['key'] : '';
    unset($_SESSION['cart'][$cart_key]);

    if ($currentUser && $db && isset($_GET['cart_id'])) {
        try {
            $del = $db->prepare("DELETE FROM cart WHERE id = :cid AND user_id = :uid");
            $del->execute([':cid' => (int)$_GET['cart_id'], ':uid' => $currentUser['id']]);
        } catch (PDOException $e) {}
    }
    $toast_message = "Sneaker removed from your bag.";
}

// APPLY PROMO COUPON VOUCHER
$discount_percent = 0;
if (isset($_POST['apply_coupon'])) {
    $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    if ($code === 'OFF10') {
        $_SESSION['coupon'] = ['code' => 'OFF10', 'discount' => 10];
        $toast_message = "Voucher code applied: 10% Discount!";
    } elseif ($code === 'SUMMER20') {
        $_SESSION['coupon'] = ['code' => 'SUMMER20', 'discount' => 20];
        $toast_message = "Voucher code applied: 20% Discount!";
    } elseif ($code === 'SNEAKERNEW') {
        $_SESSION['coupon'] = ['code' => 'SNEAKERNEW', 'discount' => 15];
        $toast_message = "Explorer code applied: 15% Discount!";
    } else {
        $toast_message = "Invalid or expired promo code.";
    }
}

if (isset($_SESSION['coupon'])) {
    $discount_percent = $_SESSION['coupon']['discount'];
}

// READ RECONCILED CART LISTS
$cartItems = [];
if ($currentUser && $db) {
    try {
        $stmt = $db->prepare("SELECT c.id as cart_id, c.shoe_id, c.size_id, c.quantity, s.name, s.price, s.discount_price, sz.size,
                              (SELECT image_url FROM shoe_images WHERE shoe_id = s.id AND is_primary = TRUE LIMIT 1) as primary_image
                              FROM cart c
                              LEFT JOIN shoes s ON c.shoe_id = s.id
                              LEFT JOIN shoe_sizes sz ON c.size_id = sz.id
                              WHERE c.user_id = :user_id");
        $stmt->execute([':user_id' => $currentUser['id']]);
        $db_items = $stmt->fetchAll();
        
        foreach ($db_items as $item) {
            $cartItems[] = [
                'cart_id' => $item['cart_id'],
                'shoe_id' => $item['shoe_id'],
                'size_id' => $item['size_id'],
                'name' => $item['name'] ?? 'StrideHub Sneaker',
                'price' => $item['price'] ?? 159.00,
                'discount_price' => $item['discount_price'],
                'size' => $item['size'] ?? '42',
                'image' => $item['primary_image'] ?? 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
                'quantity' => $item['quantity'],
                'key' => $item['shoe_id'] . '-' . $item['size_id']
            ];
        }
    } catch (PDOException $e) {
        // Fallback to session items
        $cartItems = array_values($_SESSION['cart']);
    }
} else {
    // Session list for guests
    foreach ($_SESSION['cart'] as $k => $item) {
        $item['key'] = $k;
        $cartItems[] = $item;
    }
}

// TOTAL CALCULATIONS
$subtotal = 0;
foreach ($cartItems as $c) {
    $unit_price = $c['discount_price'] !== null ? $c['discount_price'] : $c['price'];
    $subtotal += $unit_price * $c['quantity'];
}

$discount_amount = $subtotal * ($discount_percent / 100);
$shipping_cost = ($subtotal > 150 || $subtotal == 0) ? 0.00 : 15.00;
$total_amount = $subtotal - $discount_amount + $shipping_cost;

require_once __DIR__ . '/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-12">
    <!-- Header visual margin title -->
    <div class="border-l-2 border-[#FF4E00] pl-4 mb-10">
        <h1 class="text-3xl md:text-5xl font-black uppercase tracking-tight text-white flex items-center gap-1">YOUR BAG</h1>
        <p class="text-[10px] text-white/40 uppercase tracking-widest mt-1">Review your premium selections and apply promotion keys</p>
    </div>

    <?php if ($toast_message): ?>
        <div class="mb-8 bg-neutral-900 border border-white/10 text-white text-[11px] font-black px-5 py-4 rounded-xl uppercase tracking-widest flex items-center justify-between">
            <span>Notification: <?= $toast_message ?></span>
            <button onclick="this.parentElement.remove()" class="text-white/40 hover:text-white">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="text-center py-24 bg-black border border-white/5 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white/20 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <p class="text-xs uppercase font-black text-white/35 tracking-widest mb-6">Your shopping bag is empty.</p>
            <a href="index.php" class="bg-[#FF4E00] text-white font-bold text-[10px] px-6 py-3 uppercase tracking-widest hover:bg-[#FF5D14] rounded transition-all">
                Shop Arrivals
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- ITEMS COL -->
            <div class="lg:col-span-2 space-y-4">
                <?php foreach ($cartItems as $item): ?>
                    <?php $item_unit_price = $item['discount_price'] !== null ? $item['discount_price'] : $item['price']; ?>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between bg-black border border-white/10 rounded-2xl p-5 gap-4 hover:border-white/20 transition-all">
                        <div class="flex items-center gap-5">
                            <div class="w-20 h-20 bg-neutral-900 rounded-xl overflow-hidden p-3 flex items-center justify-center flex-shrink-0 border border-white/5">
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="max-h-full max-w-full object-contain filter drop-shadow">
                            </div>
                            <div>
                                <h3 class="text-sm font-black uppercase text-white tracking-wide hover:text-[#FF4E00] transition-colors">
                                    <a href="product.php?id=<?= $item['shoe_id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                                </h3>
                                <div class="text-[10px] font-bold text-white/40 uppercase mt-1 space-x-3">
                                    <span>SIZE: <span class="text-white"><?= htmlspecialchars($item['size']) ?></span></span>
                                    <span>&bull;</span>
                                    <span>QTY: <span class="text-white"><?= htmlspecialchars($item['quantity']) ?></span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Price and removal -->
                        <div class="flex sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto border-t sm:border-t-0 border-white/5 pt-3 sm:pt-0">
                            <span class="text-sm font-black text-white px-2 py-1">$<?= number_format($item_unit_price * $item['quantity'], 2) ?></span>
                            <a href="?action=delete&key=<?= $item['key'] ?><?= isset($item['cart_id']) ? '&cart_id='.$item['cart_id'] : '' ?>" class="text-[9px] font-bold text-red-500 hover:underline uppercase tracking-widest select-none p-2 block sm:-mr-2">
                                Remove
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- LEDGER CHECKOUT INFO SUMMARY -->
            <div>
                <div class="bg-black border border-white/10 rounded-2xl p-6 space-y-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#FF4E00]">ORDER SUMMARY</h3>

                    <div class="space-y-3.5 text-xs">
                        <div class="flex justify-between items-center text-white/50">
                            <span>Bag Subtotal</span>
                            <span class="text-white font-bold">$<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <?php if ($discount_percent > 0): ?>
                            <div class="flex justify-between items-center text-emerald-500 font-bold">
                                <span>Promo Discount (-<?= $discount_percent ?>%)</span>
                                <span>-$<?= number_format($discount_amount, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center text-white/50">
                            <span>Shipping Costs</span>
                            <?php if ($shipping_cost == 0): ?>
                                <span class="text-emerald-500 font-black uppercase tracking-wider text-[10px]">FREE SHIPPING</span>
                            <?php else: ?>
                                <span class="text-white font-bold">$<?= number_format($shipping_cost, 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="border-t border-white/10 pt-4 flex justify-between items-center text-sm font-black uppercase tracking-wide">
                            <span class="text-white">Order Total</span>
                            <span class="text-[#FF4E00]">$<?= number_format($total_amount, 2) ?></span>
                        </div>
                    </div>

                    <!-- COUPON PROMO FORM -->
                    <form action="cart.php" method="POST" class="border-t border-white/10 pt-6 space-y-3">
                        <label class="text-[9px] tracking-widest font-black text-white/40 block mb-1">PROMOTION VOUCHER CODE</label>
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                name="coupon_code" 
                                placeholder="e.g. SUMMER20, OFF10, SNEAKERNEW" 
                                class="w-full bg-white/[0.03] border border-white/10 text-white placeholder-white/20 px-3.5 py-2.5 text-[11px] uppercase tracking-wider focus:outline-none focus:border-[#FF4E00] rounded"
                            >
                            <button 
                                type="submit" 
                                name="apply_coupon"
                                class="bg-white/5 hover:bg-white/15 text-white font-bold text-[9px] px-4 py-2.5 uppercase tracking-widest rounded transition-all border border-white/10 select-none"
                            >
                                APPLY
                            </button>
                        </div>
                        <p class="text-[8px] text-white/30 uppercase leading-relaxed font-bold">Try applying "SNEAKERNEW" (15% off) or "SUMMER20" (20% off) vouchers.</p>
                    </form>

                    <!-- PLACE ORDER REDIRECT -->
                    <div class="pt-4">
                        <?php if ($currentUser): ?>
                            <a 
                                href="checkout.php" 
                                class="w-full bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-black text-xs py-4 rounded tracking-widest uppercase block text-center transition-all shadow-lg shadow-[#FF4E00]/10"
                            >
                                SECURE CHECKOUT
                            </a>
                        <?php else: ?>
                            <a 
                                href="login.php?msg=<?= urlencode("Please sign in or register to complete your order checkout.") ?>" 
                                class="w-full bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-black text-xs py-4 rounded tracking-widest uppercase block text-center transition-all shadow-lg shadow-[#FF4E00]/10"
                            >
                                SIGN IN TO CHECKOUT
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
