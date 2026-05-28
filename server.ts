import express from 'express';
import { createServer as createViteServer } from 'vite';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3000;

app.use(express.json({ limit: '10mb' }));

// Ensure data folder exists
const DATA_DIR = path.join(__dirname, 'data');
if (!fs.existsSync(DATA_DIR)) {
  fs.mkdirSync(DATA_DIR);
}
const DB_FILE = path.join(DATA_DIR, 'stridehub_db.json');

// Helper to encrypt password (simple mock hash, safe for preview sandbox)
const hashPassword = (pwd: string) => `sha256_${pwd}`;

// Default Bootstrapped DB State simulating PostgreSQL Tables
const DEFAULT_DB = {
  users: [
    {
      id: 1,
      full_name: 'StrideHub Admin',
      email: 'admin@stridehub.com',
      password: hashPassword('password'),
      phone: '1-800-STRIDE',
      role: 'admin',
      status: 'active',
      created_at: new Date('2026-01-01').toISOString()
    },
    {
      id: 2,
      full_name: 'Jacob Sneakerhead',
      email: 'customer@stridehub.com',
      password: hashPassword('password'),
      phone: '+44 7700 900077',
      role: 'customer',
      status: 'active',
      created_at: new Date('2026-02-15').toISOString()
    }
  ],
  brands: [
    { id: 1, name: 'Nike', logo: 'nike_logo' },
    { id: 2, name: 'Adidas', logo: 'adidas_logo' },
    { id: 3, name: 'Puma', logo: 'puma_logo' },
    { id: 4, name: 'New Balance', logo: 'nb_logo' },
    { id: 5, name: 'Jordan', logo: 'jordan_logo' }
  ],
  categories: [
    { id: 1, name: 'Running' },
    { id: 2, name: 'Basketball' },
    { id: 3, name: 'Sneakers' },
    { id: 4, name: 'Casual' },
    { id: 5, name: 'Boots' }
  ],
  shoes: [
    {
      id: 1,
      name: 'Velocity Aether Max',
      brand_id: 1,
      category_id: 1,
      description: 'A clean, high-performance running shoe built for everyday movement, maximum comfort, and effortless style.',
      price: 189.00,
      discount_price: 159.00,
      gender: 'unisex',
      color: 'Orange Pulse',
      material: 'Carbon Mesh',
      stock: 35,
      rating_average: 4.85,
      featured: true,
      is_active: true,
      created_at: new Date('2026-05-10').toISOString()
    },
    {
      id: 2,
      name: 'Sonic Rush G-2',
      brand_id: 2,
      category_id: 1,
      description: 'Ultralight everyday runners designed with breathable fabrics and an elegant silhouette for all-day comfort.',
      price: 145.00,
      discount_price: null,
      gender: 'men',
      color: 'Cyber Gray/Volt',
      material: 'Recycled Knit',
      stock: 42,
      rating_average: 4.60,
      featured: true,
      is_active: true,
      created_at: new Date('2026-05-15').toISOString()
    },
    {
      id: 3,
      name: 'Shadow Pulse 1.0',
      brand_id: 5,
      category_id: 2,
      description: 'Premium ankle-support basketball sneakers featuring enhanced traction, supportive wrap, and premium materials.',
      price: 210.00,
      discount_price: 185.00,
      gender: 'men',
      color: 'Stealth Black',
      material: 'Synthetic Leather',
      stock: 18,
      rating_average: 4.90,
      featured: true,
      is_active: true,
      created_at: new Date('2026-05-18').toISOString()
    },
    {
      id: 4,
      name: 'Lunar Drift High-Top',
      brand_id: 5,
      category_id: 2,
      description: 'A timeless high-top silhouette crafted from premium suede and leather for an elegant, elevated everyday look.',
      price: 165.00,
      discount_price: null,
      gender: 'women',
      color: 'Star Dust Pink',
      material: 'Nylon & Suede',
      stock: 24,
      rating_average: 4.70,
      featured: true,
      is_active: true,
      created_at: new Date('2026-05-20').toISOString()
    },
    {
      id: 5,
      name: 'Carbon Elite X',
      brand_id: 4,
      category_id: 1,
      description: 'Exquisite marathon shoes pairing a featherlight frame with a comfortable carbon plate for effortless movement.',
      price: 299.00,
      discount_price: 260.00,
      gender: 'unisex',
      color: 'Luminous Glow',
      material: 'Carbon Matrix Mono-Mesh',
      stock: 12,
      rating_average: 4.95,
      featured: true,
      is_active: true,
      created_at: new Date('2026-05-22').toISOString()
    },
    {
      id: 6,
      name: 'Urban Street Glide',
      brand_id: 3,
      category_id: 3,
      description: 'Ultra-minimal skate-inspired sneakers with a low-padded collar, lightweight structure, and a sleek modern finish.',
      price: 95.00,
      discount_price: null,
      gender: 'unisex',
      color: 'Chalk White/Tan',
      material: 'Canvas',
      stock: 50,
      rating_average: 4.40,
      featured: false,
      is_active: true,
      created_at: new Date('2026-05-01').toISOString()
    },
    {
      id: 7,
      name: 'Terra Shield All-Weather',
      brand_id: 4,
      category_id: 5,
      description: 'All-weather boots crafted with weather-resistant materials and robust lug soles, made for confident winter movements.',
      price: 185.00,
      discount_price: 165.00,
      gender: 'men',
      color: 'Forest Moss',
      material: 'Ripstop & Leather',
      stock: 16,
      rating_average: 4.65,
      featured: false,
      is_active: true,
      created_at: new Date('2026-04-20').toISOString()
    },
    {
      id: 8,
      name: 'Sleek Runner Neo',
      brand_id: 2,
      category_id: 3,
      description: 'Slip-on runners designed for light movement, boasting form-fitting stretch materials and a minimalist aesthetic.',
      price: 110.00,
      discount_price: 89.00,
      gender: 'women',
      color: 'Platinum Lilac',
      material: 'Tech Mesh',
      stock: 28,
      rating_average: 4.50,
      featured: false,
      is_active: true,
      created_at: new Date('2026-05-02').toISOString()
    }
  ],
  shoe_sizes: [
    // Sizes for Velocity Aether Max (ID: 1)
    { id: 1, shoe_id: 1, size: '39', stock_quantity: 5 },
    { id: 2, shoe_id: 1, size: '40', stock_quantity: 8 },
    { id: 3, shoe_id: 1, size: '41', stock_quantity: 12 },
    { id: 4, shoe_id: 1, size: '42', stock_quantity: 10 },
    { id: 5, shoe_id: 1, size: '43', stock_quantity: 0 },
    
    // Sizes for Sonic Rush G-2 (ID: 2)
    { id: 6, shoe_id: 2, size: '40', stock_quantity: 15 },
    { id: 7, shoe_id: 2, size: '41', stock_quantity: 12 },
    { id: 8, shoe_id: 2, size: '42', stock_quantity: 10 },
    { id: 9, shoe_id: 2, size: '43', stock_quantity: 5 },

    // Sizes for Shadow Pulse 1.0 (ID: 3)
    { id: 10, shoe_id: 3, size: '41', stock_quantity: 4 },
    { id: 11, shoe_id: 3, size: '42', stock_quantity: 6 },
    { id: 12, shoe_id: 3, size: '43', stock_quantity: 8 },

    // Sizes for Lunar Drift High-Top (ID: 4)
    { id: 13, shoe_id: 4, size: '37', stock_quantity: 8 },
    { id: 14, shoe_id: 4, size: '38', stock_quantity: 10 },
    { id: 15, shoe_id: 4, size: '39', stock_quantity: 6 },

    // Sizes for Carbon Elite X (ID: 5)
    { id: 16, shoe_id: 5, size: '41', stock_quantity: 3 },
    { id: 17, shoe_id: 5, size: '42', stock_quantity: 5 },
    { id: 18, shoe_id: 5, size: '43', stock_quantity: 4 },

    // Sizes for Urban Street Glide (ID: 6)
    { id: 19, shoe_id: 6, size: '40', stock_quantity: 15 },
    { id: 20, shoe_id: 6, size: '41', stock_quantity: 20 },
    { id: 21, shoe_id: 6, size: '42', stock_quantity: 15 },

    // Sizes for Terra Shield All-Weather (ID: 7)
    { id: 22, shoe_id: 7, size: '42', stock_quantity: 8 },
    { id: 23, shoe_id: 7, size: '43', stock_quantity: 8 },

    // Sizes for Sleek Runner Neo (ID: 8)
    { id: 24, shoe_id: 8, size: '38', stock_quantity: 10 },
    { id: 25, shoe_id: 8, size: '39', stock_quantity: 12 },
    { id: 26, shoe_id: 8, size: '40', stock_quantity: 6 }
  ],
  shoe_images: [
    { id: 1, shoe_id: 1, image_url: 'orange_aether_primary', is_primary: true },
    { id: 2, shoe_id: 1, image_url: 'orange_aether_alt1', is_primary: false },
    { id: 3, shoe_id: 1, image_url: 'orange_aether_alt2', is_primary: false },
    { id: 4, shoe_id: 2, image_url: 'cyber_volt_primary', is_primary: true },
    { id: 5, shoe_id: 3, image_url: 'stealth_black_primary', is_primary: true },
    { id: 6, shoe_id: 4, image_url: 'lunar_pink_primary', is_primary: true },
    { id: 7, shoe_id: 5, image_url: 'glow_carbon_primary', is_primary: true },
    { id: 8, shoe_id: 6, image_url: 'glide_white_primary', is_primary: true },
    { id: 9, shoe_id: 7, image_url: 'terra_moss_primary', is_primary: true },
    { id: 10, shoe_id: 8, image_url: 'neo_lilac_primary', is_primary: true }
  ],
  addresses: [
    { id: 1, user_id: 2, country: 'United Kingdom', city: 'London', address_line: '221B Baker St', postal_code: 'NW1 6XE', is_default: true }
  ],
  cart: [] as any[],
  wishlist: [] as any[],
  coupons: [
    { id: 1, code: 'KICKS20', discount_percent: 20, expiration_date: '2027-12-31', active: true },
    { id: 2, code: 'LAUNCH50', discount_percent: 50, expiration_date: '2027-06-30', active: true }
  ],
  orders: [
    {
      id: 1001,
      user_id: 2,
      total_price: 159.00,
      shipping_price: 10.00,
      payment_method: 'Card Payment',
      status: 'delivered',
      shipping_address_id: 1,
      coupon_id: null,
      created_at: new Date('2026-05-18T10:30:00Z').toISOString()
    }
  ],
  order_items: [
    { id: 1, order_id: 1001, shoe_id: 1, size_id: 2, quantity: 1, price: 159.00 }
  ],
  payments: [
    { id: 1, order_id: 1001, amount: 169.00, payment_method: 'Card Payment', payment_status: 'completed', transaction_reference: 'TXN-9988231', created_at: new Date('2026-05-18T10:31:00Z').toISOString() }
  ],
  reviews: [
    { id: 1, user_id: 2, shoe_id: 1, rating: 5, comment: 'Phenomenal grip and responsive step cushion! Feels futuristic and very premium.', created_at: new Date('2026-05-20').toISOString() }
  ],
  admin_logs: [
    { id: 1, admin_id: 1, action: 'Bootstrapped StrideHub premium footwear catalog.', created_at: new Date('2026-05-27T12:00:00Z').toISOString() }
  ]
};

