<?php
/**
 * StrideHub - Secure Checkout
 */
require_once __DIR__ . '/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
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

if (!$currentUser) {
    header("Location: login.php?msg=" . urlencode("Authentication required. Please sign in or register to complete your checkout."));
    exit();
}

// Fetch active items
$cartItems = [];
if ($currentUser && $db) {
    try {
        $stmt = $db->prepare("SELECT c.quantity, s.name, s.price, s.discount_price, sz.size, sz.id as size_id, s.id as shoe_id
                              FROM cart c
                              LEFT JOIN shoes s ON c.shoe_id = s.id
                              LEFT JOIN shoe_sizes sz ON c.size_id = sz.id
                              WHERE c.user_id = :user_id");
        $stmt->execute([':user_id' => $currentUser['id']]);
        $cartItems = $stmt->fetchAll();
    } catch (PDOException $e) {}
}

if (empty($cartItems) && isset($_SESSION['cart'])) {
    $cartItems = array_values($_SESSION['cart']);
}

if (empty($cartItems)) {
    header("Location: index.php");
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $c) {
    $unit_price = (isset($c['discount_price']) && $c['discount_price'] !== null) ? $c['discount_price'] : $c['price'];
    $subtotal += $unit_price * $c['quantity'];
}

$discount_percent = isset($_SESSION['coupon']) ? $_SESSION['coupon']['discount'] : 0;
$discount_amount = $subtotal * ($discount_percent / 100);
$shipping_cost = $subtotal > 150 ? 0.00 : 15.00;
$total_amount = $subtotal - $discount_amount + $shipping_cost;

$submit_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $address_line = trim($_POST['address_line'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'credit_card');

    if (empty($full_name) || empty($address_line) || empty($city) || empty("postal_code")) {
        $submit_error = 'All shipping form fields are required.';
    } else {
        // Save order to DB (if connection exists) or simulate
        $order_id = mt_rand(1001, 9999);
        $order_saved = false;

        if ($db) {
            try {
                $db->beginTransaction();

                // 1. Create Address
                $uid = $currentUser ? $currentUser['id'] : null;
                $addr_stmt = $db->prepare("INSERT INTO addresses (user_id, country, city, address_line, postal_code) VALUES (:u, :co, :ci, :a, :p)");
                $addr_stmt->execute([
                    ':u' => $uid,
                    ':co' => htmlspecialchars($country),
                    ':ci' => htmlspecialchars($city),
                    ':a' => htmlspecialchars($address_line),
                    ':p' => htmlspecialchars($postal_code)
                ]);
                $address_id = $db->lastInsertId();

                // 2. Insert Order Header
                $ord_stmt = $db->prepare("INSERT INTO orders (user_id, total_price, shipping_price, payment_method, status, shipping_address_id) VALUES (:u, :tp, :sp, :pm, 'confirmed', :aid)");
                $ord_stmt->execute([
                    ':u' => $uid,
                    ':tp' => $total_amount,
                    ':sp' => $shipping_cost,
                    ':pm' => $payment_method,
                    ':aid' => $address_id
                ]);
                $order_id = $db->lastInsertId();

                // 3. Clear SQL DB Cart list
                if ($uid) {
                    $db->prepare("DELETE FROM cart WHERE user_id = :u")->execute([':u' => $uid]);
                }

                $db->commit();
                $order_saved = true;
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $submit_error = 'Database order insertion failed: ' . $e->getMessage();
            }
        }

        if (empty($submit_error)) {
            // Unset Session parameters
            $_SESSION['cart'] = [];
            unset($_SESSION['coupon']);

            // Save in simulated history database array for quick guest tracking
            if (!isset($_SESSION['order_history'])) {
                $_SESSION['order_history'] = [];
            }
            $_SESSION['order_history'][] = [
                'order_id' => $order_id,
                'total_price' => $total_amount,
                'payment_method' => $payment_method,
                'status' => 'confirmed',
                'created_at' => date('Y-m-d H:i:s'),
                'address' => "$address_line, $city, $country ($postal_code)"
            ];

            header("Location: orders.php?success=Order placed successfully! Transaction ID #SH-{$order_id}");
            exit();
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-12">
    <div class="border-l-2 border-[#FF4E00] pl-4 mb-10">
        <h1 class="text-3xl md:text-5xl font-black uppercase tracking-tight text-white">CHECKOUT</h1>
        <p class="text-[10px] text-white/40 uppercase tracking-widest mt-1">Provide your delivery endpoint and select secure settlements</p>
    </div>

    <?php if ($submit_error): ?>
        <div class="mb-8 bg-red-950/20 border border-red-500/20 text-red-500 text-[11px] font-bold px-4 py-3 rounded uppercase tracking-wider">
            <?= $submit_error ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- FORM CONTAINER COLUMN -->
        <div class="lg:col-span-2">
            <form action="checkout.php" method="POST" class="space-y-8">
                <input type="hidden" name="place_order" value="1">

                <!-- SECTION 1: SHIPPING ADDRESS -->
                <div class="bg-black border border-white/5 p-6 rounded-2xl space-y-5">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#FF4E00] border-b border-white/15 pb-2.5">1. Delivery Endpoint</h3>
                    
                    <div>
                        <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">Recipient Full Name</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            placeholder="e.g. John Doe" 
                            required 
                            class="w-full bg-white/[0.02] border border-white/10 text-white placeholder-white/20 px-3.5 py-2.5 text-xs focus:outline-none focus:border-[#FF4E00] rounded transition-all"
                            value="<?= $currentUser ? htmlspecialchars($currentUser['full_name']) : '' ?>"
                        >
                    </div>

                    <div>
                        <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">Delivery Address</label>
                        <input 
                            type="text" 
                            name="address_line" 
                            placeholder="e.g. 10 Stride Manor, Apt 4B" 
                            required 
                            class="w-full bg-white/[0.02] border border-white/10 text-white placeholder-white/20 px-3.5 py-2.5 text-xs focus:outline-none focus:border-[#FF4E00] rounded transition-all"
                        >
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">City</label>
                            <input 
                                type="text" 
                                name="city" 
                                placeholder="e.g. New York" 
                                required 
                                class="w-full bg-white/[0.02] border border-white/10 text-white placeholder-white/20 px-3.5 py-2.5 text-xs focus:outline-none focus:border-[#FF4E00] rounded transition-all"
                            >
                        </div>
                        <div>
                            <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">Country</label>
                            <input 
                                type="text" 
                                name="country" 
                                placeholder="e.g. United States" 
                                required 
                                class="w-full bg-white/[0.02] border border-white/10 text-white placeholder-white/20 px-3.5 py-2.5 text-xs focus:outline-none focus:border-[#FF4E00] rounded transition-all"
                                value="United States"
                            >
                        </div>
                        <div>
                            <label class="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1.5">Postal Code</label>
                            <input 
                                type="text" 
                                name="postal_code" 
                                placeholder="e.g. 10001" 
                                required 
                                class="w-full bg-white/[0.02] border border-white/10 text-white placeholder-white/20 px-3.5 py-2.5 text-xs focus:outline-none focus:border-[#FF4E00] rounded transition-all"
                            >
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: PAYMENT METHOD -->
                <div class="bg-black border border-white/5 p-6 rounded-2xl space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#FF4E00] border-b border-white/15 pb-2.5">2. Secure Settlement Gate</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="relative block">
                            <input type="radio" name="payment_method" value="credit_card" checked class="peer sr-only">
                            <div class="cursor-pointer border border-white/15 hover:border-white/45 p-4 rounded text-xs transition-colors peer-checked:border-[#FF4E00] peer-checked:bg-[#FF4E00]/5 flex flex-col justify-between h-24">
                                <span class="font-black uppercase tracking-wider text-white">CARD GATEWAY</span>
                                <span class="text-[9px] text-white/40 uppercase">Visa, MasterCard, Amex</span>
                            </div>
                        </label>
                        <label class="relative block">
                            <input type="radio" name="payment_method" value="paypal" class="peer sr-only">
                            <div class="cursor-pointer border border-white/15 hover:border-white/45 p-4 rounded text-xs transition-colors peer-checked:border-[#FF4E00] peer-checked:bg-[#FF4E00]/5 flex flex-col justify-between h-24">
                                <span class="font-black uppercase tracking-wider text-white">PAYPAL PORT</span>
                                <span class="text-[9px] text-white/40 uppercase">One-Click redirection</span>
                            </div>
                        </label>
                        <label class="relative block">
                            <input type="radio" name="payment_method" value="crypto" class="peer sr-only">
                            <div class="cursor-pointer border border-white/15 hover:border-white/45 p-4 rounded text-xs transition-colors peer-checked:border-[#FF4E00] peer-checked:bg-[#FF4E00]/5 flex flex-col justify-between h-24">
                                <span class="font-black uppercase tracking-wider text-white">CRYPTOPAY</span>
                                <span class="text-[9px] text-white/40 uppercase">BTC, ETH, USDC</span>
                            </div>
                        </label>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-black text-sm py-4 rounded tracking-widest uppercase transition-all duration-300 transform hover:-translate-y-0.5"
                >
                    PLACE SECURE ORDER ($<?= number_format($total_amount, 2) ?>)
                </button>
            </form>
        </div>

        <!-- RECAP COLUMN -->
        <div>
            <div class="bg-black border border-white/10 rounded-2xl p-6 space-y-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#FF4E00] border-b border-white/15 pb-2.5">CART RECAP</h3>
                
                <div class="max-h-64 overflow-y-auto space-y-3.5 pr-2">
                    <?php if (empty($cartItems)): ?>
                        <p class="text-xs text-white/40 uppercase">No items.</p>
                    <?php else: ?>
                        <?php foreach ($cartItems as $itm): ?>
                            <?php $val = (isset($itm['discount_price']) && $itm['discount_price'] !== null) ? $itm['discount_price'] : $itm['price']; ?>
                            <div class="flex justify-between items-center text-xs">
                                <div class="flex flex-col">
                                    <span class="font-bold text-white uppercase truncate max-w-[150px]"><?= htmlspecialchars($itm['name']) ?></span>
                                    <span class="text-[9px] text-white/40 uppercase font-black">SIZE <?= htmlspecialchars($itm['size'] ?? '42') ?> &bull; QTY <?= $itm['quantity'] ?></span>
                                </div>
                                <span class="font-bold text-white/80">$<?= number_format($val * $itm['quantity'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="border-t border-white/10 pt-4 space-y-2 text-xs">
                    <div class="flex justify-between text-white/40">
                        <span>Items Subtotal</span>
                        <span>$<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <?php if ($discount_percent > 0): ?>
                        <div class="flex justify-between text-emerald-500 font-bold">
                            <span>Promo Voucher Discount</span>
                            <span>-$<?= number_format($discount_amount, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-white/40">
                        <span>Shipping Cost</span>
                        <span>$<?= number_format($shipping_cost, 2) ?></span>
                    </div>
                    <div class="border-t border-white/10 pt-4 flex justify-between font-black uppercase text-white tracking-wider text-sm">
                        <span>Final Total</span>
                        <span class="text-[#FF4E00]">$<?= number_format($total_amount, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
