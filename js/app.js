// ===== LMS STORAGE =====
const LMS = {
  // Sample book data
  books: [
    { id: 'BK001', title: 'To Kill a Mockingbird', author: 'Harper Lee', category: 'Classic', total: 4, available: 4, img: 'https://covers.openlibrary.org/b/id/8739161-M.jpg' },
    { id: 'BK002', title: 'The Great Gatsby', author: 'F. Scott Fitzgerald', category: 'Classic', total: 5, available: 3, img: 'https://covers.openlibrary.org/b/id/8432472-M.jpg' },
    { id: 'BK003', title: 'Clean Code', author: 'Robert C. Martin', category: 'Technology', total: 6, available: 6, img: 'https://covers.openlibrary.org/b/id/8459412-M.jpg' },
    { id: 'BK004', title: 'Java Programming', author: 'Herbert Schildt', category: 'Technology', total: 5, available: 5, img: 'https://covers.openlibrary.org/b/id/10521086-M.jpg' },
    { id: 'BK005', title: 'Matilda', author: 'Roald Dahl', category: 'Fiction', total: 4, available: 4, img: 'https://covers.openlibrary.org/b/id/12648961-M.jpg' },
    { id: 'BK006', title: '1984', author: 'George Orwell', category: 'Science Fiction', total: 8, available: 1, img: 'https://covers.openlibrary.org/b/id/10527843-M.jpg' },
    { id: 'BK007', title: 'Pride and Prejudice', author: 'Jane Austen', category: 'Romance', total: 7, available: 5, img: 'https://covers.openlibrary.org/b/id/8472187-M.jpg' },
    { id: 'BK008', title: 'The Hobbit', author: 'J.R.R. Tolkien', category: 'Fantasy', total: 3, available: 0, img: 'https://covers.openlibrary.org/b/id/6979861-M.jpg' },
  ],

  getUser() {
    return JSON.parse(localStorage.getItem('lms_user') || 'null');
  },
  setUser(user) {
    localStorage.setItem('lms_user', JSON.stringify(user));
  },
  getUsers() {
    return JSON.parse(localStorage.getItem('lms_users') || '[]');
  },
  addUser(user) {
    const users = this.getUsers();
    users.push(user);
    localStorage.setItem('lms_users', JSON.stringify(users));
  },
  getCart() {
    return JSON.parse(localStorage.getItem('lms_cart') || '[]');
  },
  setCart(cart) {
    localStorage.setItem('lms_cart', JSON.stringify(cart));
  },
  getBorrowed() {
    const user = this.getUser();
    if (!user) return [];
    const all = JSON.parse(localStorage.getItem('lms_borrowed') || '{}');
    return all[user.username] || [];
  },
  addBorrowed(bookId) {
    const user = this.getUser();
    if (!user) return;
    const all = JSON.parse(localStorage.getItem('lms_borrowed') || '{}');
    if (!all[user.username]) all[user.username] = [];
    if (!all[user.username].includes(bookId)) {
      all[user.username].push(bookId);
    }
    localStorage.setItem('lms_borrowed', JSON.stringify(all));
  },
  removeBorrowed(bookId) {
    const user = this.getUser();
    if (!user) return;
    const all = JSON.parse(localStorage.getItem('lms_borrowed') || '{}');
    if (!all[user.username]) return;
    all[user.username] = all[user.username].filter(id => id !== bookId);
    localStorage.setItem('lms_borrowed', JSON.stringify(all));
  }
};

// ===== TOAST =====
function showToast(msg, type = '') {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.className = 'toast show ' + type;
  setTimeout(() => { toast.className = 'toast'; }, 2800);
}

// ===== AUTH GUARD =====
function requireAuth() {
  if (!LMS.getUser()) {
    window.location.href = 'login.html';
  }
}
function redirectIfLoggedIn() {
  if (LMS.getUser()) {
    window.location.href = 'dashboard.html';
  }
}

// ===== LOGOUT =====
function logout() {
  localStorage.removeItem('lms_user');
  LMS.setCart([]);
  window.location.href = 'login.html';
}

// ===== SIDEBAR ACTIVE STATE =====
function setActiveNav() {
  const path = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(el => {
    el.classList.remove('active');
    if (el.getAttribute('href') === path) {
      el.classList.add('active');
    }
  });
}

// ===== BARCODE GENERATOR (simple SVG) =====
function generateBarcodeSVG(text) {
  const bars = [];
  let x = 10;
  for (let i = 0; i < text.length * 3 + 20; i++) {
    const width = Math.random() > 0.6 ? 4 : 2;
    const isBlack = Math.random() > 0.4;
    if (isBlack) {
      bars.push(`<rect x="${x}" y="10" width="${width}" height="90" fill="black"/>`);
    }
    x += width + 1;
  }
  return `<svg width="300" height="110" xmlns="http://www.w3.org/2000/svg">${bars.join('')}</svg>`;
}