// Database state tracker
let db = { ...DEFAULT_DB };

// Load DB from file if exists
const loadDB = () => {
  try {
    if (fs.existsSync(DB_FILE)) {
      const content = fs.readFileSync(DB_FILE, 'utf-8');
      db = JSON.parse(content);
    } else {
      saveDB();
    }
  } catch (err) {
    console.error('Failed to load JSON DB, utilizing fallback memory store.', err);
  }
};

// Save DB state to file
const saveDB = () => {
  try {
    fs.writeFileSync(DB_FILE, JSON.stringify(db, null, 2), 'utf-8');
  } catch (err) {
    console.error('Failed to write JSON DB file state.', err);
  }
};

loadDB();

// ===================================
// OOP CONTROLLERS / DATABASE HANDLERS
// ===================================

class AuthController {
  static register(fullName: string, email: string, psw: string, phone?: string) {
    const existing = db.users.find(u => u.email.toLowerCase() === email.toLowerCase());
    if (existing) {
      throw new Error('An account with this email address already exists.');
    }
    const newId = db.users.length ? Math.max(...db.users.map(u => u.id)) + 1 : 1;
    const newUser = {
      id: newId,
      full_name: fullName,
      email: email,
      password: hashPassword(psw),
      phone: phone || '',
      role: 'customer',
      status: 'active' as const,
      created_at: new Date().toISOString()
    };
    db.users.push(newUser);
    saveDB();
    const { password, ...userWithoutPwd } = newUser;
    return userWithoutPwd;
  }

