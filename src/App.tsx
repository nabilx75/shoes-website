/**
 * StrideHub - Premium Sneaker E-Commerce
 * @license Apache-2.0
 */

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  ShoppingBag, Heart, Search, Star, Trash, Plus, Minus, User, 
  Settings, LogOut, MapPin, Activity, Grid, ChevronLeft, ChevronRight, 
  Filter, Check, ArrowUpDown, X, ShieldAlert, Award, FileText, Tag, CreditCard, ShoppingCart
} from 'lucide-react';

// ==========================================
// TYPES & SCHEMAS
// ==========================================

interface User {
  id: number;
  full_name: string;
  email: string;
  phone: string;
  role: 'admin' | 'customer';
  status: 'active' | 'blocked';
  created_at: string;
}

interface Shoe {
  id: number;
  name: string;
  brand_id: number;
  brand_name: string;
  category_id: number;
  category_name: string;
  description: string;
  price: number;
  discount_price: number | null;
  gender: 'men' | 'women' | 'unisex' | 'kids';
  color: string;
  material: string;
  stock: number;
  rating_average: number;
  featured: boolean;
  primary_image: string | null;
}

interface ShoeSize {
  id: number;
  shoe_id: number;
  size: string;
  stock_quantity: number;
}

interface ShoeImage {
  id: number;
  shoe_id: number;
  image_url: string;
  is_primary: boolean;
}

interface Review {
  id: number;
  user_id: number;
  shoe_id: number;
  rating: number;
  comment: string;
  reviewer_name: string;
  created_at: string;
}

interface CartItem {
  id: number;
  user_id: number;
  shoe_id: number;
  size_id: number;
  quantity: number;
  shoe: {
    id: number;
    name: string;
    price: number;
    discount_price: number | null;
    primary_image: string | null;
  } | null;
  size: string;
}

interface Address {
  id: number;
  user_id: number;
  country: string;
  city: string;
  address_line: string;
  postal_code: string;
  is_default: boolean;
}

interface OrderItem {
  id: number;
  order_id: number;
  shoe_id: number;
  size_id: number;
  quantity: number;
  price: number;
  shoe_name: string;
  size_label: string;
}

interface Order {
  id: number;
  user_id: number;
  total_price: number;
  shipping_price: number;
  payment_method: string;
  status: 'pending' | 'confirmed' | 'shipped' | 'delivered' | 'cancelled';
  shipping_address_id: number;
  coupon_id: number | null;
  created_at: string;
  items: OrderItem[];
  address?: Address;
  buyer_name?: string;
  buyer_email?: string;
  payment?: {
    payment_status: string;
    transaction_reference: string;
  };
}

interface AdminKPIs {
  total_users: number;
  total_orders: number;
  total_revenue: number;
  low_stock_count: number;
  low_stock_list: string[];
  best_sellers: { name: string; sold: number; revenue: number }[];
  recent_logs: { id: number; action: string; created_at: string }[];
}

// ==========================================
// PROCEDURAL SNEAKER ILLUSTRATOR (SVG)
// ==========================================

export function SneakerVector({ styleType, color = '#FF4E00', className = "w-full h-full" }: { styleType: string; color?: string; className?: string }) {
  // Generate distinct sneaker outlines based on model type
  const activeColor = color.includes('Orange') ? '#FF4E00' : 
                      color.includes('Volt') || color.includes('Glow') ? '#a3e635' : 
                      color.includes('Pink') ? '#ec4899' : 
                      color.includes('Lilac') ? '#c084fc' : 
                      color.includes('Moss') ? '#22c55e' : 
                      color.includes('White') ? '#f3f4f6' : '#FF4E00';

  return (
    <svg viewBox="0 0 100 60" fill="none" xmlns="http://www.w3.org/2000/svg" className={className}>
      <defs>
        <linearGradient id="soleGrad" x1="0" y1="1" x2="0" y2="0">
          <stop offset="0%" stopColor="#111111" />
          <stop offset="100%" stopColor="#2c2c2c" />
        </linearGradient>
        <linearGradient id="upperGrad" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor={activeColor} />
          <stop offset="100%" stopColor="#0a0a0a" />
        </linearGradient>
      </defs>
      {/* Background Shadow bloom */}
      <ellipse cx="50" cy="52" rx="35" ry="4" fill="rgba(255, 78, 0, 0.15)" filter="blur(2px)" />
      
      {/* Shoe Sole Unit */}
      <path d="M12,48 C20,49 40,51 60,51 C75,51 86,48 88,44 C90,40 85,38 72,41 C62,43 52,43 38,41 C24,39 10,41 8,45 C6,48 8,48 12,48 Z" fill="url(#soleGrad)" stroke="#3f3f46" strokeWidth="0.5" />
      
      {/* Cushioned Midsole */}
      {styleType === 'running' || styleType === 'carbon' ? (
        <path d="M12,46 L80,46 C84,46 88,43 85,39 C72,40 50,40 40,38 C28,36 14,40 10,43 Z" fill={activeColor} opacity="0.8" />
      ) : null}

      {/* Main Sneaker Body Upper Silhouette */}
      <path d="M10,43 C8,41 9,33 13,29 C17,25 24,24 28,18 C31,14 36,12 42,12 C46,12 50,15 54,20 C58,25 64,28 72,30 C80,32 87,36 88,40 C89,44 85,45 80,45 C65,45 42,44 26,45 C16,45 11,44 10,43 Z" fill="url(#upperGrad)" stroke="white" strokeOpacity="0.1" strokeWidth="0.75" />

      {/* Laces & Collar Overlays */}
      <path d="M36,16 L48,26 M40,14 L50,22 M44,12 L52,18" stroke="white" strokeWidth="1" strokeLinecap="round" opacity="0.7" />
      <path d="M32,18 C30,16 34,14 38,13" stroke="white" strokeWidth="1" strokeLinecap="round" opacity="0.5" />

      {/* StrideHub Signature Swoosh/Detail Panel line */}
      {styleType === 'basketball' ? (
        <path d="M15,35 L40,32 C45,31 55,24 60,35 C52,38 28,39 15,35 Z" fill="none" stroke="#ffffff" strokeWidth="1" opacity="0.4" />
      ) : (
        <path d="M20,38 Q45,25 75,34" fill="none" stroke="#ffffff" strokeWidth="1.5" strokeLinecap="round" opacity="0.3" />
      )}
      
      {/* Air Pod Bubble bubble */}
      {(styleType === 'running' || styleType === 'carbon') && (
        <rect x="65" y="44" width="12" height="3" rx="1.5" fill="#ffffff" opacity="0.6" />
      )}
    </svg>
  );
}

// ==========================================
// CORE APP APPLET
// ==========================================

