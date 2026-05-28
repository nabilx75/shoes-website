<?php
/**
 * StrideHub - Wishlist / Liked Shoes Ledger
 * Renders liked products cleanly with real-time reactive management.
 */
require_once __DIR__ . '/header.php';

// Force authentication
if (!$currentUser) {
    header("Location: login.php?msg=" . urlencode("Please sign in or register to view your wishlist."));
    exit();
}

// Fetch user's wishlisted shoes from MySQL DB
$shoes = [];
if ($db) {
    try {
        $stmt = $db->prepare("
            SELECT s.*, b.name as brand_name, c.name as category_name 
            FROM wishlist w
            INNER JOIN shoes s ON w.shoe_id = s.id
            LEFT JOIN brands b ON s.brand_id = b.id 
            LEFT JOIN categories c ON s.category_id = c.id 
            WHERE s.is_active = TRUE AND w.user_id = :user_id
            ORDER BY w.id DESC
        ");
        $stmt->execute([':user_id' => $currentUser['id']]);
        $shoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch primary image for each shoe
        foreach ($shoes as &$shoe) {
            $img_stmt = $db->prepare("SELECT image_url FROM shoe_images WHERE shoe_id = :shoe_id AND is_primary = TRUE LIMIT 1");
            $img_stmt->execute([':shoe_id' => $shoe['id']]);
            $img = $img_stmt->fetch();
            $shoe['primary_image'] = $img ? $img['image_url'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80';
        }
    } catch (PDOException $e) {}
}

$shoes_json = json_encode($shoes);
?>

<div class="relative bg-black min-h-screen py-16 px-6">
    <!-- BACKGROUND GLOW EFFECTS -->
    <div class="absolute top-0 left-1/4 w-[400px] h-[400px] bg-[#FF4E00]/10 rounded-full blur-[150px] pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-[#FF4E00]/5 rounded-full blur-[150px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto relative z-10">
        <!-- HEADER MODULE -->
        <div class="border-b border-white/10 pb-6 mb-12 flex flex-col md:flex-row items-start md:items-end justify-between gap-6">
            <div>
                <span class="text-[10px] bg-[#FF4E00]/10 border border-[#FF4E00]/30 text-[#FF4E00] px-3 py-1 rounded font-black tracking-widest uppercase inline-block mb-3">
                    Personal Vault
                </span>
                <h1 class="text-3xl lg:text-4xl font-black uppercase tracking-tight leading-none text-white">Your Liked Shoes</h1>
                <p class="text-xs text-white/50 mt-2 max-w-lg">Manage and purchase the premium custom kicks you have favorited from our custom sneakers line.</p>
            </div>
            <a href="index.php" class="text-xs text-[#FF4E00] hover:text-white font-black tracking-widest uppercase border border-[#FF4E00]/20 hover:border-white hover:bg-white/5 px-6 py-3 rounded transition-all">
                Continue Shopping
            </a>
        </div>

        <!-- WISHLIST GRID WRAPPER -->
        <div id="wishlist-empty-state" class="hidden text-center py-20 bg-neutral-950/40 border border-white/5 rounded-3xl p-10 max-w-md mx-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[#FF4E00] mx-auto mb-6 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <h3 class="text-xl font-black uppercase tracking-wide text-white mb-2">Your Bag is empty of Likes</h3>
            <p class="text-xs text-white/50 leading-relaxed mb-6">Looks like you haven't liked any premium kicks yet. Browse our limited collections to build your wishlist!</p>
            <a href="index.php#catalog-grid" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-black text-xs px-6 py-3.5 rounded tracking-widest uppercase shadow-lg shadow-[#FF4E00]/20 transition-all inline-block">
                EXPLORE CATALOG
            </a>
        </div>

        <!-- MAIN GRID -->
        <div id="wishlist-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Dynamic elements go here -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const allShoes = <?php echo $shoes_json; ?>;
    const wishlistGrid = document.getElementById('wishlist-grid');
    const emptyState = document.getElementById('wishlist-empty-state');

    // Render server-side database list directly
    function removeWishlistItem(shoeId) {
        fetch('toggle_wishlist.php?shoe_id=' + shoeId, { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to remove from wishlist.');
                }
            })
            .catch(() => {
                // Fallback client removal if server offline/sandboxed
                location.reload();
            });
    }

    function renderWishlist() {
        // Clear grid
        wishlistGrid.innerHTML = '';

        const likedShoes = allShoes;

        if (likedShoes.length === 0) {
            wishlistGrid.style.display = 'none';
            emptyState.classList.remove('hidden');
            return;
        }

        wishlistGrid.style.display = 'grid';
        emptyState.classList.add('hidden');

        likedShoes.forEach(shoe => {
            const hasDiscount = shoe.discount_price !== null && parseFloat(shoe.discount_price) > 0;
            const displayPrice = hasDiscount ? shoe.discount_price : shoe.price;
            const originalPrice = shoe.price;
            
            // Format monetary values
            const formattedDisplayPrice = parseFloat(displayPrice).toFixed(2);
            const formattedOriginalPrice = parseFloat(originalPrice).toFixed(2);

            const card = document.createElement('div');
            card.className = "premium-card cursor-pointer bg-black/80 border border-white/10 rounded-2xl overflow-hidden flex flex-col group relative";
            card.setAttribute('onclick', `if (!event.target.closest('.wishlist-toggle-btn') && !event.target.closest('.remove-btn')) { window.location.href='product.php?id=${shoe.id}'; }`);

            card.innerHTML = `
                <!-- Overlay Brand Name tag -->
                <div class="absolute top-4 left-4 z-10 bg-black/60 border border-white/10 text-[9px] px-2.5 py-1 rounded font-bold uppercase tracking-widest text-[#FF4E00]">
                    \${shoe.brand_name || 'StrideHub'}
                </div>

                <!-- Unlike Button -->
                <button 
                    class="remove-btn absolute top-4 right-4 z-20 bg-black/60 border border-white/10 text-white/50 hover:text-white hover:bg-red-650/20 hover:border-red-500/50 p-2 rounded-full transition-all focus:outline-none"
                    title="Remove from wishlist"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>

                <!-- Clickable Image container -->
                <div class="w-full bg-neutral-900 border-b border-white/5 overflow-hidden relative aspect-square p-6 flex items-center justify-center">
                    <img 
                        src="\${shoe.primary_image}" 
                        alt="\${shoe.name}" 
                        class="h-36 w-auto object-contain transform group-hover:scale-110 transition-transform duration-500"
                        referrerpolicy="no-referrer"
                    >
                </div>

                <!-- Content -->
                <div class="p-5 flex-grow flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] text-[#FF4E00]/80 font-bold uppercase tracking-widest block mb-1">
                            \${shoe.category_name || 'Footwear'} &bull; \${shoe.gender}
                        </span>
                        <h2 class="text-base font-black uppercase tracking-wide group-hover:text-[#FF4E00] transition-colors leading-tight mb-2">
                            \${shoe.name}
                        </h2>
                        <p class="text-[11px] text-white/50 leading-relaxed line-clamp-2 h-8 mb-4">
                            \${shoe.description || 'Exclusive custom line limit.'}
                        </p>
                    </div>

                    <div class="flex items-center justify-between border-t border-white/5 pt-4">
                        <div class="flex flex-col">
                            \${hasDiscount ? `
                                <span class="text-[9px] text-white/30 line-through">$\${formattedOriginalPrice}</span>
                                <span class="text-lg font-black text-[#FF4E00] tracking-tight">$\${formattedDisplayPrice}</span>
                            ` : `
                                <span class="text-lg font-black text-white tracking-tight">$\${formattedDisplayPrice}</span>
                            `}
                        </div>
                        <div class="bg-white/5 group-hover:bg-[#FF4E00] border border-white/10 group-hover:border-[#FF4E00]/20 text-white/70 group-hover:text-white p-2.5 rounded-full transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>
                    </div>
                </div>
            `;

            // Attach click listener for Remove button
            card.querySelector('.remove-btn').addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                removeWishlistItem(shoe.id);
            });

            wishlistGrid.appendChild(card);
        });
    }

    renderWishlist();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