  static login(email: string, psw: string) {
    const user = db.users.find(u => u.email.toLowerCase() === email.toLowerCase());
    if (!user) {
      throw new Error('Invalid email or password.');
    }
    if (user.status === 'blocked') {
      throw new Error('Your account has been suspended by an administrator.');
    }
    if (user.password !== hashPassword(psw)) {
      throw new Error('Invalid email or password.');
    }
    const { password, ...userWithoutPwd } = user;
    return userWithoutPwd;
  }
}

// ============================
// API CONTROLLERS ENDPOINTS
// ============================

// Authenticate helper helper
const getSessionUser = (req: express.Request) => {
  const authHeader = req.headers.authorization;
  if (!authHeader) return null;
  const token = authHeader.replace('Bearer ', '');
  try {
    const parsed = JSON.parse(Buffer.from(token, 'base64').toString());
    const user = db.users.find(u => u.id === parsed.id && u.email === parsed.email);
    if (user && user.status === 'blocked') return null;
    return user || null;
  } catch {
    return null;
  }
};

// Logger middleware helper
const logAdminAction = (adminId: number, action: string) => {
  const newId = db.admin_logs.length ? Math.max(...db.admin_logs.map(l => l.id)) + 1 : 1;
  db.admin_logs.unshift({
    id: newId,
    admin_id: adminId,
    action,
    created_at: new Date().toISOString()
  });
  saveDB();
};

// 1. AUTH API
app.post('/api/auth/register', (req, res) => {
  const { full_name, email, password, phone } = req.body;
  if (!full_name || !email || !password) {
    return res.status(400).json({ error: 'Name, email, and password are required.' });
  }
  try {
    const user = AuthController.register(full_name, email, password, phone);
    res.status(201).json({ success: true, user });
  } catch (err: any) {
    res.status(400).json({ error: err.message });
  }
});

app.post('/api/auth/login', (req, res) => {
  const { email, password } = req.body;
  if (!email || !password) {
    return res.status(400).json({ error: 'Email and password are required.' });
  }
  try {
    const user = AuthController.login(email, password);
    // Return dummy signature token
    const token = Buffer.from(JSON.stringify({ id: user.id, email: user.email })).toString('base64');
    res.json({ success: true, token, user });
  } catch (err: any) {
    res.status(401).json({ error: err.message });
  }
});

// 2. SHOE PRODUCTS CATALOG API
app.get('/api/shoes', (req, res) => {
  const { brand_id, category_id, gender, color, min_price, max_price, sort, search } = req.query;
  
  let products = db.shoes.filter(s => s.is_active);

  // Search filter
  if (search) {
    const term = (search as string).toLowerCase();
    products = products.filter(s => 
      s.name.toLowerCase().includes(term) || 
      s.description.toLowerCase().includes(term) ||
      s.color.toLowerCase().includes(term)
    );
  }

  // Brand filter
  if (brand_id) {
    products = products.filter(s => s.brand_id === Number(brand_id));
  }

  // Category filter
  if (category_id) {
    products = products.filter(s => s.category_id === Number(category_id));
  }

  // Gender filter
  if (gender && gender !== 'all') {
    products = products.filter(s => s.gender === gender);
  }

  // Color filter
  if (color) {
    products = products.filter(s => s.color.toLowerCase().includes((color as string).toLowerCase()));
  }

  // Price range filter
  if (min_price) {
    products = products.filter(s => {
      const activePrice = s.discount_price !== null ? s.discount_price : s.price;
      return activePrice >= Number(min_price);
    });
  }
  if (max_price) {
    products = products.filter(s => {
      const activePrice = s.discount_price !== null ? s.discount_price : s.price;
      return activePrice <= Number(max_price);
    });
  }

  // Sort logic
  if (sort === 'price_low_high') {
    products.sort((a, b) => {
      const pa = a.discount_price !== null ? a.discount_price : a.price;
      const pb = b.discount_price !== null ? b.discount_price : b.price;
      return pa - pb;
    });
  } else if (sort === 'price_high_low') {
    products.sort((a, b) => {
      const pa = a.discount_price !== null ? a.discount_price : a.price;
      const pb = b.discount_price !== null ? b.discount_price : b.price;
      return pb - pa;
    });
  } else if (sort === 'rating') {
    products.sort((a, b) => b.rating_average - a.rating_average);
  } else if (sort === 'best_selling') {
    // Best selling relies on stock quantity of items bought in order_items
    const soldCounts: Record<number, number> = {};
    db.order_items.forEach(item => {
      if (item.shoe_id) {
        soldCounts[item.shoe_id] = (soldCounts[item.shoe_id] || 0) + item.quantity;
      }
    });
    products.sort((a, b) => (soldCounts[b.id] || 0) - (soldCounts[a.id] || 0));
  } else {
    // default/newest
    products.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
  }

  // Populate brand names and imagery
  const responseData = products.map(p => {
    const brand = db.brands.find(b => b.id === p.brand_id);
    const category = db.categories.find(c => c.id === p.category_id);
    const primImg = db.shoe_images.find(img => img.shoe_id === p.id && img.is_primary) || db.shoe_images.find(img => img.shoe_id === p.id);
    return {
      ...p,
      brand_name: brand ? brand.name : 'Unknown',
      category_name: category ? category.name : 'Unknown',
      primary_image: primImg ? primImg.image_url : null
    };
  });

  res.json(responseData);
});

