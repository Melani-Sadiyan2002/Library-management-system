// Renders the sidebar into an element with id="sidebar"
function renderSidebar(activePage) {
  const user = LMS.getUser();
  const navItems = [
    { label: 'Dashboard', icon: '⊞', href: 'dashboard.html' },
    { label: 'Search Books', icon: '▦', href: 'search.html' },
    { label: 'Availability', icon: '📅', href: 'availability.html' },
    { label: 'My Bar Code', icon: '⬤', href: 'barcode.html' },
    { label: 'Update', icon: '🔄', href: 'update.html' },
  ];

  const nav = navItems.map(item => `
    <a href="${item.href}" class="nav-item ${activePage === item.href ? 'active' : ''}">
      <span class="icon">${item.icon}</span> ${item.label}
    </a>
  `).join('');

  document.getElementById('sidebar').innerHTML = `
    <div class="sidebar-logo">
      <span class="logo-icon">📖</span>
      <span>LMS</span>
    </div>
    <nav class="sidebar-nav">${nav}</nav>
    <div class="sidebar-logout">
      <button class="btn-logout" onclick="logout()">Logout</button>
    </div>
  `;
}

// Renders top bar
function renderTopbar(title) {
  const user = LMS.getUser();
  document.getElementById('topbar').innerHTML = `
    <h1>${title}</h1>
    <div class="topbar-user">
      <span class="user-icon">👤</span>
      <span>${user ? user.fullname : 'User'}</span>
    </div>
  `;
}