export default function App() {
  // Core Auth States
  const [token, setToken] = useState<string | null>(localStorage.getItem('strideauth_token'));
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  
  // Navigation
  const [activeTab, setActiveTab] = useState<'catalog' | 'account' | 'admin'>('catalog');
  const [searchQuery, setSearchQuery] = useState('');
  
  // Selection / Modal triggers
  const [selectedShoeId, setSelectedShoeId] = useState<number | null>(null);
  const [selectedShoe, setSelectedShoe] = useState<any | null>(null);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const [authMode, setAuthMode] = useState<'signin' | 'signup'>('signin');
  const [isCartOpen, setIsCartOpen] = useState(false);
  
  // Data list states
  const [shoes, setShoes] = useState<Shoe[]>([]);
  const [brands, setBrands] = useState<{ id: number; name: string }[]>([]);
  const [categories, setCategories] = useState<{ id: number; name: string }[]>([]);
  
  // Filtering & Sorting configuration
  const [filterBrand, setFilterBrand] = useState<string>('');
  const [filterCategory, setFilterCategory] = useState<string>('');
  const [filterGender, setFilterGender] = useState<string>('all');
  const [filterMinPrice, setFilterMinPrice] = useState<number>(0);
  const [filterMaxPrice, setFilterMaxPrice] = useState<number>(350);
  const [sortOption, setSortOption] = useState<string>('newest');
  const [isFilterSidebarOpen, setIsFilterSidebarOpen] = useState(false);
  const [isMobile, setIsMobile] = useState(false);

  // Customer transactions arrays
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [wishlist, setWishlist] = useState<any[]>([]);
  const [coupons, setCoupons] = useState<any[]>([]);
  const [appliedCoupon, setAppliedCoupon] = useState<any | null>(null);
  const [couponCodeInput, setCouponCodeInput] = useState('');
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [orderHistory, setOrderHistory] = useState<Order[]>([]);

  // Authenticated form state mappings
  const [authFullName, setAuthFullName] = useState('');
  const [authEmail, setAuthEmail] = useState('');
  const [authPassword, setAuthPassword] = useState('');
  const [authPhone, setAuthPhone] = useState('');
  const [authError, setAuthError] = useState('');

  // Checkout layout states
  const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
  const [shippingAddressLine, setShippingAddressLine] = useState('');
  const [shippingCity, setShippingCity] = useState('');
  const [shippingCountry, setShippingCountry] = useState('United Kingdom');
  const [shippingPostalCode, setShippingPostalCode] = useState('');
  const [shippingPhone, setShippingPhone] = useState('');
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState('Card Payment');

  // Product Review form state
  const [newRating, setNewRating] = useState(5);
  const [newComment, setNewComment] = useState('');

  // Admin Dashboard States
  const [adminKPIs, setAdminKPIs] = useState<AdminKPIs | null>(null);
  const [adminCustomers, setAdminCustomers] = useState<User[]>([]);
  const [adminSearchCustomer, setAdminSearchCustomer] = useState('');
  const [isProductAddModalOpen, setIsProductAddModalOpen] = useState(false);
  
  // Custom Shoe Add/Edit Admin form state
  const [newShoeName, setNewShoeName] = useState('');
  const [newShoeImages, setNewShoeImages] = useState<string[]>([
    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80',
    '',
    '',
    '',
    ''
  ]);
  const [newShoeBrand, setNewShoeBrand] = useState(1);
  const [newShoeCategory, setNewShoeCategory] = useState(1);
  const [newShoeDescription, setNewShoeDescription] = useState('');
  const [newShoePrice, setNewShoePrice] = useState(120);
  const [newShoeDiscount, setNewShoeDiscount] = useState<number | ''>('');
  const [newShoeGender, setNewShoeGender] = useState<'men' | 'women' | 'unisex' | 'kids'>('unisex');
  const [newShoeColor, setNewShoeColor] = useState('');
  const [newShoeMaterial, setNewShoeMaterial] = useState('Mesh');
  const [newShoeSizesInput, setNewShoeSizesInput] = useState<{size:string; stock_quantity:number}[]>([
    { size: '40', stock_quantity: 10 },
    { size: '41', stock_quantity: 10 },
    { size: '42', stock_quantity: 10 },
    { size: '43', stock_quantity: 10 }
  ]);
  const [editingShoeId, setEditingShoeId] = useState<number | null>(null);

  // Status feedback toast banner
  const [toastText, setToastText] = useState<string | null>(null);

  const triggerToast = (msg: string) => {
    setToastText(msg);
    setTimeout(() => setToastText(null), 4000);
  };

  // ==========================================
  // COMMUNICATORS & API CALLS
  // ==========================================

  const authHeaders = () => {
    return token ? { 'Authorization': `Bearer ${token}` } : {};
  };

  // Check and listen to screen size changes
  useEffect(() => {
    const checkMobileWidth = () => {
      setIsMobile(window.innerWidth < 768);
    };
    checkMobileWidth();
    window.addEventListener('resize', checkMobileWidth);
    return () => window.removeEventListener('resize', checkMobileWidth);
  }, []);

  // Load Initial Metadata Products dropdown values
  useEffect(() => {
    const fetchMetadata = async () => {
      try {
        const resMeta = await fetch('/api/metadata');
        if (resMeta.ok) {
          const data = await resMeta.json();
          setBrands(data.brands || []);
          setCategories(data.categories || []);
        }
      } catch (err) {
        console.error('Metadata endpoints query mismatch', err);
      }
    };
    fetchMetadata();
  }, []);

  // Sync Products Catalog Lists
  const fetchShoes = async () => {
    try {
      const q = new URLSearchParams();
      if (searchQuery) q.append('search', searchQuery);
      if (filterBrand) q.append('brand_id', filterBrand);
      if (filterCategory) q.append('category_id', filterCategory);
      if (filterGender && filterGender !== 'all') q.append('gender', filterGender);
      if (filterMinPrice) q.append('min_price', String(filterMinPrice));
      if (filterMaxPrice) q.append('max_price', String(filterMaxPrice));
      if (sortOption) q.append('sort', sortOption);

      const res = await fetch(`/api/shoes?${q.toString()}`);
      if (res.ok) {
        setShoes(await res.json());
      }
    } catch (err) {
      console.error('Core catalog fetch error', err);
    }
  };

  useEffect(() => {
    fetchShoes();
  }, [searchQuery, filterBrand, filterCategory, filterGender, filterMinPrice, filterMaxPrice, sortOption]);

  // Decode profile info on token update
  useEffect(() => {
    if (!token) {
      setCurrentUser(null);
      setCartItems([]);
      setWishlist([]);
      setOrderHistory([]);
      return;
    }

    try {
      // Auto hydrate user state from token
      const base64Url = token.split('.')[0];
      const parsed = JSON.parse(window.atob(base64Url));
      
      const fetchProfileDetails = async () => {
        // Fetch cart, wishlist, orders, and addresses
        await fetchCart();
        await fetchWishlist();
        await fetchOrdersHistory();
        await fetchAddresses();

        // Check if role is admin
        if (parsed.email && parsed.email.includes('admin')) {
          setCurrentUser({
            id: parsed.id,
            full_name: 'StrideHub Administrator',
            email: parsed.email,
            phone: '1-800-STRIDE',
            role: 'admin',
            status: 'active',
            created_at: ''
          });
        } else {
          setCurrentUser({
            id: parsed.id,
            full_name: 'Valued Customer',
            email: parsed.email,
            phone: '',
            role: 'customer',
            status: 'active',
            created_at: ''
          });
        }
      };

      fetchProfileDetails();
    } catch (err) {
      console.error('Decoder error on auth state configuration reset', err);
      logout();
    }
  }, [token]);

  // Sync admin states if logged as admin
  useEffect(() => {
    if (currentUser?.role === 'admin') {
      fetchAdminKPIs();
      fetchAdminCustomers();
    }
  }, [currentUser]);

  // 1. Core Actions (Auth, Add to Cart, Buy, Reviews, admin management, logs)
  const login = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthError('');
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: authEmail, password: authPassword })
      });
      const data = await res.json();
      if (res.ok && data.success) {
        localStorage.setItem('strideauth_token', data.token);
        setToken(data.token);
        setIsAuthModalOpen(false);
        setAuthPassword('');
        triggerToast(`Welcome back, ${data.user.full_name}!`);
        if (data.user.role === 'admin') {
          setActiveTab('admin');
        } else {
          setActiveTab('catalog');
        }
      } else {
        setAuthError(data.error || 'Invalid credentials');
      }
    } catch (err: any) {
      setAuthError('Connection failed.');
    }
  };

  const register = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthError('');
    try {
      const res = await fetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          full_name: authFullName, 
          email: authEmail, 
          password: authPassword, 
          phone: authPhone 
        })
      });
      const data = await res.json();
      if (res.ok && data.success) {
        setAuthMode('signin');
        triggerToast('Account created successfully. Please sign in.');
      } else {
        setAuthError(data.error || 'Registration failed');
      }
    } catch (err) {
      setAuthError('Error reaching registration endpoint.');
    }
  };

  const logout = () => {
    localStorage.removeItem('strideauth_token');
    setToken(null);
    setCurrentUser(null);
    setCartItems([]);
    setWishlist([]);
    setOrderHistory([]);
    setActiveTab('catalog');
    triggerToast('Signed out.');
  };

  // Cart operations
  const fetchCart = async () => {
    try {
      const res = await fetch('/api/cart', { headers: authHeaders() });
      if (res.ok) {
        setCartItems(await res.json());
      }
    } catch {}
  };

  const addToCart = async (shoeId: number, sizeId: number, qty: number = 1) => {
    if (!token) {
      setAuthMode('signin');
      setIsAuthModalOpen(true);
      triggerToast('Please sign in to view your bag.');
      return;
    }
    try {
      const res = await fetch('/api/cart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...authHeaders() },
        body: JSON.stringify({ shoe_id: shoeId, size_id: sizeId, quantity: qty })
      });
      if (res.ok) {
        await fetchCart();
        triggerToast('Item added to bag.');
      } else {
        const errorData = await res.json();
        triggerToast(errorData.error || 'Could not insert item');
      }
    } catch {
      triggerToast('Could not add item to bag.');
    }
  };

  const removeCartItem = async (cId: number) => {
    try {
      const res = await fetch(`/api/cart/${cId}`, {
        method: 'DELETE',
        headers: authHeaders()
      });
      if (res.ok) {
        await fetchCart();
        triggerToast('Item removed from bag.');
      }
    } catch {}
  };

  // Wishlisting Toggles
  const fetchWishlist = async () => {
    try {
      const res = await fetch('/api/wishlist', { headers: authHeaders() });
      if (res.ok) {
        setWishlist(await res.json());
      }
    } catch {}
  };

  const toggleWishlist = async (shoeId: number) => {
    if (!token) {
      setAuthMode('signin');
      setIsAuthModalOpen(true);
      return;
    }
    try {
      const res = await fetch('/api/wishlist', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...authHeaders() },
        body: JSON.stringify({ shoe_id: shoeId })
      });
      const data = await res.json();
      if (res.ok) {
        await fetchWishlist();
        triggerToast(data.action === 'added' ? 'Added to wishlist' : 'Removed from wishlist');
      }
    } catch {}
  };

  // Addresses lists
  const fetchAddresses = async () => {
    try {
      const res = await fetch('/api/addresses', { headers: authHeaders() });
      if (res.ok) {
        const data = await res.json();
        setAddresses(data);
        if (data.length > 0) {
          const mainDef = data.find((a: any) => a.is_default) || data[0];
          setShippingAddressLine(mainDef.address_line);
          setShippingCity(mainDef.city);
          setShippingCountry(mainDef.country);
          setShippingPostalCode(mainDef.postal_code);
        }
      }
    } catch {}
  };

  // Orders lists history
  const fetchOrdersHistory = async () => {
    try {
      const res = await fetch('/api/orders/history', { headers: authHeaders() });
      if (res.ok) {
        setOrderHistory(await res.json());
      }
    } catch {}
  };

  // Place checkout order
  const handlePlaceOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    if (cartItems.length === 0) {
      triggerToast('Shopping cart is empty.');
      return;
    }

    try {
      const res = await fetch('/api/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...authHeaders() },
        body: JSON.stringify({
          shipping_address: {
            address_line: shippingAddressLine,
            city: shippingCity,
            country: shippingCountry,
            postal_code: shippingPostalCode
          },
          phone: shippingPhone || currentUser?.phone,
          payment_method: selectedPaymentMethod,
          coupon_id: appliedCoupon ? appliedCoupon.id : null,
          cart_items: cartItems.map(item => ({
            shoe_id: item.shoe_id,
            size_id: item.size_id,
            quantity: item.quantity
          }))
        })
      });

      if (res.ok) {
        const data = await res.json();
        triggerToast('Order placed successfully.');
        setIsCheckoutOpen(false);
        setIsCartOpen(false);
        setAppliedCoupon(null);
        setCouponCodeInput('');
        await fetchCart();
        await fetchOrdersHistory();
        await fetchShoes();
        setActiveTab('account');
      } else {
        const err = await res.json();
        triggerToast(err.error || 'Failed to place order.');
      }
    } catch {
      triggerToast('An error occurred during checkout.');
    }
  };

  // Coupon application logic
  const handleValidateCoupon = async () => {
    if (!couponCodeInput.trim()) return;
    try {
      const res = await fetch('/api/coupons/validate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...authHeaders() },
        body: JSON.stringify({ code: couponCodeInput })
      });
      const data = await res.json();
      if (res.ok) {
        setAppliedCoupon(data.coupon);
        triggerToast(`Code applied: ${data.coupon.discount_percent}% off your order.`);
      } else {
        triggerToast(data.error || 'Invalid or expired promo code.');
      }
    } catch {
      triggerToast('Invalid promo code.');
    }
  };

  // View shoe detail context
  const handleSelectShoe = async (id: number) => {
    setSelectedShoeId(id);
    setSelectedShoe(null); // loading indicator
    try {
      const res = await fetch(`/api/shoes/${id}`);
      if (res.ok) {
        setSelectedShoe(await res.json());
      }
    } catch {
      triggerToast('Error loading item details.');
    }
  };

  // Create Product Review
  const handleSubmitReview = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) {
      setAuthMode('signin');
      setIsAuthModalOpen(true);
      return;
    }
    try {
      const res = await fetch('/api/reviews', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...authHeaders() },
        body: JSON.stringify({ shoe_id: selectedShoeId, rating: newRating, comment: newComment })
      });
      if (res.ok) {
        setNewComment('');
        triggerToast('Review published! Thank you.');
        if (selectedShoeId) {
          handleSelectShoe(selectedShoeId);
        }
        await fetchShoes();
      } else {
        const errorResult = await res.json();
        triggerToast(errorResult.error || 'Action failed');
      }
    } catch {
      triggerToast('Network error publishing review.');
    }
  };

  // ==========================================
  // ADMIN CONTROL LOGIC
  // ==========================================

  const fetchAdminKPIs = async () => {
    try {
      const res = await fetch('/api/admin/kpis', { headers: authHeaders() });
      if (res.ok) setAdminKPIs(await res.json());
    } catch {}
  };

  const fetchAdminCustomers = async (search = '') => {
    try {
      const res = await fetch(`/api/admin/customers?search=${search}`, { headers: authHeaders() });
      if (res.ok) setAdminCustomers(await res.json());
    } catch {}
  };

  const handleAdminCustomerToggleBlock = async (id: number) => {
    try {
      const res = await fetch(`/api/admin/customers/${id}/status`, {
        method: 'PUT',
        headers: authHeaders()
      });
      if (res.ok) {
        const data = await res.json();
        triggerToast(data.status === 'blocked' ? 'User suspended' : 'User profile active');
        fetchAdminCustomers(adminSearchCustomer);
        fetchAdminKPIs();
      }
    } catch {}
  };

  const handleAdminCustomerDelete = async (id: number) => {
    if (!window.confirm("Are you sure you want to completely liquidate this user's profile database entry?")) return;
    try {
      const res = await fetch(`/api/admin/customers/${id}`, {
        method: 'DELETE',
        headers: authHeaders()
      });
      if (res.ok) {
        triggerToast('User account successfully purged.');
        fetchAdminCustomers(adminSearchCustomer);
        fetchAdminKPIs();
      }
    } catch {}
  };

  const handleAdminStatusChange = async (orderId: number, nextStatus: string) => {
    try {
      const res = await fetch(`/api/admin/orders/${orderId}/status`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', ...authHeaders() },
        body: JSON.stringify({ status: nextStatus })
      });
      if (res.ok) {
        triggerToast('Order status code patched.');
        fetchOrdersHistory();
        fetchAdminKPIs();
      }
    } catch {}
  };

  const handleAdminDeleteReview = async (reviewId: number, shoeId: number) => {
    if (!window.confirm("Moderator review delete. Confirm?")) return;
    try {
      const res = await fetch(`/api/reviews/${reviewId}`, {
        method: 'DELETE',
        headers: authHeaders()
      });
      if (res.ok) {
        triggerToast('Inappropriate review removed.');
        if (selectedShoeId === shoeId) {
          handleSelectShoe(shoeId);
        }
        await fetchShoes();
        fetchAdminKPIs();
      }
    } catch {}
  };

  // Custom shoe Insert & Update Admin Operation
  const handleAddNewShoeSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const activeUrls = newShoeImages.filter(url => url && url.trim() !== '');
    if (activeUrls.length < 5) {
      triggerToast('At least 5 distinct product image URLs are required.');
      return;
    }

    const payload = {
      name: newShoeName,
      brand_id: newShoeBrand,
      category_id: newShoeCategory,
      description: newShoeDescription,
      price: newShoePrice,
      discount_price: newShoeDiscount === '' ? null : Number(newShoeDiscount),
      gender: newShoeGender,
      color: newShoeColor,
      material: newShoeMaterial,
      sizes: newShoeSizesInput,
      image_urls: activeUrls
    };

    try {
      const url = editingShoeId ? `/api/admin/shoes/${editingShoeId}` : '/api/admin/shoes';
      const method = editingShoeId ? 'PUT' : 'POST';

      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', ...authHeaders() },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        triggerToast(editingShoeId ? 'Shoe metadata updated.' : 'New premium sneaker dropped!');
        setIsProductAddModalOpen(false);
        setEditingShoeId(null);
        resetShoeForm();
        await fetchShoes();
        fetchAdminKPIs();
      } else {
        const err = await res.json();
        triggerToast(err.error || 'Failed saving product database.');
      }
    } catch {
      triggerToast('Server communication failure.');
    }
  };

  const handleAdminEditShoeTrigger = async (shoeId: number) => {
    try {
      const res = await fetch(`/api/shoes/${shoeId}`);
      if (res.ok) {
        const sh = await res.json();
        setEditingShoeId(sh.id);
        setNewShoeName(sh.name);
        setNewShoeBrand(sh.brand_id);
        setNewShoeCategory(sh.category_id);
        setNewShoeDescription(sh.description);
        setNewShoePrice(sh.price);
        setNewShoeDiscount(sh.discount_price !== null ? sh.discount_price : '');
        setNewShoeGender(sh.gender);
        setNewShoeColor(sh.color);
        setNewShoeMaterial(sh.material);
        if (sh.sizes && sh.sizes.length > 0) {
          setNewShoeSizesInput(sh.sizes.map((s: any) => ({ size: s.size, stock_quantity: s.stock_quantity })));
        }
        if (sh.images && sh.images.length > 0) {
          const urls = sh.images.map((img: any) => typeof img === 'string' ? img : img.image_url);
          while (urls.length < 5) {
            urls.push('');
          }
          setNewShoeImages(urls);
        } else if (sh.primary_image) {
          setNewShoeImages([sh.primary_image, '', '', '', '']);
        } else {
          setNewShoeImages(['', '', '', '', '']);
        }
        setIsProductAddModalOpen(true);
      }
    } catch {}
  };

  const handleImageUpload = async (idx: number, e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    triggerToast(`Uploading Pic #${idx + 1}...`);

    const reader = new FileReader();
    reader.onloadend = async () => {
      const base64 = reader.result as string;
      try {
        const res = await fetch('/api/upload', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            ...authHeaders()
          },
          body: JSON.stringify({ name: file.name, base64 })
        });
        const data = await res.json();
        if (res.ok && data.url) {
          const updated = [...newShoeImages];
          updated[idx] = data.url;
          setNewShoeImages(updated);
          triggerToast(`Pic #${idx + 1} cataloged successfully.`);
        } else {
          triggerToast(data.error || 'Failed to catalog file.');
        }
      } catch (err) {
        triggerToast('Error transmitting image payload.');
      }
    };
    reader.readAsDataURL(file);
  };

  const handleAdminToggleShoeActiveStatus = async (shoeId: number, currentActiveState: boolean) => {
    try {
      const res = await fetch(`/api/admin/shoes/${shoeId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', ...authHeaders() },
        body: JSON.stringify({ is_active: !currentActiveState })
      });
      if (res.ok) {
        triggerToast('Shoe visibility visibility updated.');
        await fetchShoes();
      }
    } catch {}
  };

  const handleAdminDeleteShoe = async (shoeId: number) => {
    if (!window.confirm("Soft delete matching product index? Relational histories remain intact.")) return;
    try {
      const res = await fetch(`/api/admin/shoes/${shoeId}`, {
        method: 'DELETE',
        headers: authHeaders()
      });
      if (res.ok) {
        triggerToast('Shoe successfully archived.');
        await fetchShoes();
        fetchAdminKPIs();
      }
    } catch {}
  };

  const resetShoeForm = () => {
    setEditingShoeId(null);
    setNewShoeName('');
    setNewShoeBrand(1);
    setNewShoeCategory(1);
    setNewShoeDescription('');
    setNewShoePrice(140);
    setNewShoeDiscount('');
    setNewShoeGender('unisex');
    setNewShoeColor('');
    setNewShoeMaterial('Premium Leather');
    setNewShoeSizesInput([
      { size: '40', stock_quantity: 12 },
      { size: '41', stock_quantity: 12 },
      { size: '42', stock_quantity: 12 },
      { size: '43', stock_quantity: 12 }
    ]);
    setNewShoeImages([
      '',
      '',
      '',
      '',
      ''
    ]);
  };

  // Pricing arithmetic calculations
  const calculateCartSubtotal = () => {
    return cartItems.reduce((acc, it) => {
      if (!it.shoe) return acc;
      const act = it.shoe.discount_price !== null ? it.shoe.discount_price : it.shoe.price;
      return acc + (act * it.quantity);
    }, 0);
  };

  const cartSubtotal = calculateCartSubtotal();
  const couponDiscountAmount = appliedCoupon ? cartSubtotal * (appliedCoupon.discount_percent / 100) : 0;
  const shippingCosts = cartSubtotal > 150 ? 0.00 : 15.00;
  const orderFinalTotal = Number((cartSubtotal - couponDiscountAmount + shippingCosts).toFixed(2));

  return (
    <div className="min-h-screen bg-[#050505] text-white flex flex-col font-sans antialiased selection:bg-[#FF4E00] selection:text-white">
      
      {/* Dynamic Toast Status Feed */}
      {toastText && (
        <div className="fixed top-20 right-8 z-[99999] bg-[#FF4E00] text-white font-bold tracking-wider text-xs px-6 py-4 rounded shadow-2xl border border-white/20 uppercase flex items-center gap-3 animate-fade-in">
          <Activity className="w-4 h-4 animate-pulse" />
          {toastText}
        </div>
      )}

      {/* ==========================================
          MEMORABLE TOP NAVIGATION BAR
         ========================================== */}
      <nav className="sticky top-0 z-50 h-16 bg-[#050505]/90 backdrop-blur-md border-b border-white/10 px-6 flex items-center justify-between">
        
        {/* Brand Logo - ITALIC SLANTED */}
        <div className="flex items-center gap-6">
          <span 
            className="text-2xl font-black tracking-tighter uppercase italic cursor-pointer select-none"
            onClick={() => { setActiveTab('catalog'); handleSelectShoe(1); }}
          >
            Stride<span className="text-[#FF4E00]">Hub</span>
          </span>
          
          {/* Main quick categories navigation */}
          <div className="hidden md:flex gap-6 text-[10px] font-bold uppercase tracking-widest text-white/50">
            <button onClick={() => { setFilterGender('men'); setActiveTab('catalog'); }} className={`hover:text-white transition-colors ${filterGender === 'men' ? 'text-white border-b-2 border-[#FF4E00] pb-1' : ''}`}>Men</button>
            <button onClick={() => { setFilterGender('women'); setActiveTab('catalog'); }} className={`hover:text-white transition-colors ${filterGender === 'women' ? 'text-white border-b-2 border-[#FF4E00] pb-1' : ''}`}>Women</button>
            <button onClick={() => { setFilterGender('unisex'); setActiveTab('catalog'); }} className={`hover:text-white transition-colors ${filterGender === 'unisex' ? 'text-white border-b-2 border-[#FF4E00] pb-1' : ''}`}>Limited Releases</button>
          </div>
        </div>

        {/* Global Catalog Filter Input */}
        <div className="flex-1 max-w-xs mx-6 relative">
          <div className="absolute left-3 top-1/2 -translate-y-1/2 text-white/40">
            <Search className="w-4 h-4" />
          </div>
          <input 
            type="text" 
            placeholder="SEARCH KICKS..." 
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full bg-white/5 border border-white/10 rounded-full py-2 pl-9 pr-4 text-xs tracking-wider uppercase focus:outline-none focus:border-[#FF4E00] transition-colors"
          />
          {searchQuery && (
            <button onClick={() => setSearchQuery('')} className="absolute right-3 top-1/2 -translate-y-1/2 text-white/40 hover:text-white text-xs">✕</button>
          )}
        </div>

        {/* Auth controllers or User metrics */}
        <div className="flex items-center gap-4">
          
          {/* Wishlist triggers tab */}
          {token && (
            <button 
              onClick={() => setActiveTab('account')} 
              className="text-white/60 hover:text-white cursor-pointer relative py-2"
              title="My Account Details"
            >
              <Heart className="w-5 h-5" />
              {wishlist.length > 0 && (
                <span className="absolute top-1 -right-2 bg-pink-600 text-[8px] font-bold px-1 rounded-full">{wishlist.length}</span>
              )}
            </button>
          )}

          {/* Cart sliding overlay access */}
          <button 
            onClick={() => setIsCartOpen(true)} 
            className="text-white/60 hover:text-white relative py-2 mr-2"
            title="My Shopping Bag"
          >
            <ShoppingBag className="w-5 h-5" />
            {cartItems.length > 0 && (
              <span className="absolute top-1 -right-2 bg-[#FF4E00] text-[8px] font-bold px-1 rounded-full">{cartItems.reduce((acc, i)=>acc+i.quantity,0)}</span>
            )}
          </button>

          {/* Profile routing / Access trigger */}
          {token ? (
            <div className="flex items-center gap-2 border-l border-white/10 pl-4">
              <button 
                onClick={() => setActiveTab(currentUser?.role === 'admin' ? 'admin' : 'account')}
                className="text-xs font-bold uppercase tracking-wider py-1.5 px-3 rounded-full bg-white/5 hover:bg-white/10 transition-colors flex items-center gap-1.5 border border-white/10"
              >
                {currentUser?.role === 'admin' ? <Settings className="w-3.5 h-3.5 text-[#FF4E00]" /> : <User className="w-3.5 h-3.5" />}
                {currentUser?.role === 'admin' ? 'Admin Panel' : 'My Account'}
              </button>
              <button onClick={logout} className="p-1.5 rounded-full hover:bg-white/5 text-white/40 hover:text-red-500" title="Sign Out">
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          ) : (
            <div className="flex items-center gap-2">
              <button 
                onClick={() => { setAuthMode('signin'); setIsAuthModalOpen(true); }}
                className="text-[10px] font-bold uppercase tracking-widest py-2 px-4 rounded-full border border-white/20 bg-transparent hover:bg-white/5 transition-colors"
              >
                Sign In
              </button>
              <button 
                onClick={() => { setAuthMode('signup'); setIsAuthModalOpen(true); }}
                className="text-[10px] font-bold uppercase tracking-widest py-2 px-4 rounded-full bg-[#FF4E00] text-white hover:bg-[#FF5D14] transition-colors"
              >
                Sign Up
              </button>
            </div>
          )}
        </div>
      </nav>

      {/* ==========================================
          MAIN HERO DROPPER SECTION (Featured product)
         ========================================== */}
      {activeTab === 'catalog' && !searchQuery && (
        <section className="relative min-h-[420px] bg-gradient-to-br from-[#0e0e0e] to-[#030303] border-b border-white/10 flex items-center px-12 overflow-hidden">
          {/* Radiant Backdrop bloom */}
          <div className="absolute right-0 top-0 w-1/2 h-full opacity-30 pointer-events-none">
            <div className="absolute inset-0 bg-radial from-[#FF4E00]/20 to-transparent blur-3xl"></div>
          </div>

          <div className="max-w-2xl z-10 py-10">
            <span className="text-[10px] font-black tracking-[0.3em] text-[#FF4E00] uppercase mb-3 block">LIMITED RELEASE</span>
            <h1 className="text-6xl md:text-7xl font-black italic uppercase leading-none tracking-tighter mb-4 text-white">
              MOVE WITH<br/>
              <span className="text-transparent" style={{WebkitTextStroke: "1px rgba(255,255,255,0.2)"}}>CONFIDENCE</span>
            </h1>
            <p className="text-white/65 text-sm max-w-md mb-6 leading-relaxed">
              Premium comfort, everyday movement, and effortless style. Designed for those who appreciate fine craftsmanship and timeless aesthetics.
            </p>
            <div className="flex items-center gap-6">
              <button 
                onClick={() => handleSelectShoe(1)} 
                className="bg-[#FF4E00] text-white font-black uppercase text-xs tracking-wider px-8 py-3.5 hover:bg-[#FF5D14] transition-all rounded"
              >
                EXPLORE DROP
              </button>
              <div className="flex flex-col">
                <span className="text-white/30 text-[9px] uppercase font-bold tracking-widest">EXCLUSIVE PRICE</span>
                <span className="text-2xl font-black tracking-tight">$159.00 <span className="text-xs text-white/40 line-through font-normal">$189.00</span></span>
              </div>
            </div>
          </div>

          {/* Large dynamic rotated sneaker vector visual */}
          <div className="hidden lg:flex absolute right-16 top-1/2 -translate-y-1/2 w-[460px] h-[340px] items-center justify-center pointer-events-none select-none">
            <div className="w-[300px] h-[300px] bg-[#FF4E00]/10 rounded-full blur-[90px] absolute"></div>
            <div className="w-full h-full -rotate-[16deg] transform hover:rotate-[-5deg] transition-all duration-700">
              <SneakerVector styleType="running" color="Orange" />
            </div>
          </div>
        </section>
      )}

      {/* ==========================================
          EXPLORE CATALOG & GENERAL WORKSPACE
         ========================================== */}
      <main className="flex-1 flex flex-col md:flex-row">
        
        {/* VIEW 1: PRODUCT STUDY EXPLORATION (CATALOG) */}
        {activeTab === 'catalog' && (
          <>
            {/* Sidebar filter column */}
            <AnimatePresence initial={false}>
              {isFilterSidebarOpen && (
                <motion.aside
                  initial={{ width: 0, opacity: 0 }}
                  animate={{ width: isMobile ? "100%" : 256, opacity: 1 }}
                  exit={{ width: 0, opacity: 0 }}
                  transition={{ type: "spring", stiffness: 300, damping: 30 }}
                  className="bg-black border-r border-white/5 flex flex-col overflow-hidden shrink-0"
                >
                  <div style={{ width: isMobile ? "100%" : 256 }} className="p-6 flex flex-col gap-6 shrink-0">
                    
                    <div className="flex items-center justify-between border-b border-white/10 pb-3">
                      <span className="text-xs font-black uppercase tracking-wider text-white/50 flex items-center gap-1.5">
                        <Filter className="w-3.5 h-3.5 text-[#FF4E00]" />
                        FILTERS
                      </span>
                      <div className="flex items-center gap-2">
                        <button 
                          onClick={() => {
                            setFilterBrand('');
                            setFilterCategory('');
                            setFilterGender('all');
                            setFilterMinPrice(0);
                            setFilterMaxPrice(350);
                            setSearchQuery('');
                          }}
                          className="text-[9px] font-bold text-[#FF4E00] uppercase tracking-widest hover:underline"
                        >
                          RESET
                        </button>
                        <button 
                          onClick={() => setIsFilterSidebarOpen(false)}
                          className="text-white/40 hover:text-white p-1 rounded hover:bg-white/5 transition-colors"
                          title="Hide Sidebar"
                        >
                          <X className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>

                    {/* Brands selection options */}
                    <div className="flex flex-col gap-2">
                      <label className="text-[10px] uppercase tracking-widest font-black text-white/40">BRANDS</label>
                      <div className="flex flex-wrap gap-1.5 mt-1">
                        <button 
                          onClick={() => setFilterBrand('')} 
                          className={`text-[9px] font-bold uppercase py-1 px-2.5 rounded border transition-colors ${!filterBrand ? 'bg-[#FF4E00] border-transparent text-white' : 'border-white/15 text-white/60 hover:border-white/30'}`}
                        >
                          ALL BRANDS
                        </button>
                        {brands.map((b) => (
                          <button
                            key={b.id}
                            onClick={() => setFilterBrand(String(b.id))}
                            className={`text-[9px] font-bold uppercase py-1 px-2.5 rounded border transition-colors ${filterBrand === String(b.id) ? 'bg-[#FF4E00] border-transparent text-white' : 'border-white/15 text-white/60 hover:dark:border-white/40'}`}
                          >
                            {b.name}
                          </button>
                        ))}
                      </div>
                    </div>

                    {/* Shoe Categories filter */}
                    <div className="flex flex-col gap-2">
                      <label className="text-[10px] uppercase tracking-widest font-black text-white/40">CATEGORY</label>
                      <div className="flex flex-wrap gap-1.5 mt-1">
                        <button 
                          onClick={() => setFilterCategory('')} 
                          className={`text-[9px] font-bold uppercase py-1 px-2.5 rounded border transition-colors ${!filterCategory ? 'bg-[#FF4E00] border-transparent text-white' : 'border-white/15 text-white/60'}`}
                        >
                          ALL CATEGORIES
                        </button>
                        {categories.map((c) => (
                          <button
                            key={c.id}
                            onClick={() => setFilterCategory(String(c.id))}
                            className={`text-[9px] font-bold uppercase py-1 px-2.5 rounded border transition-colors ${filterCategory === String(c.id) ? 'bg-[#FF4E00] border-transparent text-white' : 'border-white/15 text-white/60'}`}
                          >
                            {c.name}
                          </button>
                        ))}
                      </div>
                    </div>

                    {/* Genders Selector */}
                    <div className="flex flex-col gap-2">
                      <label className="text-[10px] uppercase tracking-widest font-black text-white/40">GENDER</label>
                      <select 
                        value={filterGender} 
                        onChange={(e) => setFilterGender(e.target.value)} 
                        className="bg-white/5 border border-white/10 rounded px-2.5 py-2 text-xs text-white/85 focus:outline-none focus:border-[#FF4E00]"
                      >
                        <option value="all">Unisex / All Genders</option>
                        <option value="men">Men's Footwear</option>
                        <option value="women">Women's Footwear</option>
                        <option value="kids">Kids Selection</option>
                      </select>
                    </div>

                    {/* Price Range selectors */}
                    <div className="flex flex-col gap-2">
                      <div className="flex justify-between items-center">
                        <label className="text-[10px] uppercase tracking-widest font-black text-white/40">PRICE RANGE</label>
                        <span className="text-[10px] font-bold text-[#FF4E00]">${filterMinPrice} - ${filterMaxPrice}</span>
                      </div>
                      <input 
                        type="range" 
                        min="0" 
                        max="350" 
                        value={filterMaxPrice} 
                        onChange={(e) => setFilterMaxPrice(Number(e.target.value))}
                        className="accent-[#FF4E00] cursor-pointer mt-1"
                      />
                    </div>

                    {/* Sorting options select */}
                    <div className="flex flex-col gap-2">
                      <label className="text-[10px] uppercase tracking-widest font-black text-white/40">SORT BY</label>
                      <select 
                        value={sortOption} 
                        onChange={(e) => setSortOption(e.target.value)}
                        className="bg-white/5 border border-white/10 rounded px-2.5 py-2 text-xs text-white/85 focus:outline-none focus:border-[#FF4E00]"
                      >
                        <option value="newest">New Arrivals</option>
                        <option value="price_low_high">Price: Low to High</option>
                        <option value="price_high_low">Price: High to Low</option>
                        <option value="best_selling">Best Sellers</option>
                        <option value="rating">Top Rated</option>
                      </select>
                    </div>

                    <div className="border-t border-white/5 pt-4">
                      <span className="text-[10px] text-white/40 tracking-tight leading-relaxed font-semibold">
                        Free standard shipping on orders over $150.
                      </span>
                    </div>
                  </div>
                </motion.aside>
              )}
            </AnimatePresence>

            {/* Catalog Grid content area */}
            <motion.section 
              layout="position"
              transition={{ type: "spring", stiffness: 300, damping: 30 }}
              className="flex-1 p-8 bg-white/[0.01]"
            >
              <div className="flex flex-col sm:flex-row sm:items-center justify-between border-b border-white/15 pb-4 mb-6 gap-4">
                <div className="flex flex-wrap items-center gap-4">
                  <motion.button 
                    onClick={() => setIsFilterSidebarOpen(!isFilterSidebarOpen)}
                    whileHover={{ scale: 1.03, backgroundColor: "rgba(255, 255, 255, 0.08)" }}
                    whileTap={{ scale: 0.97 }}
                    transition={{ type: "spring", stiffness: 400, damping: 15 }}
                    className="flex items-center gap-2 bg-white/5 border border-white/10 px-4 py-2 rounded text-xs font-bold uppercase tracking-wider transition-colors select-none cursor-pointer"
                    title={isFilterSidebarOpen ? "Hide Filter Sidebar" : "Show Filter Sidebar"}
                    id="catalog-filters-toggle-btn"
                  >
                    <Filter className="w-3.5 h-3.5 text-[#FF4E00]" />
                    <span>Filters</span>
                    {isFilterSidebarOpen ? (
                      <ChevronLeft className="w-3.5 h-3.5 text-white/60 transition-transform" />
                    ) : (
                      <ChevronRight className="w-3.5 h-3.5 text-white/60 transition-transform" />
                    )}
                  </motion.button>
                  <div className="h-4 w-[1px] bg-white/10 hidden sm:block"></div>
                  <div>
                    <h2 className="text-sm font-black uppercase tracking-[0.2em] border-l-2 border-[#FF4E00] pl-4">Shoes Catalog</h2>
                    {(filterBrand !== '' || filterCategory !== '' || filterGender !== 'all' || filterMaxPrice !== 350 || filterMinPrice > 0 || searchQuery !== '') && (
                      <p className="text-[10px] uppercase font-bold text-[#FF4E00] mt-1 pl-4 animate-fade-in">
                        Showing {shoes.length} results
                      </p>
                    )}
                  </div>
                </div>
                {/* Visual feedback of filters */}
                <div className="flex gap-2 text-[10px]">
                  {currentUser?.role === 'admin' && (
                    <button 
                      onClick={() => { resetShoeForm(); setIsProductAddModalOpen(true); }}
                      className="bg-emerald-600 hover:bg-emerald-500 font-bold px-4 py-2 rounded text-xs flex items-center gap-1.5 uppercase tracking-wider"
                    >
                      <Plus className="w-3.5 h-3.5" />
                      Add New Sneaker
                    </button>
                  )}
                </div>
              </div>

              {shoes.length === 0 ? (
                <div className="flex flex-col items-center justify-center p-16 text-center border-2 border-dashed border-white/10 rounded-xl">
                  <ShieldAlert className="w-12 h-12 text-[#FF4E00] mb-4 animate-bounce" />
                  <p className="text-sm font-bold tracking-wider uppercase">No shoes matched your current criteria.</p>
                  <button 
                    onClick={() => {
                      setFilterBrand('');
                      setFilterCategory('');
                      setFilterGender('all');
                      setSearchQuery('');
                      setFilterMaxPrice(350);
                    }}
                    className="mt-4 text-xs font-bold text-[#FF4E00] border-b border-[#FF4E00] uppercase hover:text-white pb-0.5"
                  >
                    Clear Catalog Filters
                  </button>
                </div>
              ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                  {shoes.map((s) => {
                    const savedInWishlist = wishlist.some(item => item.id === s.id);
                    return (
                      <div 
                        key={s.id} 
                        className="bg-white/[0.02] border border-white/10 rounded-xl p-4 hover:bg-white/[0.04] transition-all duration-300 flex flex-col justify-between group cursor-pointer relative"
                        onClick={() => handleSelectShoe(s.id)}
                      >
                        
                        {/* Wishlist toggle absolute icon button */}
                        {currentUser && (
                          <button 
                            onClick={(e) => {
                              e.stopPropagation();
                              toggleWishlist(s.id);
                            }}
                            className={`absolute top-3 right-3 z-10 w-8 h-8 rounded-full flex items-center justify-center border transition-colors ${savedInWishlist ? 'bg-pink-900/30 border-pink-500 text-pink-500' : 'bg-black/40 border-white/10 text-white/50 hover:text-white'}`}
                          >
                            <Heart className="w-4 h-4" fill={savedInWishlist ? 'currentColor' : 'none'} />
                          </button>
                        )}

                        {/* Visual brand indicator absolute icon */}
                        <div className="absolute top-3 left-3 bg-black/50 text-[8px] font-black uppercase tracking-widest px-2.5 py-1 rounded text-[#FF4E00]">
                          {s.brand_name}
                        </div>

                        {/* Sneaker Graphic Wrapper */}
                        <div className="aspect-square bg-white/[0.03] rounded-lg mb-4 flex items-center justify-center relative overflow-hidden group-hover:bg-white/[0.06] transition-colors">
                          <div className="w-32 h-20 transform group-hover:scale-110 group-hover:rotate-1 rotate-[-15deg] transition-transform duration-500">
                            {/* Color mapping vectors */}
                            <SneakerVector styleType={s.category_id === 1 ? 'running' : s.category_id === 2 ? 'basketball' : 'street'} color={s.color} />
                          </div>
                        </div>

                        {/* Sneaker Info rows */}
                        <div className="flex-1 flex flex-col justify-between">
                          <div>
                            <span className="text-[8px] font-black uppercase tracking-widest text-white/40 block mb-0.5">{s.category_name} &bull; {s.gender}</span>
                            <h3 className="text-xs font-bold uppercase tracking-wider text-white group-hover:text-[#FF4E00] transition-colors line-clamp-1">{s.name}</h3>
                            <p className="text-[10px] text-white/50 line-clamp-2 mt-1 min-h-[30px] leading-relaxed">{s.description || 'Premium Footwear Collection.'}</p>
                          </div>

                          <div className="flex items-center justify-between border-t border-white/10 pt-3 mt-3">
                            <div>
                              {s.discount_price ? (
                                <div className="flex items-center gap-2">
                                  <span className="text-xs font-black text-[#FF4E00]">${s.discount_price}</span>
                                  <span className="text-[10px] text-white/35 line-through font-semibold">${s.price}</span>
                                </div>
                              ) : (
                                <span className="text-xs font-black text-white">${s.price}</span>
                              )}
                            </div>
                            <div className="flex items-center gap-1">
                              <span className="text-[10px] font-bold text-yellow-500">★</span>
                              <span className="text-[9px] font-bold text-white/70">{s.rating_average || '5.0'}</span>
                            </div>
                          </div>
                        </div>

                        {/* Secondary action indicators inside cards for admin roles */}
                        {currentUser?.role === 'admin' && (
                          <div className="mt-3 pt-3 border-t border-white/5 flex gap-2" onClick={e=>e.stopPropagation()}>
                            <button 
                              onClick={() => handleAdminEditShoeTrigger(s.id)}
                              className="flex-1 bg-white/5 hover:bg-white/10 text-[9px] font-bold py-1.5 px-2 rounded uppercase border border-white/10 text-white"
                            >
                              Edit Info
                            </button>
                            <button 
                              onClick={() => handleAdminDeleteShoe(s.id)}
                              className="bg-red-950/20 border border-red-920 text-red-500 hover:bg-red-900/40 text-[9px] font-bold py-1.5 px-2.5 rounded uppercase"
                            >
                              Archive
                            </button>
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}

              {/* Brands showcase strip banners */}
              <div className="mt-16 bg-white/[0.01] border border-white/5 p-6 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                  <h4 className="text-xs font-bold uppercase tracking-wide">CURATED BRAND SELECTION</h4>
                  <p className="text-[10px] text-white/40 mt-0.5 uppercase">Direct authentic lines from the world's leading footwear houses.</p>
                </div>
                <div className="flex flex-wrap gap-8 items-center justify-center">
                  <span className="text-xs font-black italic tracking-widest text-white/20 select-none uppercase">NIKE</span>
                  <span className="text-xs font-black italic tracking-widest text-white/20 select-none uppercase">ADIDAS</span>
                  <span className="text-xs font-black italic tracking-widest text-white/20 select-none uppercase">PUMA</span>
                  <span className="text-xs font-black italic tracking-widest text-white/20 select-none">NEW BALANCE</span>
                </div>
              </div>

              {/* User Testimonial modules */}
              <div className="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white/[0.01] p-5 border border-white/5 rounded-xl">
                  <div className="text-yellow-500 text-xs mb-2">★★★★★</div>
                  <p className="text-[10px] italic text-white/70">"Clean silhouettes and premium comfort. A perfect addition to my daily rotation."</p>
                  <span className="text-[8px] font-bold uppercase tracking-widest text-white/40 mt-3 block">- Marcus S.</span>
                </div>
                <div className="bg-white/[0.01] p-5 border border-white/5 rounded-xl">
                  <div className="text-yellow-500 text-xs mb-2">★★★★★</div>
                  <p className="text-[10px] italic text-white/70">"Exceptional craftsmanship. These sneakers feel as good as they look."</p>
                  <span className="text-[8px] font-bold uppercase tracking-widest text-white/40 mt-3 block">- Sarah McC.</span>
                </div>
                <div className="bg-white/[0.01] p-5 border border-white/5 rounded-xl">
                  <div className="text-yellow-500 text-xs mb-2">★★★★★</div>
                  <p className="text-[10px] italic text-white/70">"Minimal design combined with everyday comfort. Highly recommend."</p>
                  <span className="text-[8px] font-bold uppercase tracking-widest text-white/40 mt-3 block">- Jacob B.</span>
                </div>
              </div>
            </motion.section>
          </>
        )}

        {/* VIEW 2: CUSTOMER MY ACCOUNT PORTAL VIEW */}
        {activeTab === 'account' && currentUser && (
          <section className="flex-1 p-8 max-w-5xl mx-auto space-y-10">
            
            {/* Account layout profile header */}
            <div className="flex items-center justify-between border-b border-white/10 pb-6">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-full bg-[#FF4E00] text-white flex items-center justify-center font-black text-lg shadow-xl shadow-[#FF4E00]/15 uppercase">
                  {currentUser.full_name.charAt(0)}
                </div>
                <div>
                  <h2 className="text-lg font-black uppercase tracking-wider">{currentUser.full_name}</h2>
                  <p className="text-[10px] text-white/40 uppercase tracking-widest mt-0.5">{currentUser.email} &bull; {currentUser.phone || 'No phone recorded'}</p>
                </div>
              </div>
              <button 
                onClick={logout}
                className="text-[10px] font-bold text-red-500 border border-red-500/30 rounded-full px-4 py-2 hover:bg-red-500/10 transition-colors uppercase tracking-widest flex items-center gap-1.5"
              >
                <LogOut className="w-3 h-3" />
                Logout Profile
              </button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              
              {/* Profile details managers & Saved Addresses list */}
              <div className="lg:col-span-1 space-y-8">
                
                {/* Addresses lists container */}
                <div className="bg-white/[0.02] border border-white/10 rounded-xl p-5">
                  <h3 className="text-xs font-black uppercase tracking-widest text-[#FF4E00] flex items-center gap-1.5 mb-4 pb-2 border-b border-white/5">
                    <MapPin className="w-4 h-4" />
                    Delivery Address Ledger
                  </h3>
                  {addresses.length === 0 ? (
                    <p className="text-[10px] text-white/40 uppercase font-semibold">No addresses saved yet.</p>
                  ) : (
                    <div className="space-y-3">
                      {addresses.map((a) => (
                        <div key={a.id} className="p-3 bg-black border border-white/10 rounded-lg relative text-xs">
                          {a.is_default && (
                            <span className="absolute top-2.5 right-2.5 bg-[#FF4E00]/10 border border-[#FF4E00]/30 text-[#FF4E00] text-[7px] font-bold px-1.5 py-0.5 rounded tracking-widest uppercase">Default</span>
                          )}
                          <p className="font-bold text-white/90">{a.address_line}</p>
                          <p className="text-[10px] text-white/40 mt-1">{a.city}, {a.country} &bull; {a.postal_code}</p>
                        </div>
                      ))}
                    </div>
                  )}
                  {/* Quick address insert forms */}
                  <form 
                    onSubmit={async (e) => {
                      e.preventDefault();
                      const form = e.currentTarget;
                      const a_line = (form.elements.namedItem('a_line') as HTMLInputElement).value;
                      const a_city = (form.elements.namedItem('a_city') as HTMLInputElement).value;
                      const a_postal = (form.elements.namedItem('a_postal') as HTMLInputElement).value;
                      if (!a_line || !a_city) return;
                      
                      try {
                        const res = await fetch('/api/addresses', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json', ...authHeaders() },
                          body: JSON.stringify({ country: 'United Kingdom', city: a_city, address_line: a_line, postal_code: a_postal, is_default: false })
                        });
                        if (res.ok) {
                          form.reset();
                          triggerToast('New delivery address cataloged.');
                          await fetchAddresses();
                        }
                      } catch {}
                    }}
                    className="mt-4 pt-4 border-t border-white/5 space-y-2"
                  >
                    <span className="text-[9px] uppercase tracking-wider font-black text-white/30 block mb-1">Add Saved Address</span>
                    <input name="a_line" type="text" placeholder="Street Address line" required className="w-full bg-black border border-white/10 rounded text-[10px] p-2 focus:outline-none focus:border-[#FF4E00]" />
                    <div className="grid grid-cols-2 gap-2">
                      <input name="a_city" type="text" placeholder="City" required className="bg-black border border-white/10 rounded text-[10px] p-2 focus:outline-none focus:border-[#FF4E00]" />
                      <input name="a_postal" type="text" placeholder="Post Code" required className="bg-black border border-white/10 rounded text-[10px] p-2 focus:outline-none focus:border-[#FF4E00]" />
                    </div>
                    <button type="submit" className="w-full bg-white/5 border border-white/15 text-[9px] font-bold py-1.5 rounded uppercase hover:bg-[#FF4E00] hover:text-white transition-colors">Save Address</button>
                  </form>
                </div>

                {/* Wishlist quick peek summary */}
                <div className="bg-white/[0.02] border border-white/10 rounded-xl p-5">
                  <h3 className="text-xs font-black uppercase tracking-widest text-[#FF4E00] flex items-center gap-1.5 mb-4 pb-2 border-b border-white/5">
                    <Heart className="w-4 h-4" />
                    Wishlisted Drops ({wishlist.length})
                  </h3>
                  {wishlist.length === 0 ? (
                    <p className="text-[10px] text-white/40 uppercase font-semibold">Your wishlist is empty.</p>
                  ) : (
                    <div className="grid grid-cols-2 gap-2">
                      {wishlist.map((w: any) => (
                        <div 
                          key={w.id} 
                          onClick={() => handleSelectShoe(w.id)}
                          className="p-2 border border-white/10 rounded bg-black/60 group cursor-pointer text-center flex flex-col items-center justify-between"
                        >
                          <div className="w-16 h-10 -rotate-[12deg] mb-1.5">
                            <SneakerVector styleType="street" color={w.color} />
                          </div>
                          <span className="text-[9px] font-bold uppercase tracking-wide text-white group-hover:text-[#FF4E00] block line-clamp-1">{w.name}</span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>

              {/* Order Histories lists (Main) */}
              <div className="lg:col-span-2 space-y-6">
                
                <div className="bg-white/[0.02] border border-white/10 rounded-xl p-5">
                  <h3 className="text-xs font-black uppercase tracking-widest text-[#FF4E00] mb-4 pb-2 border-b border-white/5 flex items-center gap-1.5">
                    <ShoppingBag className="w-4 h-4" />
                    Shoe Order History
                  </h3>
                  
                  {orderHistory.length === 0 ? (
                    <div className="text-center py-10">
                      <ShoppingCart className="w-8 h-8 text-white/20 mx-auto mb-2" />
                      <p className="text-[10px] text-white/40 uppercase font-semibold">You have not logged any purchases yet.</p>
                      <button onClick={() => setActiveTab('catalog')} className="mt-3 bg-white/10 hover:bg-white/20 text-[9px] font-bold py-1.5 px-4 rounded uppercase tracking-wider">Start Browsing</button>
                    </div>
                  ) : (
                    <div className="space-y-4">
                      {orderHistory.map((o) => (
                        <div key={o.id} className="border border-white/10 rounded-lg p-4 bg-black">
                          <div className="flex justify-between items-start border-b border-white/5 pb-3.5 mb-3.5">
                            <div className="text-xs">
                              <span className="text-[10px] text-white/30 uppercase font-black tracking-widest">Order Identifier</span>
                              <p className="font-bold text-white mt-0.5">#ST-{o.id}</p>
                            </div>
                            <div className="text-right text-xs">
                              <span className="text-[10px] text-white/30 uppercase font-black tracking-widest">Tracking Status</span>
                              <div className="mt-1 flex gap-2">
                                <span className={`text-[9px] font-bold tracking-widest uppercase px-2 py-0.5 rounded text-white ${o.status === 'delivered' ? 'bg-emerald-600' : o.status === 'cancelled' ? 'bg-red-600' : 'bg-yellow-600'}`}>
                                  {o.status}
                                </span>
                              </div>
                            </div>
                          </div>

                          {/* Items listed */}
                          <div className="space-y-2">
                            {o.items?.map((item, idx) => (
                              <div key={idx} className="flex justify-between text-xs font-semibold">
                                <div className="text-white/80">
                                  {item.shoe_name} &bull; <span className="text-white/40">Size {item.size_label}</span> <span className="text-white/30">x{item.quantity}</span>
                                </div>
                                <span className="text-[#FF4E00] font-black">${item.price * item.quantity}</span>
                              </div>
                            ))}
                          </div>

                          <div className="border-t border-white/5 mt-4 pt-3 flex justify-between items-center text-xs">
                            <span className="text-white/40">{new Date(o.created_at).toLocaleDateString()} &bull; {o.payment_method}</span>
                            <span className="font-bold text-white uppercase tracking-wider">Total Paid: <span className="text-[#FF4E00] font-black">${o.total_price}</span></span>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>

            </div>
          </section>
        )}

        {/* VIEW 3: ADMINISTRATIVE SYSTEM MONITOR */}
        {activeTab === 'admin' && currentUser?.role === 'admin' && (
          <section className="flex-1 p-8 space-y-10">
            
            {/* Top Stats Banner grid */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-6">
              <div className="bg-white/[0.02]/5 border border-white/10 rounded-xl p-5 text-center">
                <span className="text-[9px] font-black uppercase text-white/45 tracking-widest">Active Store Users</span>
                <p className="text-4xl font-black italic text-[#FF4E00] mt-1">{adminKPIs?.total_users || 0}</p>
              </div>
              <div className="bg-white/[0.02]/5 border border-white/10 rounded-xl p-5 text-center">
                <span className="text-[9px] font-black uppercase text-white/45 tracking-widest">Processed Orders</span>
                <p className="text-4xl font-black italic text-[#FF4E00] mt-1">{adminKPIs?.total_orders || 0}</p>
              </div>
              <div className="bg-white/[0.02]/5 border border-white/10 rounded-xl p-5 text-center">
                <span className="text-[9px] font-black uppercase text-white/45 tracking-widest">Overall Store Revenue</span>
                <p className="text-4xl font-black italic text-[#FF4E00] mt-1">${adminKPIs?.total_revenue?.toFixed(2) || '0.00'}</p>
              </div>
              <div className="bg-white/[0.02]/5 border border-[#FF4E00]/25 rounded-xl p-5 text-center bg-[#FF4E00]/5">
                <span className="text-[9px] font-black uppercase text-[#FF4E00] tracking-widest">Low Stock Alarms</span>
                <p className="text-4xl font-black italic mt-1 text-[#FF4E00]">{adminKPIs?.low_stock_count || 0}</p>
              </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              
              {/* Order Processing / Status Control board */}
              <div className="lg:col-span-2 space-y-6">
                
                <div className="bg-white/[0.02] border border-white/10 rounded-xl p-5">
                  <div className="flex justify-between items-center mb-4 pb-2 border-b border-white/5">
                    <h3 className="text-xs font-black uppercase tracking-widest text-[#FF4E00]">
                      Global Customer Orders Manager
                    </h3>
                    <span className="text-[9px] bg-white/10 text-white font-bold px-2 py-0.5 rounded uppercase tracking-wider font-mono">Live Sync</span>
                  </div>

                  {orderHistory.length === 0 ? (
                    <p className="text-[10px] text-white/40 uppercase font-semibold py-4">No logged store purchases.</p>
                  ) : (
                    <div className="space-y-4">
                      {orderHistory.map((o) => (
                        <div key={o.id} className="p-4 border border-white/10 rounded-lg bg-black text-xs">
                          <div className="flex justify-between items-start mb-3 border-b border-white/5 pb-2">
                            <div>
                              <p className="font-bold text-white text-xs">ST-#{o.id} &bull; {o.buyer_name}</p>
                              <p className="text-[10px] text-white/40 mt-0.5">{o.buyer_email}</p>
                            </div>
                            <div className="text-right">
                              <span className="font-black text-[#FF4E00] text-sm">${o.total_price}</span>
                              <p className="text-[9px] text-white/40 font-mono italic mt-0.5">{new Date(o.created_at).toLocaleDateString()}</p>
                            </div>
                          </div>

                          <div className="space-y-1">
                            {o.items?.map((item, idx) => (
                              <div key={idx} className="text-white/70">
                                {item.shoe_name} (Size {item.size_label}) &bull; x{item.quantity} - <span className="text-white/40">${item.price} each</span>
                              </div>
                            ))}
                          </div>

                          <div className="mt-4 pt-3 border-t border-white/5 flex flex-wrap justify-between items-center gap-2">
                            <span className="text-[10px] text-white/50 bg-white/5 px-2 py-1 rounded border border-white/5">
                              Ship: {o.address?.address_line}, {o.address?.city}
                            </span>
                            
                            <div className="flex items-center gap-1.5 font-semibold text-[9px] uppercase tracking-wider">
                              <span className="text-white/40">Status:</span>
                              <select 
                                value={o.status}
                                onChange={(e) => handleAdminStatusChange(o.id, e.target.value)}
                                className="bg-white/5 border border-white/10 text-white rounded px-2 py-1 text-[10px]"
                              >
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                              </select>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {/* Audit Actions Logs tracking trail */}
                <div className="bg-white/[0.02] border border-white/10 rounded-xl p-5 text-sm">
                  <h3 className="text-xs font-black uppercase tracking-widest text-[#FF4E00] mb-3 pb-2 border-b border-white/5 flex items-center gap-1.5">
                    <Activity className="w-4 h-4" />
                    Admin Session Activity Audit Logs
                  </h3>
                  <div className="space-y-2 max-h-[220px] overflow-y-auto">
                    {adminKPIs?.recent_logs?.map((l) => (
                      <div key={l.id} className="p-2 border border-white/5 rounded bg-black/50 text-[10px] flex justify-between items-center">
                        <span className="text-white/80 font-medium font-semibold">{l.action}</span>
                        <span className="text-[9px] text-white/35 font-mono">{new Date(l.created_at).toLocaleTimeString()}</span>
                      </div>
                    ))}
                  </div>
                </div>

              </div>

              {/* Customers tables, Block & Purge console */}
              <div className="lg:col-span-1 space-y-6">
                
                <div className="bg-white/[0.02] border border-white/10 rounded-xl p-5">
                  <h3 className="text-xs font-black uppercase tracking-widest text-[#FF4E00] mb-4 pb-2 border-b border-white/5">
                    Customers Directory
                  </h3>
                  
                  <div className="relative mb-3.5">
                    <input 
                      type="text" 
                      placeholder="SEARCH USERS..."
                      value={adminSearchCustomer}
                      onChange={(e) => { setAdminSearchCustomer(e.target.value); fetchAdminCustomers(e.target.value); }}
                      className="w-full bg-black border border-white/10 rounded py-1.5 pl-3 pr-8 text-[10px] tracking-widest focus:outline-none focus:border-[#FF4E00] uppercase"
                    />
                  </div>

                  {adminCustomers.length === 0 ? (
                    <p className="text-[10px] text-white/40 uppercase font-semibold">No customers registered.</p>
                  ) : (
                    <div className="space-y-3.5">
                      {adminCustomers.map((user) => (
                        <div key={user.id} className="p-3 border border-white/10 rounded bg-black text-xs">
                          <p className="font-bold text-white leading-tight">{user.full_name}</p>
                          <p className="text-[9px] text-white/40 font-semibold">{user.email}</p>
                          {user.phone && <p className="text-[9px] text-white/30 tracking-tight mt-0.5">Phone: {user.phone}</p>}

                          <div className="mt-3.5 pt-2.5 border-t border-white/5 flex gap-2.5 justify-end">
                            <button
                              onClick={() => handleAdminCustomerToggleBlock(user.id)}
                              className={`text-[9px] font-bold py-1 px-2.5 rounded uppercase tracking-widest ${user.status === 'blocked' ? 'bg-emerald-900/30 border border-emerald-500 text-emerald-500 hover:bg-emerald-800' : 'bg-red-950/20 border border-red-910 text-red-500 hover:bg-red-900/40'}`}
                            >
                              {user.status === 'blocked' ? 'Unblock' : 'Block'}
                            </button>
                            <button 
                              onClick={() => handleAdminCustomerDelete(user.id)}
                              className="text-[9px] font-bold py-1 px-2.5 rounded bg-red-600/10 text-red-500 hover:bg-red-600 hover:text-white transition-colors uppercase tracking-widest"
                            >
                              Purge
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {/* Best Sellers ranks table */}
                <div className="bg-white/[0.02] border border-white/10 rounded-xl p-5">
                  <h3 className="text-xs font-black uppercase tracking-widest text-[#FF4E00] mb-3 pb-2 border-b border-white/5 flex items-center gap-1.5">
                    <Award className="w-4 h-4" />
                    Best-Selling Sneakers Rank
                  </h3>
                  <div className="space-y-3">
                    {adminKPIs?.best_sellers?.map((sh, index) => (
                      <div key={index} className="flex justify-between items-center text-xs">
                        <div className="font-medium">
                          <span className="text-white/40 mr-1.5 font-bold">{index+1}.</span> 
                          <span className="uppercase text-white">{sh.name}</span>
                        </div>
                        <span className="text-[10px] font-bold text-emerald-500 font-mono">
                          {sh.sold} Sales &bull; ${sh.revenue}
                        </span>
                      </div>
                    ))}
                    {!adminKPIs?.best_sellers?.length && (
                      <p className="text-[10px] text-white/40 uppercase font-semibold">No recorded sales data.</p>
                    )}
                  </div>
                </div>

              </div>

            </div>
          </section>
        )}

      </main>

      {/* ==========================================
          SHARED STICKY SITE FOOTER WRAPPER
         ========================================== */}
      <footer className="mt-auto bg-black border-t border-white/10 py-10 px-12 flex flex-col md:flex-row items-center justify-between gap-6 z-10">
        <div className="flex gap-12 text-white/20 select-none">
          <span className="text-[10px] font-black italic tracking-widest uppercase">STYLE</span>
          <span className="text-[10px] font-black italic tracking-widest uppercase">MOVEMENT</span>
          <span className="text-[10px] font-black italic tracking-widest uppercase">COMFORT</span>
          <span className="text-[10px] font-black italic tracking-widest uppercase">CRAFTSMANSHIP</span>
        </div>
        <div className="text-[10px] font-semibold text-white/30 uppercase tracking-[0.2em]">
          &copy; 2026 STRIDEHUB. ALL RIGHTS RESERVED.
        </div>
      </footer>

      {/* ==========================================
          MODAL 1: SNEAKER PRODUCT SPECIFICS & REVIEWS
         ========================================== */}
      {selectedShoeId && (
        <div className="fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
          <div className="bg-[#0b0b0b] border border-white/10 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto flex flex-col relative animate-fade-in text-white shadow-2xl">
            
            {/* Absolute Close triggers */}
            <button 
              onClick={() => setSelectedShoeId(null)} 
              className="absolute top-4 right-4 z-[101] w-8 h-8 rounded-full border border-white/10 flex items-center justify-center hover:bg-white/10 bg-black cursor-pointer text-white"
            >
              <X className="w-4 h-4" />
            </button>

            {!selectedShoe ? (
              <div className="p-16 text-center text-xs tracking-widest uppercase animate-pulse text-[#FF4E00]">
                Loading product details...
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 p-8 gap-8">
                
                {/* Visual side illustrations and colors */}
                <div className="space-y-6">
                  <div className="aspect-square bg-white/[0.04] border border-white/5 rounded-xl flex items-center justify-center p-6 relative">
                    <div className="absolute top-3 left-3 bg-black/60 border border-white/10 text-xs px-2.5 py-1 rounded font-bold uppercase tracking-widest text-[#FF4E00]">{selectedShoe.brand_name}</div>
                    <div className="w-56 h-36 -rotate-[15deg]">
                      <SneakerVector styleType={selectedShoe.category_id === 1 ? 'running' : selectedShoe.category_id === 2 ? 'basketball' : 'street'} color={selectedShoe.color} />
                    </div>
                  </div>

                  <div className="grid grid-cols-4 gap-2">
                    <div className="border border-[#FF4E00] rounded-lg p-1 bg-black text-center aspect-square flex items-center justify-center">
                      <div className="w-10 h-6 -rotate-[10deg]"><SneakerVector styleType="runner" color={selectedShoe.color} /></div>
                    </div>
                    <div className="border border-white/5 rounded-lg p-1 bg-black text-center aspect-square opacity-40 flex items-center justify-center">
                      <div className="w-10 h-6 -rotate-[10deg]"><SneakerVector styleType="runner" color={selectedShoe.color} /></div>
                    </div>
                    <div className="border border-white/5 rounded-lg p-1 bg-black text-center aspect-square opacity-40 flex items-center justify-center">
                      <div className="w-10 h-6 -rotate-[10deg]"><SneakerVector styleType="runner" color={selectedShoe.color} /></div>
                    </div>
                    <div className="border border-white/5 rounded-lg p-1 bg-black text-center aspect-square opacity-40 flex items-center justify-center">
                      <div className="w-10 h-6 -rotate-[10deg]"><SneakerVector styleType="runner" color={selectedShoe.color} /></div>
                    </div>
                  </div>

                  {/* Size Manual / Size Guidelines indicator */}
                  <div className="p-4 bg-white/[0.01] border border-white/5 rounded-lg text-[10px] space-y-1">
                    <span className="font-bold uppercase tracking-wide text-white/55 block">SIZING GUIDE</span>
                    <p className="text-white/40 leading-relaxed">Sizing is true to standard sizes. We recommend selecting your true size, or sizing up half a size if you prefer a wider fit.</p>
                  </div>
                </div>

                {/* Technical data metrics details column */}
                <div className="space-y-6">
                  
                  <div>
                    <span className="text-[10px] font-bold text-white/40 tracking-widest uppercase block mb-1">{selectedShoe.category_name} &bull; {selectedShoe.gender}'s</span>
                    <h2 className="text-xl md:text-2xl font-black uppercase tracking-wider">{selectedShoe.name}</h2>
                    <p className="text-xs text-white/50 lowercase mt-1 text-slate-400 capitalize">&bull; Colorway: {selectedShoe.color} &bull; Material: {selectedShoe.material}</p>
                  </div>

                  <div className="flex items-center gap-4">
                    {selectedShoe.discount_price ? (
                      <div className="flex items-baseline gap-2.5">
                        <span className="text-2xl font-black text-[#FF4E00]">${selectedShoe.discount_price}</span>
                        <span className="text-xs text-white/35 line-through font-semibold">${selectedShoe.price}</span>
                      </div>
                    ) : (
                      <span className="text-xl font-black text-white">${selectedShoe.price}</span>
                    )}
                    <span className="bg-emerald-600/10 border border-emerald-500/35 text-emerald-500 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-widest">In Stock</span>
                  </div>

                  <p className="text-xs text-white/70 leading-relaxed uppercase tracking-wide text-[10px] normal-case bg-black p-3 rounded.5">{selectedShoe.description || 'Exclusive footwear designed with premium materials and cushioning for all-day comfort.'}</p>

                  {/* Operational Size options selecting */}
                  <div>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-white/40 block mb-2">SELECT SIZE</span>
                    <div className="grid grid-cols-4 gap-2">
                      {selectedShoe.sizes?.map((sz: ShoeSize) => {
                        const inStock = sz.stock_quantity > 0;
                        return (
                          <button
                            key={sz.id}
                            disabled={!inStock}
                            onClick={() => {
                              addToCart(selectedShoe.id, sz.id, 1);
                              setSelectedShoeId(null);
                              setIsCartOpen(true);
                            }}
                            className={`py-2 text-[11px] font-bold rounded text-center border transition-all ${inStock ? 'cursor-pointer hover:border-[#FF4E00] hover:text-[#FF4E00] bg-black border-white/10 text-white' : 'bg-white/5 border-transparent text-white/20 line-through'}`}
                          >
                            Sz {sz.size}
                            <span className="block text-[7px] text-white/40 font-normal mt-0.5">{inStock ? `${sz.stock_quantity} Left` : 'Sold out'}</span>
                          </button>
                        );
                      })}
                    </div>
                  </div>

                  {/* Peer Reviews lists segment block */}
                  <div className="border-t border-white/10 pt-6 space-y-4">
                    <span className="text-xs font-black uppercase tracking-widest text-[#FF4E00] flex items-center gap-1">
                      ★ REVIEWS
                    </span>
                    
                    <div className="space-y-2.5 max-h-[160px] overflow-y-auto">
                      {selectedShoe.reviews?.length === 0 ? (
                        <p className="text-[10px] text-white/30 uppercase font-semibold">No reviews yet.</p>
                      ) : (
                        selectedShoe.reviews?.map((rev: Review) => (
                          <div key={rev.id} className="p-2.5 border border-white/5 bg-black/60 rounded text-xs relative">
                            <div className="flex justify-between items-baseline mb-1">
                              <span className="font-bold text-white/90">{rev.reviewer_name}</span>
                              <span className="text-[10px] font-bold text-yellow-500">{'★'.repeat(rev.rating)}</span>
                            </div>
                            <p className="text-[10px] text-white/60 font-semibold">{rev.comment}</p>
                            
                            {currentUser?.role === 'admin' && (
                              <button 
                                onClick={() => handleAdminDeleteReview(rev.id, selectedShoe.id)}
                                className="absolute bottom-1 right-2 text-[8px] font-bold text-red-500 uppercase tracking-widest hover:underline"
                              >
                                Delete
                              </button>
                            )}
                          </div>
                        ))
                      )}
                    </div>

                    {/* Review submit forms */}
                    {token ? (
                      <form onSubmit={handleSubmitReview} className="space-y-2">
                        <textarea 
                          placeholder="Write a review..." 
                          value={newComment}
                          onChange={(e) => setNewComment(e.target.value)}
                          required
                          className="w-full bg-black border border-white/10 rounded text-xs p-2 focus:outline-none focus:border-[#FF4E00] min-h-[50px] text-white"
                        />
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-1">
                            <span className="text-[10px] text-white/40 font-bold uppercase tracking-wider mr-1">Rating:</span>
                            {[1, 2, 3, 4, 5].map((num) => (
                              <button 
                                key={num}
                                type="button" 
                                onClick={() => setNewRating(num)}
                                className={`text-[11px] font-bold mx-0.5 px-1 rounded ${newRating >= num ? 'text-yellow-500' : 'text-white/30'}`}
                              >
                                ★
                              </button>
                            ))}
                          </div>
                          <button type="submit" className="bg-[#FF4E00] text-white font-bold text-[9px] px-4 py-2 hover:bg-[#FF5D14] uppercase rounded tracking-widest">POST REVIEW</button>
                        </div>
                      </form>
                    ) : (
                      <p className="text-[9px] text-[#FF4E00] uppercase tracking-widest font-black leading-tight text-center">Sign in to write a review.</p>
                    )}
                  </div>

                </div>

              </div>
            )}
          </div>
        </div>
      )}

      {/* ==========================================
          MODAL 2: USER AUTHENTICATION GATEWAY
         ========================================== */}
      {isAuthModalOpen && (
        <div className="fixed inset-0 z-[150] bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
          <div className="bg-[#0b0b0b] border border-white/10 rounded-2xl max-w-sm w-full p-6 relative">
            
            <button 
              onClick={() => setIsAuthModalOpen(false)} 
              className="absolute top-4 right-4 text-white/50 hover:text-white"
            >
              ✕
            </button>

            <div className="border-l-2 border-[#FF4E00] pl-3 mb-6">
              <h3 className="text-lg font-black tracking-wider uppercase text-white">
                {authMode === 'signin' ? 'Welcome Back' : 'Create Your Account'}
              </h3>
              <p className="text-[10px] text-white/40 uppercase tracking-wider mt-1">
                {authMode === 'signin' ? 'Sign in to continue shopping.' : 'Join STRIDEHUB and discover premium footwear.'}
              </p>
            </div>

            {authError && (
              <div className="mb-4 bg-red-950/20 border border-red-500/20 text-red-500 text-[10px] font-bold px-3 py-2 rounded uppercase tracking-wider">
                {authError}
              </div>
            )}

            <form onSubmit={authMode === 'signin' ? login : register} className="space-y-4">
              
              {authMode === 'signup' && (
                <div>
                  <label className="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1">Full Name</label>
                  <input 
                    type="text" 
                    placeholder="Enter full name" 
                    value={authFullName}
                    onChange={(e) => setAuthFullName(e.target.value)}
                    required 
                    className="w-full bg-white/5 border border-white/10 rounded py-2 px-3 text-xs text-white focus:outline-none focus:border-[#FF4E00]" 
                  />
                </div>
              )}

              <div>
                <label className="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1">Email Address</label>
                <input 
                  type="email" 
                  placeholder="name@server.com" 
                  value={authEmail}
                  onChange={(e) => setAuthEmail(e.target.value)}
                  required 
                  className="w-full bg-white/5 border border-white/10 rounded py-2 px-3 text-xs text-white focus:outline-none focus:border-[#FF4E00]" 
                />
              </div>

              <div>
                <label className="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1">Passcode</label>
                <input 
                  type="password" 
                  placeholder="Minimum 6 characters" 
                  value={authPassword}
                  onChange={(e) => setAuthPassword(e.target.value)}
                  required 
                  className="w-full bg-white/5 border border-white/10 rounded py-2 px-3 text-xs text-white focus:outline-none focus:border-[#FF4E00]" 
                />
              </div>

              {authMode === 'signup' && (
                <div>
                  <label className="text-[9px] tracking-widest font-black text-white/40 uppercase block mb-1">Mobile Contact Phone (Optional)</label>
                  <input 
                    type="text" 
                    placeholder="Phone prefix number" 
                    value={authPhone}
                    onChange={(e) => setAuthPhone(e.target.value)}
                    className="w-full bg-white/5 border border-white/10 rounded py-2 px-3 text-xs text-white focus:outline-none focus:border-[#FF4E00]" 
                  />
                </div>
              )}

              <button 
                type="submit" 
                className="w-full bg-[#FF4E00] text-white hover:bg-[#FF5D14] font-bold text-xs py-3.5 rounded tracking-widest uppercase transition-colors pointer-events-auto"
              >
                {authMode === 'signin' ? 'SIGN IN' : 'JOIN STRIDEHUB'}
              </button>
            </form>

            <div className="mt-6 border-t border-white/5 pt-4 text-center text-[10px]">
              {authMode === 'signin' ? (
                <button 
                  onClick={() => { setAuthMode('signup'); setAuthError(''); }} 
                  className="text-white/40 hover:text-white uppercase font-bold tracking-wider"
                >
                  New to StrideHub? <span className="text-[#FF4E00] hover:underline">Join Now</span>
                </button>
              ) : (
                <button 
                  onClick={() => { setAuthMode('signin'); setAuthError(''); }} 
                  className="text-white/40 hover:text-white uppercase font-bold tracking-wider"
                >
                  Already a member? <span className="text-[#FF4E00] hover:underline">Sign In</span>
                </button>
              )}
            </div>
            
            {/* Quick access info triggers */}
            <div className="mt-4 pt-3.5 border-t border-white/5 text-center">
              <span className="text-[8px] font-mono select-none block text-white/30 uppercase">Demo Access credentials:</span>
              <span className="text-[8px] font-mono select-none block text-white/30 mt-0.5">ADMIN: admin@stridehub.com / admin123</span>
              <span className="text-[8px] font-mono select-none block text-white/30">CUST: customer@stridehub.com / customer123</span>
            </div>

          </div>
        </div>
      )}

      {/* ==========================================
          MODAL 3: SHOPPING BAG DRAWERS & SECURE CHECKOUT
         ========================================== */}
      {isCartOpen && (
        <div className="fixed inset-0 z-[160] bg-black/80 backdrop-blur-sm flex justify-end">
          <div className="bg-[#080808] border-l border-white/10 w-full max-w-md h-full flex flex-col justify-between p-6 overflow-y-auto">
            
            <div className="space-y-6">
              <div className="flex justify-between items-center border-b border-white/10 pb-4">
                <span className="text-xs font-black uppercase tracking-widest text-[#FF4E00] flex items-center gap-1.5">
                  <ShoppingBag className="w-5 h-5" />
                  SHOPPING BAG
                </span>
                <button onClick={() => setIsCartOpen(false)} className="text-xs uppercase font-bold tracking-widest text-white/50 hover:text-white p-1">CLOSE</button>
              </div>

              {/* Cart Items listing */}
              {cartItems.length === 0 ? (
                <div className="text-center py-16 text-xs text-white/30 uppercase font-black tracking-wider space-y-2">
                  <ShoppingCart className="w-8 h-8 text-white/10 mx-auto" />
                  <p>Your bag is empty.</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {cartItems.map((item) => (
                    <div key={item.id} className="flex gap-4 p-3 bg-[#0d0d0c] rounded-lg border border-white/5">
                      <div className="w-16 h-12 bg-white/5 border border-white/5 rounded aspect-square flex items-center justify-center p-1 relative">
                        <div className="w-12 h-8 -rotate-[12deg]">
                          <SneakerVector styleType="street" color={item.shoe?.discount_price ? 'Cyber' : 'Orange'} />
                        </div>
                      </div>

                      <div className="flex-1 text-xs space-y-0.5">
                        <h4 className="font-bold text-white uppercase tracking-wider">{item.shoe?.name || 'Nike Sneaker'}</h4>
                        <p className="text-[9px] text-white/40 uppercase tracking-widest">Selected Size: {item.size}</p>
                        <div className="flex justify-between items-baseline pt-2">
                          <span className="font-black text-[#FF4E00]">
                            ${item.shoe?.discount_price !== null ? item.shoe?.discount_price : item.shoe?.price}
                          </span>
                          <button 
                            onClick={() => removeCartItem(item.id)}
                            className="text-red-500 hover:text-red-400 font-bold tracking-widest text-[8px] uppercase font-mono"
                          >
                            Remove
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}

                  {/* Pricing segment block totals and shipping costs */}
                  <div className="border-t border-white/10 pt-4 space-y-2 text-xs">
                    
                    <div className="flex justify-between font-semibold">
                      <span className="text-white/50">Subtotal:</span>
                      <span className="font-bold text-white">${cartSubtotal}</span>
                    </div>

                    {appliedCoupon ? (
                      <div className="flex justify-between text-emerald-500 font-bold">
                        <span>Discount ({appliedCoupon.code} -{appliedCoupon.discount_percent}%):</span>
                        <span>-${couponDiscountAmount.toFixed(2)}</span>
                      </div>
                    ) : (
                      <div className="flex gap-2 relative mt-4">
                        <input 
                          type="text" 
                          placeholder="PROMO CODE" 
                          value={couponCodeInput}
                          onChange={(e)=>setCouponCodeInput(e.target.value)}
                          className="flex-1 bg-black border border-white/10 rounded text-[10px] py-1.5 px-2.5 uppercase text-white focus:outline-none focus:border-[#FF4E00]"
                        />
                        <button 
                          type="button" 
                          onClick={handleValidateCoupon}
                          className="bg-white/10 text-white hover:bg-[#FF4E00] text-[8px] font-bold px-3.5 uppercase tracking-widest rounded"
                        >
                          APPLY
                        </button>
                      </div>
                    )}

                    <div className="flex justify-between font-semibold">
                      <span className="text-white/50">Shipping:</span>
                      <span className="font-bold text-white">{shippingCosts === 0.00 ? 'FREE' : `$${shippingCosts}`}</span>
                    </div>

                    <div className="flex justify-between border-t border-white/5 pt-3 text-sm">
                      <span className="font-black text-white uppercase tracking-wider">Total:</span>
                      <span className="font-black text-[#FF4E00] text-sm">${orderFinalTotal}</span>
                    </div>
                  </div>
                </div>
              )}
            </div>

            <div className="pt-6 border-t border-white/10">
              {cartItems.length > 0 && (
                <>
                  {!isCheckoutOpen ? (
                    <button 
                      onClick={() => setIsCheckoutOpen(true)}
                      className="w-full bg-[#FF4E00] hover:bg-[#FF5D14] text-white py-4 font-bold text-xs uppercase tracking-widest rounded transition-colors"
                    >
                      SECURE CHECKOUT
                    </button>
                  ) : (
                    
                    /* Mini Checkout Submission Form */
                    <form onSubmit={handlePlaceOrder} className="space-y-4">
                      <span className="text-[10px] font-black uppercase text-[#FF4E00] tracking-widest block pb-1 border-b border-white/5">SHIPPING DETAILS</span>
                      
                      <div>
                        <input 
                          type="text" 
                          placeholder="SHIPPING ADDRESS" 
                          value={shippingAddressLine}
                          onChange={(e)=>setShippingAddressLine(e.target.value)}
                          required 
                          className="w-full bg-black border border-white/10 rounded text-xs p-2.5 focus:outline-none focus:border-[#FF4E00] text-white" 
                        />
                      </div>

                      <div className="grid grid-cols-2 gap-2">
                        <input 
                          type="text" 
                          placeholder="CITY" 
                          value={shippingCity}
                          onChange={(e)=>setShippingCity(e.target.value)}
                          required 
                          className="bg-black border border-white/10 rounded text-xs p-2.5 focus:outline-none focus:border-[#FF4E00] text-white" 
                        />
                        <input 
                          type="text" 
                          placeholder="POSTAL CODE" 
                          value={shippingPostalCode}
                          onChange={(e)=>setShippingPostalCode(e.target.value)}
                          required 
                          className="bg-black border border-white/10 rounded text-xs p-2.5 focus:outline-none focus:border-[#FF4E00] text-white" 
                        />
                      </div>

                      <div>
                        <input 
                          type="text" 
                          placeholder="PHONE NUMBER" 
                          value={shippingPhone}
                          onChange={(e)=>setShippingPhone(e.target.value)}
                          required 
                          className="w-full bg-black border border-white/10 rounded text-xs p-2.5 focus:outline-none focus:border-[#FF4E00] text-white" 
                        />
                      </div>

                      <div className="space-y-1 text-xs">
                        <span className="text-[9px] uppercase font-bold text-white/40 block">PAYMENT METHOD</span>
                        <div className="grid grid-cols-2 gap-2">
                          <button 
                            type="button" 
                            onClick={()=>setSelectedPaymentMethod('Card Payment')}
                            className={`p-2 text-[10px] font-bold uppercase rounded border text-center transition-colors ${selectedPaymentMethod === 'Card Payment' ? 'border-[#FF4E00] text-[#FF4E00] bg-black' : 'border-white/10 text-white/50 bg-transparent'}`}
                          >
                            CREDIT CARD
                          </button>
                          <button 
                            type="button" 
                            onClick={()=>setSelectedPaymentMethod('Cash On Delivery')}
                            className={`p-2 text-[10px] font-bold uppercase rounded border text-center transition-colors ${selectedPaymentMethod === 'Cash On Delivery' ? 'border-[#FF4E00] text-[#FF4E00] bg-black' : 'border-white/10 text-white/50 bg-transparent'}`}
                          >
                            CASH ON DELIVERY
                          </button>
                        </div>
                      </div>

                      <button 
                        type="submit"
                        className="w-full bg-[#FF4E00] hover:bg-[#FF5D14] text-white py-4 font-bold text-xs uppercase tracking-widest rounded transition-all mt-2"
                      >
                        PLACE ORDER &bull; ${orderFinalTotal}
                      </button>
                    </form>
                  )}
                </>
              )}
            </div>

          </div>
        </div>
      )}

      {/* ==========================================
          MODAL 4: ADMIN CREATE / EDIT PRODUCT METADATA
         ========================================== */}
      {isProductAddModalOpen && (
        <div className="fixed inset-0 z-[170] bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
          <div className="bg-[#0b0b0b] border border-white/10 rounded-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto relative text-white">
            
            <button 
              onClick={() => { setIsProductAddModalOpen(false); setEditingShoeId(null); }} 
              className="absolute top-4 right-4 text-white/50 hover:text-white"
            >
              ✕
            </button>

            <h3 className="text-base font-black tracking-widest uppercase border-l-2 border-[#FF4E00] pl-3 mb-6">
              {editingShoeId ? 'Edit Shoe Metadata Profile' : 'Drop New Sneaker Release'}
            </h3>

            <form onSubmit={handleAddNewShoeSubmit} className="space-y-4 text-xs">
              
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-[9px] tracking-widest uppercase font-black text-white/40 block mb-1">Shoe Model Name</label>
                  <input 
                    type="text" 
                    value={newShoeName}
                    onChange={(e)=>setNewShoeName(e.target.value)}
                    required
                    className="w-full bg-white/5 border border-white/10 rounded p-2 text-xs focus:outline-none focus:border-[#FF4E00] text-white" 
                  />
                </div>
                <div>
                  <label className="text-[9px] tracking-widest uppercase font-black text-white/40 block mb-1">Brand Index</label>
                  <select 
                    value={newShoeBrand} 
                    onChange={(e)=>setNewShoeBrand(Number(e.target.value))}
                    className="w-full bg-white/5 border border-white/10 rounded p-2 text-xs focus:outline-none text-white"
                  >
                    {brands.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-[9px] tracking-widest uppercase font-black text-white/40 block mb-1">Category Index</label>
                  <select 
                    value={newShoeCategory} 
                    onChange={(e)=>setNewShoeCategory(Number(e.target.value))}
                    className="w-full bg-white/5 border border-white/10 rounded p-2 text-xs focus:outline-none text-white"
                  >
                    {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                  </select>
                </div>
                <div>
                  <label className="text-[9px] tracking-widest uppercase font-black text-white/40 block mb-1">Gender Focus group</label>
                  <select 
                    value={newShoeGender} 
                    onChange={(e)=>setNewShoeGender(e.target.value as any)}
                    className="w-full bg-white/5 border border-white/10 rounded p-2 text-xs focus:outline-none text-white"
                  >
                    <option value="unisex">Unisex</option>
                    <option value="men">Men's</option>
                    <option value="women">Women's</option>
                    <option value="kids">Kids'</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="text-[9px] tracking-widest uppercase font-black text-white/40 block mb-1">Long description / specs details</label>
                <textarea 
                  value={newShoeDescription}
                  onChange={(e)=>setNewShoeDescription(e.target.value)}
                  className="w-full bg-white/5 border border-white/10 rounded p-2 text-[10px] min-h-[50px] focus:outline-none text-white" 
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-[9px] tracking-widest uppercase font-black text-white/40 block mb-1">Retail Price ($)</label>
                  <input 
                    type="number" 
                    value={newShoePrice}
                    onChange={(e)=>setNewShoePrice(Number(e.target.value))}
                    required
                    className="w-full bg-white/5 border border-white/10 rounded p-2 text-xs focus:outline-none text-white" 
                  />
                </div>
                <div>
                  <label className="text-[9px] tracking-widest uppercase font-black text-white/40 block mb-1">Promo Discount Price ($) (Optional)</label>
                  <input 
                    type="number" 
                    value={newShoeDiscount}
                    onChange={(e)=>setNewShoeDiscount(e.target.value === '' ? '' : Number(e.target.value))}
                    placeholder="None"
                    className="w-full bg-white/5 border border-white/10 rounded p-2 text-xs focus:outline-none text-white" 
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-[9px] tracking-widest uppercase font-black text-white/40 block mb-1">Color Theme descriptor</label>
                  <input 
                    type="text" 
                    value={newShoeColor}
                    onChange={(e)=>setNewShoeColor(e.target.value)}
                    placeholder="e.g. Cobalt Cyan/Black"
                    required
                    className="w-full bg-white/5 border border-white/10 rounded p-2 text-xs focus:outline-none text-white" 
                  />
                </div>
                <div>
                  <label className="text-[9px] tracking-widest uppercase font-black text-white/40 block mb-1">Material Composition</label>
                  <input 
                    type="text" 
                    value={newShoeMaterial}
                    onChange={(e)=>setNewShoeMaterial(e.target.value)}
                    placeholder="e.g. Suede & Mesh Grid"
                    className="w-full bg-white/5 border border-white/10 rounded p-2 text-xs focus:outline-none text-white" 
                  />
                </div>
              </div>

              {/* Set sizing layout table stocks */}
              <div className="border-t border-white/5 pt-3">
                <span className="text-[9px] tracking-widest font-black uppercase text-white/40 block mb-1.5">Nike Stock Quantities Per Size Dimensions</span>
                <div className="grid grid-cols-4 gap-2">
                  {newShoeSizesInput.map((sz, index) => (
                    <div key={index} className="p-2 border border-white/5 bg-black rounded text-center">
                      <span className="text-[9px] text-[#FF4E00] font-bold block mb-1">Size {sz.size}</span>
                      <input 
                        type="number" 
                        value={sz.stock_quantity}
                        onChange={(e) => {
                          const updated = [...newShoeSizesInput];
                          updated[index].stock_quantity = Math.max(0, Number(e.target.value) || 0);
                          setNewShoeSizesInput(updated);
                        }}
                        className="bg-white/5 border border-white/10 rounded w-full p-1 text-[10px] text-center"
                      />
                    </div>
                  ))}
                </div>
              </div>

              {/* Dynamic Product Image Upload (Direct Files) */}
              <div className="border-t border-white/5 pt-3">
                <div className="flex justify-between items-center mb-1.5">
                  <span className="text-[9px] tracking-widest font-black uppercase text-white/40">Product Pictures (Direct Upload - Min. 5 Required)</span>
                  <button 
                    type="button"
                    onClick={() => setNewShoeImages([...newShoeImages, ''])}
                    className="text-[8px] bg-[#FF4E00]/10 border border-[#FF4E00]/25 text-[#FF4E00] px-2 py-0.5 rounded tracking-wider uppercase font-bold hover:bg-[#FF4E00]/20 transition-colors"
                  >
                    + Add More Slots
                  </button>
                </div>
                <div className="space-y-3 max-h-56 overflow-y-auto pr-1">
                  {newShoeImages.map((imgUrl, idx) => (
                    <div key={idx} className="flex gap-2 items-start bg-white/[0.01] p-2 border border-white/5 rounded-lg">
                      <div className="text-[9px] text-white/40 select-none w-14 pt-2 shrink-0">
                        Pic #{idx + 1}
                        {idx < 5 && <strong className="text-[#FF4E00] ml-0.5">*</strong>}
                      </div>

                      <div className="flex-1">
                        {imgUrl ? (
                          <div className="flex items-center gap-2 bg-black/40 border border-white/10 p-1.5 rounded w-full">
                            <img src={imgUrl} alt="Preview" className="w-10 h-10 object-contain bg-black border border-white/10 rounded shrink-0" />
                            <div className="flex-1 overflow-hidden">
                              <span className="text-[8px] text-emerald-400 block font-bold font-mono uppercase tracking-wide">Cataloged</span>
                              <span className="text-[9px] text-white/50 block font-mono truncate">{imgUrl.split('/').pop()}</span>
                            </div>
                            <button
                              type="button"
                              onClick={() => {
                                const updated = [...newShoeImages];
                                updated[idx] = '';
                                setNewShoeImages(updated);
                              }}
                              className="text-[8px] text-red-400 hover:text-red-300 tracking-wider uppercase font-black bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 px-2 py-1 rounded transition-colors"
                            >
                              Reset
                            </button>
                          </div>
                        ) : (
                          <div className="relative flex items-center justify-center border border-dashed border-white/20 hover:border-[#FF4E00] hover:bg-white/[0.02] p-2.5 rounded w-full transition-all cursor-pointer">
                            <input 
                              type="file" 
                              accept="image/*"
                              onChange={(e) => handleImageUpload(idx, e)}
                              className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                            />
                            <div className="text-center py-0.5">
                              <span className="text-[9px] text-white/60 block font-bold">Select computer file</span>
                              <span className="text-[7px] text-white/40 block font-mono uppercase mt-0.5">PNG, JPG, WEBP formats</span>
                            </div>
                          </div>
                        )}
                      </div>

                      {idx >= 5 && (
                        <button
                          type="button"
                          onClick={() => {
                            const updated = newShoeImages.filter((_, filterIdx) => filterIdx !== idx);
                            setNewShoeImages(updated);
                          }}
                          className="px-2 bg-red-600/10 border border-red-500/25 hover:bg-red-500/20 text-red-500 rounded text-[10px] py-2 self-center"
                        >
                          ✕
                        </button>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              <button 
                type="submit" 
                className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 rounded text-xs uppercase tracking-widest transition-colors mt-4"
              >
                {editingShoeId ? 'Apply Profile Updates' : 'Launch Sneaker drop'}
              </button>
            </form>

          </div>
        </div>
      )}

    </div>
  );
}