// Single product details
app.get('/api/shoes/:id', (req, res) => {
  const shoeId = Number(req.params.id);
  const shoe = db.shoes.find(s => s.id === shoeId);
  if (!shoe) {
    return res.status(404).json({ error: 'Product not found.' });
  }

  const brand = db.brands.find(b => b.id === shoe.brand_id);
  const category = db.categories.find(c => c.id === shoe.category_id);
  const sizes = db.shoe_sizes.filter(s => s.shoe_id === shoeId);
  const images = db.shoe_images.filter(img => img.shoe_id === shoeId);
  const reviews = db.reviews.filter(rev => rev.shoe_id === shoeId).map(rev => {
    const reviewer = db.users.find(u => u.id === rev.user_id);
    return {
      ...rev,
      reviewer_name: reviewer ? reviewer.full_name : 'Verified Customer'
    };
  });

  res.json({
    ...shoe,
    brand_name: brand ? brand.name : 'Unknown',
    category_name: category ? category.name : 'Unknown',
    sizes,
    images,
    reviews
  });
});

// Create reviews
app.post('/api/reviews', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const { shoe_id, rating, comment } = req.body;
  if (!shoe_id || !rating) {
    return res.status(400).json({ error: 'Shoe ID and rating are required.' });
  }

  // Delete preexisting review if rewrite is requested
  const preIdx = db.reviews.findIndex(r => r.user_id === user.id && r.shoe_id === Number(shoe_id));
  if (preIdx !== -1) {
    db.reviews.splice(preIdx, 1);
  }

  const reviewId = db.reviews.length ? Math.max(...db.reviews.map(r => r.id)) + 1 : 1;
  const newReview = {
    id: reviewId,
    user_id: user.id,
    shoe_id: Number(shoe_id),
    rating: Number(rating),
    comment: comment || '',
    created_at: new Date().toISOString()
  };

  db.reviews.unshift(newReview);

  // Recalculate average rating of shoe
  const allShoeReviews = db.reviews.filter(r => r.shoe_id === Number(shoe_id));
  const avg = allShoeReviews.reduce((sum, r) => sum + r.rating, 0) / allShoeReviews.length;
  const shoeObj = db.shoes.find(s => s.id === Number(shoe_id));
  if (shoeObj) {
    shoeObj.rating_average = Number(avg.toFixed(2));
  }

  saveDB();
  res.status(201).json({ success: true, review: { ...newReview, reviewer_name: user.full_name } });
});

// Admin Review moderation: Delete Review
app.delete('/api/reviews/:id', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') {
    return res.status(403).json({ error: 'Administrative action required.' });
  }

  const reviewId = Number(req.params.id);
  const idx = db.reviews.findIndex(r => r.id === reviewId);
  if (idx === -1) {
    return res.status(404).json({ error: 'Review not found.' });
  }

  const review = db.reviews[idx];
  db.reviews.splice(idx, 1);

  // Reweight average rating
  const allShoeReviews = db.reviews.filter(r => r.shoe_id === review.shoe_id);
  const avg = allShoeReviews.length ? (allShoeReviews.reduce((sum, r) => sum + r.rating, 0) / allShoeReviews.length) : 5.00;
  const shoeObj = db.shoes.find(s => s.id === review.shoe_id);
  if (shoeObj) {
    shoeObj.rating_average = Number(avg.toFixed(2));
  }

  logAdminAction(user.id, `Moderated and deleted inapppropriate Review #${reviewId} by User #${review.user_id}.`);
  saveDB();
  res.json({ success: true });
});

// Metadata endpoints
app.get('/api/metadata', (req, res) => {
  res.json({
    brands: db.brands,
    categories: db.categories,
    colors: Array.from(new Set(db.shoes.map(s => s.color.split('/')[0].trim())))
  });
});

// 3. CART API
app.get('/api/cart', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const userCart = db.cart.filter(item => item.user_id === user.id).map(item => {
    const shoe = db.shoes.find(s => s.id === item.shoe_id);
    const size = db.shoe_sizes.find(sz => sz.id === item.size_id);
    const primImg = db.shoe_images.find(img => img.shoe_id === item.shoe_id && img.is_primary) || db.shoe_images.find(img => img.shoe_id === item.shoe_id);
    return {
      ...item,
      shoe: shoe ? {
        id: shoe.id,
        name: shoe.name,
        price: shoe.price,
        discount_price: shoe.discount_price,
        primary_image: primImg ? primImg.image_url : null
      } : null,
      size: size ? size.size : 'N/A'
    };
  });

  res.json(userCart);
});

app.post('/api/cart', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const { shoe_id, size_id, quantity } = req.body;
  if (!shoe_id || !size_id || !quantity) {
    return res.status(400).json({ error: 'Shoe ID, size ID and quantity are required.' });
  }

  // Double check stock availability
  const sizeObj = db.shoe_sizes.find(sz => sz.id === Number(size_id));
  if (!sizeObj || sizeObj.stock_quantity < Number(quantity)) {
    return res.status(400).json({ error: 'Requested size is currently out of stock or insufficient stock.' });
  }

  const existingIdx = db.cart.findIndex(item => item.user_id === user.id && item.shoe_id === Number(shoe_id) && item.size_id === Number(size_id));
  if (existingIdx !== -1) {
    db.cart[existingIdx].quantity = Number(quantity);
  } else {
    const newId = db.cart.length ? Math.max(...db.cart.map(c => c.id)) + 1 : 1;
    db.cart.push({
      id: newId,
      user_id: user.id,
      shoe_id: Number(shoe_id),
      size_id: Number(size_id),
      quantity: Number(quantity)
    });
  }

  saveDB();
  res.json({ success: true });
});

app.delete('/api/cart/:id', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const id = Number(req.params.id);
  const idx = db.cart.findIndex(item => item.id === id && item.user_id === user.id);
  if (idx !== -1) {
    db.cart.splice(idx, 1);
    saveDB();
    res.json({ success: true });
  } else {
    res.status(404).json({ error: 'Cart item not found.' });
  }
});

// 4. WISHLIST API
app.get('/api/wishlist', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const wishlistItems = db.wishlist.filter(w => w.user_id === user.id).map(w => {
    const shoe = db.shoes.find(s => s.id === w.shoe_id);
    const brand = shoe ? db.brands.find(b => b.id === shoe.brand_id) : null;
    const primImg = shoe ? (db.shoe_images.find(img => img.shoe_id === shoe.id && img.is_primary) || db.shoe_images.find(img => img.shoe_id === shoe.id)) : null;
    return shoe ? {
      wishlist_id: w.id,
      ...shoe,
      brand_name: brand ? brand.name : 'Unknown',
      primary_image: primImg ? primImg.image_url : null
    } : null;
  }).filter(Boolean);

  res.json(wishlistItems);
});

app.post('/api/wishlist', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const { shoe_id } = req.body;
  if (!shoe_id) return res.status(400).json({ error: 'Shoe ID is required.' });

  const idx = db.wishlist.findIndex(w => w.user_id === user.id && w.shoe_id === Number(shoe_id));
  if (idx !== -1) {
    // Remove if already wishlisted (toggle)
    db.wishlist.splice(idx, 1);
    saveDB();
    return res.json({ success: true, action: 'removed' });
  } else {
    const newId = db.wishlist.length ? Math.max(...db.wishlist.map(w => w.id)) + 1 : 1;
    db.wishlist.push({
      id: newId,
      user_id: user.id,
      shoe_id: Number(shoe_id)
    });
    saveDB();
    return res.json({ success: true, action: 'added' });
  }
});

// 5. ADDRESSES API
app.get('/api/addresses', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const addrs = db.addresses.filter(addr => addr.user_id === user.id);
  res.json(addrs);
});

app.post('/api/addresses', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const { country, city, address_line, postal_code, is_default } = req.body;
  if (!country || !city || !address_line || !postal_code) {
    return res.status(400).json({ error: 'All fields are required.' });
  }

  if (is_default) {
    db.addresses.forEach(addr => {
      if (addr.user_id === user.id) addr.is_default = false;
    });
  }

  const newId = db.addresses.length ? Math.max(...db.addresses.map(a => a.id)) + 1 : 1;
  const newAddr = {
    id: newId,
    user_id: user.id,
    country,
    city,
    address_line,
    postal_code,
    is_default: is_default || db.addresses.filter(a => a.user_id === user.id).length === 0,
    created_at: new Date().toISOString()
  };

  db.addresses.push(newAddr);
  saveDB();
  res.json({ success: true, address: newAddr });
});

// 6. VALIDATE COUPONS
app.post('/api/coupons/validate', (req, res) => {
  const { code } = req.body;
  if (!code) return res.status(400).json({ error: 'Coupon code required.' });

  const coupon = db.coupons.find(c => c.code.toUpperCase() === code.toUpperCase() && c.active);
  if (!coupon) {
    return res.status(400).json({ error: 'Invalid or deactivated coupon.' });
  }

  if (new Date(coupon.expiration_date).getTime() < Date.now()) {
    return res.status(400).json({ error: 'This coupon code has expired.' });
  }

  res.json({ success: true, coupon });
});

// 7. PLACE ORDER (CHECKOUT)
app.post('/api/orders', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const { shipping_address, phone, payment_method, coupon_id, cart_items } = req.body;
  if (!shipping_address || !cart_items || cart_items.length === 0) {
    return res.status(400).json({ error: 'Missing shipping address details or shopping cart is empty.' });
  }

  // Find or create address record
  let addressId = 1;
  const existingAddr = db.addresses.find(addr => 
    addr.user_id === user.id && 
    addr.address_line === shipping_address.address_line &&
    addr.city === shipping_address.city
  );

  if (existingAddr) {
    addressId = existingAddr.id;
  } else {
    const newId = db.addresses.length ? Math.max(...db.addresses.map(a => a.id)) + 1 : 1;
    const newAddr = {
      id: newId,
      user_id: user.id,
      country: shipping_address.country || 'United Kingdom',
      city: shipping_address.city,
      address_line: shipping_address.address_line,
      postal_code: shipping_address.postal_code || 'N/A',
      is_default: false,
      created_at: new Date().toISOString()
    };
    db.addresses.push(newAddr);
    addressId = newId;
  }

  // Update phone if supplied
  if (phone) {
    const dbUserIdx = db.users.findIndex(u => u.id === user.id);
    if (dbUserIdx !== -1) {
      db.users[dbUserIdx].phone = phone;
    }
  }

  // Calculate Subtotal & apply discounts, verify stocks
  let subtotals = 0;
  const validatedItems: any[] = [];

  for (const clientItem of cart_items) {
    const shoe = db.shoes.find(s => s.id === clientItem.shoe_id);
    if (!shoe) {
      return res.status(400).json({ error: `Product with ID ${clientItem.shoe_id} not found.` });
    }
    const size = db.shoe_sizes.find(sz => sz.id === clientItem.size_id);
    if (!size) {
      return res.status(400).json({ error: `Invalid size configuration.` });
    }
    if (size.stock_quantity < clientItem.quantity) {
      return res.status(400).json({ error: `Insufficient stock for dimensions of ${shoe.name}. Only ${size.stock_quantity} remaining.` });
    }

    // Deduct stock quantity safely
    size.stock_quantity -= clientItem.quantity;
    
    const priceUnit = shoe.discount_price !== null ? shoe.discount_price : shoe.price;
    subtotals += priceUnit * clientItem.quantity;

    validatedItems.push({
      shoe_id: shoe.id,
      size_id: size.id,
      quantity: clientItem.quantity,
      price: priceUnit
    });
  }

  // Apply Coupon Discount
  let discountAmount = 0;
  if (coupon_id) {
    const coupon = db.coupons.find(c => c.id === Number(coupon_id));
    if (coupon) {
      discountAmount = subtotals * (coupon.discount_percent / 100);
    }
  }

  const shippingCost = subtotals > 150 ? 0.00 : 15.00;
  const finalTotal = Number((subtotals - discountAmount + shippingCost).toFixed(2));

  // Create Order
  const orderId = db.orders.length ? Math.max(...db.orders.map(o => o.id)) + 1 : 1002;
  const newOrder = {
    id: orderId,
    user_id: user.id,
    total_price: finalTotal,
    shipping_price: shippingCost,
    payment_method: payment_method || 'Card Payment',
    status: 'confirmed' as const, // Automatically confirmed on checkout
    shipping_address_id: addressId,
    coupon_id: coupon_id ? Number(coupon_id) : null,
    created_at: new Date().toISOString()
  };

  db.orders.unshift(newOrder);

  // Add Order Items
  for (const itm of validatedItems) {
    const orderItemId = db.order_items.length ? Math.max(...db.order_items.map(oi => oi.id)) + 1 : 1;
    db.order_items.push({
      id: orderItemId,
      order_id: orderId,
      shoe_id: itm.shoe_id,
      size_id: itm.size_id,
      quantity: itm.quantity,
      price: itm.price
    });
  }

  // Create payment record
  const payId = db.payments.length ? Math.max(...db.payments.map(p => p.id)) + 1 : 1;
  db.payments.push({
    id: payId,
    order_id: orderId,
    amount: finalTotal,
    payment_method: payment_method || 'Card Payment',
    payment_status: 'completed',
    transaction_reference: 'TXN-' + Math.random().toString(36).substring(2, 9).toUpperCase(),
    created_at: new Date().toISOString()
  });

  // Clear customer's cart
  db.cart = db.cart.filter(item => item.user_id !== user.id);

  saveDB();
  res.status(201).json({ success: true, order_id: orderId });
});

// 8. CUSTOMER ORDERS HISTORY
app.get('/api/orders/history', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  let results = db.orders;
  if (user.role !== 'admin') {
    results = results.filter(o => o.user_id === user.id);
  }

  const finalHistory = results.map(ord => {
    const items = db.order_items.filter(oi => oi.order_id === ord.id).map(oi => {
      const shoe = db.shoes.find(s => s.id === oi.shoe_id);
      const sizeObj = db.shoe_sizes.find(sz => sz.id === oi.size_id);
      return {
        ...oi,
        shoe_name: shoe ? shoe.name : 'Unknown Shoe',
        size_label: sizeObj ? sizeObj.size : 'N/A'
      };
    });
    const addr = db.addresses.find(a => a.id === ord.shipping_address_id);
    const buyer = db.users.find(u => u.id === ord.user_id);
    const payment = db.payments.find(p => p.order_id === ord.id);
    return {
      ...ord,
      items,
      address: addr,
      buyer_name: buyer ? buyer.full_name : 'Guest Customer',
      buyer_email: buyer ? buyer.email : '',
      payment
    };
  });

  res.json(finalHistory);
});

// profile updates
app.put('/api/customer/profile', (req, res) => {
  const user = getSessionUser(req);
  if (!user) return res.status(401).json({ error: 'Authentication required.' });

  const { full_name, phone, password } = req.body;
  const userIdx = db.users.findIndex(u => u.id === user.id);
  if (userIdx !== -1) {
    if (full_name) db.users[userIdx].full_name = full_name;
    if (phone !== undefined) db.users[userIdx].phone = phone;
    if (password) db.users[userIdx].password = hashPassword(password);
    
    saveDB();
    const { password: _, ...clean } = db.users[userIdx];
    return res.json({ success: true, user: clean });
  }
  res.status(404).json({ error: 'User not found.' });
});

// =====================
// ADMINISTRATIVE CONSOLES
// =====================

// KPIs Dashboard Counters
app.get('/api/admin/kpis', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') {
    return res.status(403).json({ error: 'Unauthorized administrative operation.' });
  }

  const activeCustomers = db.users.filter(u => u.role === 'customer');
  const totalRevenue = db.payments.filter(p => p.payment_status === 'completed').reduce((sum, p) => sum + Number(p.amount), 0);
  
  // Low stock alert products (either total shoe stock < 10 or specific size stock is 0 but shoe active)
  const lowStockShoes = db.shoes.filter(s => {
    const sizeStocks = db.shoe_sizes.filter(sz => sz.shoe_id === s.id);
    const totalSizesStock = sizeStocks.reduce((tot, sz) => tot + sz.stock_quantity, 0);
    return totalSizesStock < 10 || sizeStocks.some(sz => sz.stock_quantity === 0);
  }).map(s => s.name);

  // Best selling sneaker order ranking
  const shoeSoldCounts: Record<number, { name: string; sold: number; revenue: number }> = {};
  db.order_items.forEach(item => {
    if (item.shoe_id) {
      if (!shoeSoldCounts[item.shoe_id]) {
        const shoe = db.shoes.find(s => s.id === item.shoe_id);
        shoeSoldCounts[item.shoe_id] = {
          name: shoe ? shoe.name : `Shoe #${item.shoe_id}`,
          sold: 0,
          revenue: 0
        };
      }
      shoeSoldCounts[item.shoe_id].sold += item.quantity;
      shoeSoldCounts[item.shoe_id].revenue += item.quantity * item.price;
    }
  });

  const bestSellers = Object.values(shoeSoldCounts).sort((a,b) => b.sold - a.sold).slice(0, 5);

  res.json({
    total_users: activeCustomers.length,
    total_orders: db.orders.length,
    total_revenue: totalRevenue,
    low_stock_count: lowStockShoes.length,
    low_stock_list: lowStockShoes,
    best_sellers: bestSellers,
    recent_logs: db.admin_logs.slice(0, 15)
  });
});

// Shoe CRUD Operations
app.post('/api/admin/shoes', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') {
    return res.status(403).json({ error: 'Unauthorized action.' });
  }

  const { name, brand_id, category_id, description, price, discount_price, gender, color, material, sizes, image_urls } = req.body;
  if (!name || !brand_id || !category_id || !price || !color) {
    return res.status(400).json({ error: 'Required fields: Name, Brand, Category, Price, and Color.' });
  }

  // Enforce at least 5 URLs
  if (!Array.isArray(image_urls)) {
    return res.status(400).json({ error: 'At least 5 pictures of the shoe are required.' });
  }
  const validUrls = image_urls.filter(u => typeof u === 'string' && u.trim() !== '');
  if (validUrls.length < 5) {
    return res.status(400).json({ error: 'An administrator must insert at least 5 pictures of the product.' });
  }

  const shoeId = db.shoes.length ? Math.max(...db.shoes.map(s => s.id)) + 1 : 1;
  const newShoe = {
    id: shoeId,
    name,
    brand_id: Number(brand_id),
    category_id: Number(category_id),
    description: description || '',
    price: Number(price),
    discount_price: discount_price ? Number(discount_price) : null,
    gender: gender || 'unisex',
    color,
    material: material || 'Suede/Mesh',
    stock: 0,
    rating_average: 5.0,
    featured: false,
    is_active: true,
    created_at: new Date().toISOString()
  };

  db.shoes.unshift(newShoe);

  // Clear existing and persist new image urls
  db.shoe_images = db.shoe_images.filter(img => img.shoe_id !== shoeId);
  validUrls.forEach((url, idx) => {
    const imgId = db.shoe_images.length ? Math.max(...db.shoe_images.map(img => img.id)) + 1 : 1;
    db.shoe_images.push({
      id: imgId,
      shoe_id: shoeId,
      image_url: url,
      is_primary: idx === 0
    });
  });

  // Sizes creation
  if (Array.isArray(sizes) && sizes.length > 0) {
    let rollStock = 0;
    sizes.forEach(sz => {
      const szId = db.shoe_sizes.length ? Math.max(...db.shoe_sizes.map(s => s.id)) + 1 : 1;
      const qty = Number(sz.stock_quantity) || 0;
      rollStock += qty;
      db.shoe_sizes.push({
        id: szId,
        shoe_id: shoeId,
        size: sz.size,
        stock_quantity: qty
      });
    });
    newShoe.stock = rollStock;
  } else {
    // Generate default dimensions if omitted
    const defaultSizes = ['40', '41', '42', '43'];
    defaultSizes.forEach(sz => {
      const szId = db.shoe_sizes.length ? Math.max(...db.shoe_sizes.map(s => s.id)) + 1 : 1;
      db.shoe_sizes.push({
        id: szId,
        shoe_id: shoeId,
        size: sz,
        stock_quantity: 10
      });
    });
    newShoe.stock = 40;
  }

  logAdminAction(user.id, `Created new shoe index item "${name}" (ID #${shoeId}) with ${validUrls.length} images.`);
  saveDB();
  res.status(201).json({ success: true, shoe: newShoe });
});

// Update Shoe
app.put('/api/admin/shoes/:id', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') return res.status(403).json({ error: 'Unauthorized.' });

  const shoeId = Number(req.params.id);
  const idx = db.shoes.findIndex(s => s.id === shoeId);
  if (idx === -1) return res.status(404).json({ error: 'Shoe index has not been found.' });

  const { name, brand_id, category_id, description, price, discount_price, gender, color, material, stock, sizes, is_active, featured, image_urls } = req.body;
  const shoe = db.shoes[idx];

  // Validate images if provided
  if (image_urls !== undefined) {
    if (!Array.isArray(image_urls)) {
      return res.status(400).json({ error: 'image_urls must be an array.' });
    }
    const validUrls = image_urls.filter(u => typeof u === 'string' && u.trim() !== '');
    if (validUrls.length < 5) {
      return res.status(400).json({ error: 'An administrator must insert at least 5 pictures of the product.' });
    }

    // Refresh images list
    db.shoe_images = db.shoe_images.filter(img => img.shoe_id !== shoeId);
    validUrls.forEach((url, idx) => {
      const imgId = db.shoe_images.length ? Math.max(...db.shoe_images.map(img => img.id)) + 1 : 1;
      db.shoe_images.push({
        id: imgId,
        shoe_id: shoeId,
        image_url: url,
        is_primary: idx === 0
      });
    });
  }

  if (name) shoe.name = name;
  if (brand_id) shoe.brand_id = Number(brand_id);
  if (category_id) shoe.category_id = Number(category_id);
  if (description !== undefined) shoe.description = description;
  if (price) shoe.price = Number(price);
  shoe.discount_price = discount_price !== undefined ? (discount_price ? Number(discount_price) : null) : shoe.discount_price;
  if (gender) shoe.gender = gender;
  if (color) shoe.color = color;
  if (material) shoe.material = material;
  if (is_active !== undefined) shoe.is_active = is_active;
  if (featured !== undefined) shoe.featured = featured;

  // Sizes revision
  if (Array.isArray(sizes)) {
    // Remove existing
    db.shoe_sizes = db.shoe_sizes.filter(s => s.shoe_id !== shoeId);
    let totalS = 0;
    sizes.forEach(sz => {
      const szId = db.shoe_sizes.length ? Math.max(...db.shoe_sizes.map(s => s.id)) + 1 : 1;
      const q = Number(sz.stock_quantity) || 0;
      totalS += q;
      db.shoe_sizes.push({
        id: szId,
        shoe_id: shoeId,
        size: sz.size,
        stock_quantity: q
      });
    });
    shoe.stock = totalS;
  } else if (stock !== undefined) {
    shoe.stock = Number(stock);
  }

  logAdminAction(user.id, `Updated shoe metadata profile for "${shoe.name}" (ID #${shoeId}).`);
  saveDB();
  res.json({ success: true, shoe });
});

// Delete Shoe (soft delete mapping)
app.delete('/api/admin/shoes/:id', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') return res.status(403).json({ error: 'Unauthorized.' });

  const shoeId = Number(req.params.id);
  const idx = db.shoes.findIndex(s => s.id === shoeId);
  if (idx !== -1) {
    // Soft deletion to protect relational history
    db.shoes[idx].is_active = false;
    logAdminAction(user.id, `Deactivated/Deleted product index of "${db.shoes[idx].name}" (ID #${shoeId}).`);
    saveDB();
    res.json({ success: true });
  } else {
    res.status(404).json({ error: 'Product not found.' });
  }
});

// Customer Management APIS
app.get('/api/admin/customers', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') return res.status(403).json({ error: 'Unauthorized.' });

  const queryTerm = (req.query.search as string || '').toLowerCase();
  let list = db.users.filter(u => u.role === 'customer');

  if (queryTerm) {
    list = list.filter(u => u.full_name.toLowerCase().includes(queryTerm) || u.email.toLowerCase().includes(queryTerm));
  }

  res.json(list.map(({ password, ...clean }) => clean));
});

// Block or Unblock Customer
app.put('/api/admin/customers/:id/status', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') return res.status(403).json({ error: 'Unauthorized.' });

  const customerId = Number(req.params.id);
  const idx = db.users.findIndex(u => u.id === customerId);
  if (idx === -1) return res.status(404).json({ error: 'Customer not found.' });

  const currentStatus = db.users[idx].status;
  const nextStatus = currentStatus === 'active' ? 'blocked' : 'active';
  db.users[idx].status = nextStatus;

  logAdminAction(user.id, `${nextStatus === 'blocked' ? 'Suspended' : 'Reactivated'} user account access for "${db.users[idx].full_name}" (ID #${customerId}).`);
  saveDB();
  res.json({ success: true, status: nextStatus });
});

// Delete Customer Account
app.delete('/api/admin/customers/:id', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') return res.status(403).json({ error: 'Unauthorized.' });

  const customerId = Number(req.params.id);
  const idx = db.users.findIndex(u => u.id === customerId);
  if (idx !== -1) {
    const deletedName = db.users[idx].full_name;
    db.users.splice(idx, 1);
    
    // Cascading removes
    db.cart = db.cart.filter(c => c.user_id !== customerId);
    db.wishlist = db.wishlist.filter(w => w.user_id !== customerId);

    logAdminAction(user.id, `Liquidated customer account of "${deletedName}" (ID #${customerId}).`);
    saveDB();
    res.json({ success: true });
  } else {
    res.status(404).json({ error: 'Customer not found.' });
  }
});

// Order Status Mutator
app.put('/api/admin/orders/:id/status', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') return res.status(403).json({ error: 'Unauthorized.' });

  const orderId = Number(req.params.id);
  const { status } = req.body;
  
  const idx = db.orders.findIndex(o => o.id === orderId);
  if (idx === -1) return res.status(404).json({ error: 'Order not found.' });

  const validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
  if (!validStatuses.includes(status)) {
    return res.status(400).json({ error: 'Invalid order tracking status code.' });
  }

  db.orders[idx].status = status;
  logAdminAction(user.id, `Patched Status code for Order #${orderId} to: "${status}".`);
  saveDB();
  res.json({ success: true, status });
});

// Serve direct uploaded images from public/uploads folder dynamically
app.use('/uploads', express.static(path.join(process.cwd(), 'public', 'uploads')));

// Direct Image upload endpoint (accepts base64 from admins and writes to public/uploads)
app.post('/api/upload', (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') {
    return res.status(403).json({ error: 'Only admins can catalog images.' });
  }

  const { name, base64 } = req.body;
  if (!name || !base64) {
    return res.status(400).json({ error: 'Missing name or base64 data for upload.' });
  }

  try {
    // Strip metadata prefix if exists (e.g., data:image/png;base64,)
    const base64Data = base64.replace(/^data:image\/\w+;base64,/, '');
    const buffer = Buffer.from(base64Data, 'base64');

    const uploadsDir = path.join(process.cwd(), 'public', 'uploads');
    if (!fs.existsSync(uploadsDir)) {
      fs.mkdirSync(uploadsDir, { recursive: true });
    }

    const fileExt = path.extname(name) || '.png';
    const cleanName = `${Date.now()}_${Math.random().toString(36).substring(2, 9)}${fileExt}`;
    const filePath = path.join(uploadsDir, cleanName);

    fs.writeFileSync(filePath, buffer);

    return res.json({ url: `/uploads/${cleanName}` });
  } catch (err: any) {
    return res.status(500).json({ error: err.message });
  }
});

// ========================
// DEVELOPMENT & PRODUCTION SERVERS SUPPORT
// ========================

async function boot() {
  if (process.env.NODE_ENV === 'production') {
    app.use(express.static(path.join(__dirname, 'dist')));
    app.get('*', (req, res) => {
      res.sendFile(path.join(__dirname, 'dist', 'index.html'));
    });
  } else {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'spa',
    });
    app.use(vite.middlewares);
  }

  app.listen(PORT, '0.0.0.0', () => {
    console.log(`StrideHub Node.js relational proxy applet running on port ${PORT}...`);
  });
}

boot().catch((err) => {
  console.error('Failure initializing server process.', err);
});
