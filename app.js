/**
 * Pirgacha Internet - Comprehensive Core Application Engine
 * Includes: ISP Billing, Expenses & Salary Tracker, Admin Staff Management, 
 * Fast Extra Charge Entry, and Complaint Support Ticket Management.
 */

// Initial Seed Data (Realistic Bangladeshi ISP Dataset)
const INITIAL_CUSTOMERS = [
  { id: 'PI-1001', name: 'মোঃ আব্দুর রহিম', phone: '01712345678', area: 'পীরগাছা বাজার', package: 'Standard (10 Mbps)', fee: 800, pppoe: 'rahim_pi', pppoePass: '123456', due: 1600, status: 'suspended', expireDate: '2026-09-05', joined: '2025-01-10' },
  { id: 'PI-1002', name: 'মোঃ কামরুল হাসান', phone: '01898765432', area: 'কলেজ রোড', package: 'Starter (5 Mbps)', fee: 500, pppoe: 'kamrul_net', pppoePass: 'kamrul@12', due: 500, status: 'active', expireDate: '2026-09-08', joined: '2025-02-15' },
  { id: 'PI-1003', name: 'নুরুল হুদা ট্রেডার্স', phone: '01911223344', area: 'পীরগাছা বাজার', package: 'Turbo (20 Mbps)', fee: 1200, pppoe: 'nurul_biz', pppoePass: 'biz990', due: 0, status: 'active', expireDate: '2026-09-30', joined: '2024-11-20' },
  { id: 'PI-1004', name: 'প্রকৌশলী সাজিদ আহমেদ', phone: '01755667788', area: 'রেল স্টেশন মোড়', package: 'Premium (15 Mbps)', fee: 1000, pppoe: 'sajid_home', pppoePass: 'sajid@pi', due: 2000, status: 'suspended', expireDate: '2026-09-06', joined: '2025-03-01' },
  { id: 'PI-1005', name: 'আল-মদিনা ফার্মেসি', phone: '01633445566', area: 'হাসপাতাল রোড', package: 'Standard (10 Mbps)', fee: 800, pppoe: 'madina_pharmacy', pppoePass: 'madina#1', due: 800, status: 'active', expireDate: '2026-09-09', joined: '2025-01-25' },
  { id: 'PI-1006', name: 'মালেক মাহমুদ', phone: '01777889900', area: 'চৌধুরী পাড়া', package: 'Standard (10 Mbps)', fee: 800, pppoe: 'malek_pi', pppoePass: 'pi1234', due: 0, status: 'active', expireDate: '2026-09-25', joined: '2024-12-05' },
  { id: 'PI-1007', name: 'ডাঃ মোস্তাফিজুর রহমান', phone: '01812349911', area: 'হাসপাতাল রোড', package: 'Turbo (20 Mbps)', fee: 1200, pppoe: 'dr_mostafiz', pppoePass: 'doc@net', due: 1200, status: 'suspended', expireDate: '2026-09-07', joined: '2025-04-12' },
  { id: 'PI-1008', name: 'ফারহানা ইয়াসমিন', phone: '01555666777', area: 'কলেজ রোড', package: 'Starter (5 Mbps)', fee: 500, pppoe: 'farhana_home', pppoePass: 'home555', due: 1000, status: 'active', expireDate: '2026-09-12', joined: '2025-05-18' }
];

const INITIAL_PACKAGES = [
  { id: 'PKG-01', name: 'Starter (5 Mbps)', speed: '5 Mbps', price: 500 },
  { id: 'PKG-02', name: 'Standard (10 Mbps)', speed: '10 Mbps', price: 800 },
  { id: 'PKG-03', name: 'Premium (15 Mbps)', speed: '15 Mbps', price: 1000 },
  { id: 'PKG-04', name: 'Turbo (20 Mbps)', speed: '20 Mbps', price: 1200 }
];

const INITIAL_AREAS = [
  'পীরগাছা বাজার', 'কলেজ রোড', 'রেল স্টেশন মোড়', 'হাসপাতাল রোড', 'চৌধুরী পাড়া'
];

const INITIAL_STAFF = [
  { 
    id: 'ST-101', 
    name: 'রাশেদুল ইসলাম (এডমিন)', 
    username: 'admin', 
    password: '123', 
    phone: '01700000000', 
    role: 'admin', 
    permissions: ['all'], 
    salary: 30000, 
    status: 'active', 
    joined: '2024-01-01' 
  },
  { 
    id: 'ST-102', 
    name: 'মোঃ তারেক রহমান', 
    username: 'tarek', 
    password: '123', 
    phone: '01711112222', 
    role: 'staff', 
    permissions: ['bill_pay', 'bill_add', 'cust_renew', 'complaint_manage'], 
    salary: 12000, 
    status: 'active', 
    joined: '2024-06-15' 
  },
  { 
    id: 'ST-103', 
    name: 'মোঃ করিম মিয়া', 
    username: 'karim', 
    password: '123', 
    phone: '01733334444', 
    role: 'staff', 
    permissions: ['bill_pay', 'complaint_manage'], 
    salary: 10000, 
    status: 'active', 
    joined: '2024-08-01' 
  }
];

const INITIAL_PAYMENTS = [
  { receiptNo: 'PI-REC-8901', custId: 'PI-1003', custName: 'নুরুল হুদা ট্রেডার্স', amount: 1200, discount: 0, method: 'Cash', staff: 'মোঃ তারেক রহমান', date: '2026-09-08 09:30 AM', synced: true },
  { receiptNo: 'PI-REC-8902', custId: 'PI-1006', custName: 'মালেক মাহমুদ', amount: 800, discount: 0, method: 'bKash', staff: 'মোঃ করিম মিয়া', date: '2026-09-08 10:15 AM', synced: true }
];

const INITIAL_EXPENSES = [
  { id: 'EXP-501', voucherNo: 'V-101', date: '2026-09-08', category: 'বিদ্যুৎ বিল', staffName: '-', amount: 1850, method: 'বিকাশ', paidBy: 'admin', description: 'অফিস সেপ্টেম্বর পল্লী বিদ্যুৎ বিল' },
  { id: 'EXP-502', voucherNo: 'V-102', date: '2026-09-08', category: 'স্টাফ বেতন', staffName: 'মোঃ করিম মিয়া', amount: 5000, method: 'ক্যাশ', paidBy: 'admin', description: 'চলতি মাসের অগ্রিম বেতন' },
  { id: 'EXP-503', voucherNo: 'V-103', date: '2026-09-07', category: 'যন্ত্রাংশ ক্রয়', staffName: '-', amount: 3200, method: 'ক্যাশ', paidBy: 'admin', description: '২ রোল ২-কোর ড্রপ তার ও প্যাচ কর্ড ক্রয়' }
];

const INITIAL_COMPLAINTS = [
  { id: 'TCK-201', date: '2026-09-08 08:30 AM', custId: 'PI-1001', custName: 'মোঃ আব্দুর রহিম', custPhone: '01712345678', area: 'পীরগাছা বাজার', issue: 'ইন্টারনেট নেই / লাল বাতি (LOS)', priority: 'Urgent', status: 'Pending', assignedStaff: 'মোঃ তারেক রহমান', notes: 'সকালে হঠাৎ লাল বাতি জ্বলছে, কাজ আটকে আছে।', solutionNote: '' },
  { id: 'TCK-202', date: '2026-09-08 09:10 AM', custId: 'PI-1004', custName: 'প্রকৌশলী সাজিদ আহমেদ', custPhone: '01755667788', area: 'রেল স্টেশন মোড়', issue: 'গতি অত্যন্ত কম (Slow Speed)', priority: 'High', status: 'In Progress', assignedStaff: 'মোঃ করিম মিয়া', notes: 'বিকেল থেকে স্পিড ড্রপ করছে।', solutionNote: 'মাইক্রোটিক কিউ চেক করা হচ্ছে।' },
  { id: 'TCK-203', date: '2026-09-07 04:20 PM', custId: 'PI-1002', custName: 'মোঃ কামরুল হাসান', custPhone: '01898765432', area: 'কলেজ রোড', issue: 'তার কাটা / ফাইবার সমস্যা', priority: 'High', status: 'Resolved', assignedStaff: 'মোঃ তারেক রহমান', notes: 'রোডের গাছে ড্রপ তার ছিঁড়ে গেছে।', solutionNote: 'নতুন ড্রপ তার টেনে কানেক্ট করা হয়েছে।' }
];

// App State Management
const app = {
  currentUser: null,
  customers: [],
  packages: [],
  areas: [],
  payments: [],
  expenses: [],
  complaints: [],
  staffUsers: [],
  syncQueue: [],
  currentFilter: 'all',
  currentAreaFilter: '',
  complaintFilter: 'all',
  searchQuery: '',
  activeTab: 'cards',
  selectedCustForPay: null,
  selectedCustForCharge: null,
  gasApiUrl: '',

  // Initialize
  init() {
    this.loadStorage();
    this.setupNetworkListeners();
    this.setupEventListeners();
    this.renderPackageDropdowns();
    this.renderAreaDropdowns();
    
    // Check if user is logged in
    if (this.checkAuth()) {
      this.updateUserUI();
      this.render();
      this.checkSyncQueue();
    }
  },

  // Local Storage Management
  loadStorage() {
    try {
      const storedCust = localStorage.getItem('pirgacha_customers');
      this.customers = storedCust ? JSON.parse(storedCust) : [...INITIAL_CUSTOMERS];
      // Backfill expireDate for existing stored customers if missing
      this.customers.forEach(c => {
        if (!c.expireDate) {
          const initMatch = INITIAL_CUSTOMERS.find(ic => ic.id === c.id);
          c.expireDate = initMatch && initMatch.expireDate ? initMatch.expireDate : '2026-09-30';
        }
      });

      const storedPackages = localStorage.getItem('pirgacha_packages');
      this.packages = storedPackages ? JSON.parse(storedPackages) : [...INITIAL_PACKAGES];

      const storedAreas = localStorage.getItem('pirgacha_areas');
      this.areas = storedAreas ? JSON.parse(storedAreas) : [...INITIAL_AREAS];

      const storedStaff = localStorage.getItem('pirgacha_staff_users');
      if (storedStaff) {
        this.staffUsers = JSON.parse(storedStaff);
        // Backfill passwords and permissions for existing stored staff if missing
        this.staffUsers.forEach(u => {
          if (!u.password) u.password = '123';
          if (!u.permissions || !Array.isArray(u.permissions)) {
            u.permissions = u.role === 'admin' ? ['all'] : ['bill_pay', 'cust_renew', 'complaint_manage'];
          } else if (u.role === 'staff' && !u.permissions.includes('cust_renew') && u.permissions.includes('bill_pay')) {
            u.permissions.push('cust_renew');
          }
        });
      } else {
        this.staffUsers = JSON.parse(JSON.stringify(INITIAL_STAFF));
      }

      const storedPayments = localStorage.getItem('pirgacha_payments');
      this.payments = storedPayments ? JSON.parse(storedPayments) : [...INITIAL_PAYMENTS];

      const storedExpenses = localStorage.getItem('pirgacha_expenses');
      this.expenses = storedExpenses ? JSON.parse(storedExpenses) : [...INITIAL_EXPENSES];

      const storedComplaints = localStorage.getItem('pirgacha_complaints');
      this.complaints = storedComplaints ? JSON.parse(storedComplaints) : [...INITIAL_COMPLAINTS];

      const storedQueue = localStorage.getItem('pirgacha_sync_queue');
      this.syncQueue = storedQueue ? JSON.parse(storedQueue) : [];

      // Auth Session: Check logged in user ID
      const loggedUserId = localStorage.getItem('pirgacha_logged_user_id');
      if (loggedUserId) {
        const found = this.staffUsers.find(u => u.id === loggedUserId && u.status === 'active');
        if (found) {
          this.currentUser = found;
        } else {
          this.currentUser = null;
        }
      } else {
        this.currentUser = null;
      }

      this.gasApiUrl = localStorage.getItem('pirgacha_gas_url') || '';
      const gasInput = document.getElementById('setting-gas-url');
      if (gasInput) gasInput.value = this.gasApiUrl;
    } catch (err) {
      console.error('Storage load error:', err);
      this.customers = [...INITIAL_CUSTOMERS];
      this.packages = [...INITIAL_PACKAGES];
      this.areas = [...INITIAL_AREAS];
      this.staffUsers = JSON.parse(JSON.stringify(INITIAL_STAFF));
      this.payments = [...INITIAL_PAYMENTS];
      this.expenses = [...INITIAL_EXPENSES];
      this.complaints = [...INITIAL_COMPLAINTS];
      this.currentUser = null;
    }
  },

  saveStorage() {
    try {
      localStorage.setItem('pirgacha_customers', JSON.stringify(this.customers));
      localStorage.setItem('pirgacha_packages', JSON.stringify(this.packages));
      localStorage.setItem('pirgacha_areas', JSON.stringify(this.areas));
      localStorage.setItem('pirgacha_staff_users', JSON.stringify(this.staffUsers));
      localStorage.setItem('pirgacha_payments', JSON.stringify(this.payments));
      localStorage.setItem('pirgacha_expenses', JSON.stringify(this.expenses));
      localStorage.setItem('pirgacha_complaints', JSON.stringify(this.complaints));
      localStorage.setItem('pirgacha_sync_queue', JSON.stringify(this.syncQueue));
    } catch (err) {
      console.error('Storage save error:', err);
    }
  },

  // Network & Online/Offline Listener
  setupNetworkListeners() {
    const updateNetworkStatus = () => {
      const isOnline = navigator.onLine;
      const statusPill = document.getElementById('connection-status');
      const statusText = document.getElementById('connection-text');

      if (isOnline) {
        statusPill.className = 'status-pill online';
        statusText.textContent = 'অনলাইন';
        if (this.syncQueue.length > 0) {
          this.syncPendingQueue(true);
        }
      } else {
        statusPill.className = 'status-pill offline';
        statusText.textContent = 'অফলাইন মোড';
      }
    };

    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
    updateNetworkStatus();
  },

  setupEventListeners() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        this.searchQuery = e.target.value.trim().toLowerCase();
        const clearBtn = document.getElementById('search-clear-btn');
        if (clearBtn) {
          clearBtn.classList.toggle('active', this.searchQuery.length > 0);
        }
        this.renderCustomerViews();
      });

      window.addEventListener('keydown', (e) => {
        if (e.key === '/' && document.activeElement !== searchInput && !document.querySelector('.modal-backdrop.open')) {
          e.preventDefault();
          searchInput.focus();
        }
      });
    }
  },

  clearSearch() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
      searchInput.value = '';
      this.searchQuery = '';
      document.getElementById('search-clear-btn').classList.remove('active');
      this.renderCustomerViews();
      searchInput.focus();
    }
  },

  setFilter(filter) {
    this.currentFilter = filter;
    document.querySelectorAll('.filter-chips .chip-btn').forEach(btn => {
      if (btn.dataset.filter) {
        btn.classList.toggle('active', btn.dataset.filter === filter);
      }
    });
    this.renderCustomerViews();
  },

  setAreaFilter(area) {
    this.currentAreaFilter = area;
    this.renderCustomerViews();
  },

  switchTab(tabKey) {
    this.activeTab = tabKey;
    const tabs = ['cards', 'table', 'packages-areas', 'expenses', 'complaints', 'staff-users', 'staff', 'history'];
    tabs.forEach(t => {
      const el = document.getElementById(`tab-${t}-view`);
      if (el) el.style.display = t === tabKey ? 'block' : 'none';
    });

    document.querySelectorAll('.admin-tabs .tab-btn').forEach(btn => {
      btn.classList.remove('active');
      if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(tabKey)) {
        btn.classList.add('active');
      }
    });

    if (tabKey === 'packages-areas') this.renderPackagesAndAreas();
    if (tabKey === 'expenses') this.renderExpenses();
    if (tabKey === 'complaints') this.renderComplaints();
    if (tabKey === 'staff-users') this.renderStaffUsers();
    if (tabKey === 'staff') this.renderStaffReport();
    if (tabKey === 'history') this.renderPaymentHistory();
  },

  // Authentication & Session Management
  checkAuth() {
    if (this.currentUser && this.currentUser.status === 'active') {
      this.closeModal('login-modal');
      return true;
    }
    this.openLoginModal();
    return false;
  },

  openLoginModal() {
    const errorEl = document.getElementById('login-error-alert');
    if (errorEl) {
      errorEl.style.display = 'none';
      errorEl.textContent = '';
    }
    const usernameInput = document.getElementById('login-username');
    if (usernameInput) {
      usernameInput.value = '';
      setTimeout(() => usernameInput.focus(), 150);
    }
    const passwordInput = document.getElementById('login-password');
    if (passwordInput) passwordInput.value = '';

    this.openModal('login-modal');
  },

  fillDemoLogin(username, password) {
    const userField = document.getElementById('login-username');
    const passField = document.getElementById('login-password');
    if (userField) userField.value = username;
    if (passField) passField.value = password;
    this.submitLogin();
  },

  submitLogin() {
    const userField = document.getElementById('login-username');
    const passField = document.getElementById('login-password');
    const errorEl = document.getElementById('login-error-alert');

    const username = userField ? userField.value.trim().toLowerCase() : '';
    const password = passField ? passField.value.trim() : '';

    if (!username || !password) {
      if (errorEl) {
        errorEl.textContent = 'ইউজারনেম এবং পাসওয়ার্ড উভয়ই পূরণ করুন!';
        errorEl.style.display = 'block';
      }
      return;
    }

    const found = this.staffUsers.find(u => 
      (u.username.toLowerCase() === username || u.phone === username) && 
      String(u.password) === password
    );

    if (!found) {
      if (errorEl) {
        errorEl.textContent = '❌ ভুল ইউজারনেম বা পাসওয়ার্ড! অনুগ্রহ করে সঠিক তথ্য দিন।';
        errorEl.style.display = 'block';
      }
      return;
    }

    if (found.status === 'inactive') {
      if (errorEl) {
        errorEl.textContent = '⚠️ আপনার অ্যাকাউন্টটি বর্তমানে বন্ধ (Inactive) রয়েছে! এডমিনের সাথে যোগাযোগ করুন।';
        errorEl.style.display = 'block';
      }
      return;
    }

    this.currentUser = found;
    localStorage.setItem('pirgacha_logged_user_id', found.id);
    if (errorEl) errorEl.style.display = 'none';
    this.closeModal('login-modal');

    // Switch to cards tab by default on login
    this.switchTab('cards');
    this.updateUserUI();
    this.render();
    this.checkSyncQueue();
  },

  logout() {
    if (!confirm('আপনি কি নিশ্চিত যে সিস্টেম থেকে লগআউট করতে চান?')) {
      return;
    }
    localStorage.removeItem('pirgacha_logged_user_id');
    this.currentUser = null;
    this.openLoginModal();
  },

  hasPermission(permKey) {
    if (!this.currentUser) return false;
    if (this.currentUser.role === 'admin') return true;
    const perms = this.currentUser.permissions || [];
    if (perms.includes('all')) return true;
    return perms.includes(permKey);
  },

  updateUserUI() {
    if (!this.currentUser) return;
    const isAdmin = this.currentUser.role === 'admin';
    const nameEl = document.getElementById('header-user-name');
    const roleEl = document.getElementById('header-user-role');
    const avatarEl = document.getElementById('header-user-avatar');

    if (nameEl) nameEl.textContent = this.currentUser.name;
    if (roleEl) {
      roleEl.textContent = this.currentUser.role.toUpperCase();
      roleEl.className = `user-role-badge ${isAdmin ? 'role-admin' : 'role-staff'}`;
    }
    if (avatarEl) {
      avatarEl.textContent = this.currentUser.name.charAt(0);
    }

    // Toggle Admin Only Elements (Strictly for admin)
    document.querySelectorAll('.admin-only').forEach(el => {
      el.style.display = isAdmin ? '' : 'none';
    });

    // Granular Permission-Based UI Enforcement for Tabs & Action Buttons
    const setVisibility = (elemId, isAllowed) => {
      const el = document.getElementById(elemId);
      if (el) el.style.display = isAllowed ? '' : 'none';
    };

    // 1. Quick Action Buttons Visibility
    setVisibility('btn-quick-renew', this.hasPermission('cust_renew') || this.hasPermission('bill_pay'));
    setVisibility('btn-quick-new-bill', this.hasPermission('bill_add'));
    setVisibility('btn-quick-add-cust', this.hasPermission('cust_manage'));
    setVisibility('btn-quick-pkg-area', this.hasPermission('package_manage'));
    setVisibility('btn-quick-staff-users', isAdmin);
    setVisibility('btn-quick-expense', this.hasPermission('expense_manage'));
    setVisibility('btn-quick-complaint', this.hasPermission('complaint_manage'));
    setVisibility('btn-quick-gen-bill', isAdmin);
    setVisibility('btn-quick-settings', isAdmin);

    // 2. Admin Tabs Visibility
    setVisibility('tab-btn-table', this.hasPermission('reports_view'));
    setVisibility('tab-btn-packages-areas', this.hasPermission('package_manage'));
    setVisibility('tab-btn-expenses', this.hasPermission('expense_manage'));
    setVisibility('tab-btn-complaints', this.hasPermission('complaint_manage'));
    setVisibility('tab-btn-staff-users', isAdmin);
    setVisibility('tab-btn-staff', this.hasPermission('reports_view'));
    setVisibility('tab-btn-history', this.hasPermission('reports_view'));

    // If current tab is restricted, redirect to 'cards'
    const tabPermMap = {
      'table': 'reports_view',
      'packages-areas': 'package_manage',
      'expenses': 'expense_manage',
      'complaints': 'complaint_manage',
      'staff-users': 'admin',
      'staff': 'reports_view',
      'history': 'reports_view'
    };
    const requiredPerm = tabPermMap[this.activeTab];
    if (requiredPerm) {
      const allowed = requiredPerm === 'admin' ? isAdmin : this.hasPermission(requiredPerm);
      if (!allowed) {
        this.switchTab('cards');
      }
    }
  },

  getTodayStr() {
    return new Date().toISOString().slice(0, 10);
  },

  getExpiryStatus(expireDate) {
    if (!expireDate) {
      return { daysDiff: 0, label: 'মেয়াদ নির্ধারিত নেই', badgeClass: 'status-badge', isExpired: false, isToday: false };
    }
    const todayStr = this.getTodayStr();
    const today = new Date(todayStr + 'T00:00:00');
    const exp = new Date(expireDate + 'T00:00:00');
    const diffTime = exp.getTime() - today.getTime();
    const daysDiff = Math.round(diffTime / (1000 * 60 * 60 * 24));

    if (daysDiff < 0) {
      return {
        daysDiff,
        label: `মেয়াদ শেষ (${Math.abs(daysDiff)} দিন আগে)`,
        badgeClass: 'status-badge status-expired',
        isExpired: true,
        isToday: false
      };
    } else if (daysDiff === 0) {
      return {
        daysDiff,
        label: 'আজ মেয়াদ শেষ ⚠️',
        badgeClass: 'status-badge status-warning',
        isExpired: true,
        isToday: true
      };
    } else if (daysDiff <= 3) {
      return {
        daysDiff,
        label: `আর ${daysDiff} দিন বাকি ⏳`,
        badgeClass: 'status-badge status-warning',
        isExpired: false,
        isToday: false
      };
    } else {
      return {
        daysDiff,
        label: `আর ${daysDiff} দিন বাকি`,
        badgeClass: 'status-badge status-active',
        isExpired: false,
        isToday: false
      };
    }
  },

  // Calculations & Analytics (Billing, Expenses, Net Cash)
  calculateStats() {
    const todayStr = this.getTodayStr();
    let todayCollection = 0;
    let todayCount = 0;

    this.payments.forEach(p => {
      if (p.date.includes(todayStr) || p.date.includes('2026-09-08')) {
        if (this.currentUser.role === 'staff') {
          if (p.staff === this.currentUser.name) {
            todayCollection += Number(p.amount) || 0;
            todayCount++;
          }
        } else {
          todayCollection += Number(p.amount) || 0;
          todayCount++;
        }
      }
    });

    let todayExpense = 0;
    this.expenses.forEach(e => {
      if (e.date.includes(todayStr) || e.date.includes('2026-09-08')) {
        todayExpense += Number(e.amount) || 0;
      }
    });

    const netCashBalance = todayCollection - todayExpense;

    let totalDue = 0;
    let dueCount = 0;
    let monthlyTotal = 0;
    let activeCustomers = 0;

    this.customers.forEach(c => {
      if (c.status === 'active') {
        activeCustomers++;
        monthlyTotal += Number(c.fee) || 0;
      }
      if (Number(c.due) > 0) {
        totalDue += Number(c.due) || 0;
        dueCount++;
      }
    });

    const pendingComplaints = this.complaints.filter(c => c.status === 'Pending').length;

    return { 
      todayCollection, 
      todayCount, 
      todayExpense, 
      netCashBalance, 
      totalDue, 
      dueCount, 
      monthlyTotal, 
      activeCustomers,
      pendingComplaints
    };
  },

  // Main Render Routine
  render() {
    const stats = this.calculateStats();

    // Stats Cards
    const statTodayEl = document.getElementById('stat-today-collection');
    const statTodayCountEl = document.getElementById('stat-today-count');
    if (statTodayEl) statTodayEl.textContent = `৳ ${stats.todayCollection.toLocaleString('bn-BD')}`;
    if (statTodayCountEl) {
      statTodayCountEl.textContent = this.currentUser.role === 'staff' 
        ? `আপনার সংগৃহীত: ${stats.todayCount} টি বিল` 
        : `${stats.todayCount} টি পেমেন্ট গৃহীত`;
    }

    const statExpenseEl = document.getElementById('stat-today-expense');
    if (statExpenseEl) statExpenseEl.textContent = `৳ ${stats.todayExpense.toLocaleString('bn-BD')}`;

    const statNetEl = document.getElementById('stat-net-balance');
    if (statNetEl) {
      statNetEl.textContent = `৳ ${stats.netCashBalance.toLocaleString('bn-BD')}`;
      statNetEl.style.color = stats.netCashBalance >= 0 ? 'var(--purple-600)' : 'var(--danger-500)';
    }

    const statDueEl = document.getElementById('stat-total-due');
    const statDueCountEl = document.getElementById('stat-due-count');
    if (statDueEl) statDueEl.textContent = `৳ ${stats.totalDue.toLocaleString('bn-BD')}`;
    if (statDueCountEl) statDueCountEl.textContent = `${stats.dueCount} জন গ্রাহকের বাকি`;

    const statMonthlyEl = document.getElementById('stat-monthly-total');
    if (statMonthlyEl) statMonthlyEl.textContent = `৳ ${stats.monthlyTotal.toLocaleString('bn-BD')}`;

    const statCustEl = document.getElementById('stat-total-customers');
    if (statCustEl) statCustEl.textContent = stats.activeCustomers.toLocaleString('bn-BD');

    // Complaint Header Badge
    const compCountEl = document.getElementById('header-complaint-count');
    if (compCountEl) compCountEl.textContent = stats.pendingComplaints;

    // Counts in filter chips
    document.getElementById('count-all').textContent = this.customers.length;
    document.getElementById('count-due').textContent = this.customers.filter(c => c.due > 0).length;
    document.getElementById('count-paid').textContent = this.customers.filter(c => c.due <= 0).length;
    const expiredCount = this.customers.filter(c => {
      const exp = this.getExpiryStatus(c.expireDate);
      return exp.isExpired || exp.isToday;
    }).length;
    const countExpiredEl = document.getElementById('count-expired');
    if (countExpiredEl) countExpiredEl.textContent = expiredCount;

    this.renderCustomerViews();
  },

  // Customer Filtering Logic
  getFilteredCustomers() {
    return this.customers.filter(c => {
      if (this.currentFilter === 'due' && c.due <= 0) return false;
      if (this.currentFilter === 'paid' && c.due > 0) return false;
      if (this.currentFilter === 'expired') {
        const exp = this.getExpiryStatus(c.expireDate);
        if (!exp.isExpired && !exp.isToday) return false;
      }
      if (this.currentAreaFilter && c.area !== this.currentAreaFilter) return false;

      if (this.searchQuery) {
        const q = this.searchQuery;
        const matchName = (c.name || '').toLowerCase().includes(q);
        const matchPhone = (c.phone || '').includes(q);
        const matchId = (c.id || '').toLowerCase().includes(q);
        const matchPppoe = (c.pppoe || '').toLowerCase().includes(q);
        const matchArea = (c.area || '').toLowerCase().includes(q);
        return matchName || matchPhone || matchId || matchPppoe || matchArea;
      }

      return true;
    });
  },

  // Render Customer Cards & Admin Table
  renderCustomerViews() {
    const list = this.getFilteredCustomers();
    const gridEl = document.getElementById('customer-grid');
    const tableBody = document.getElementById('customer-table-body');
    const canPay = this.hasPermission('bill_pay');
    const canCharge = this.hasPermission('bill_add');
    const canRenew = this.hasPermission('cust_renew') || this.hasPermission('bill_pay');
    const canEditCust = this.hasPermission('cust_manage');

    // 1. Render Cards
    if (gridEl) {
      if (list.length === 0) {
        gridEl.innerHTML = `
          <div class="empty-state" style="grid-column: 1 / -1;">
            <div class="empty-state-icon">🔍</div>
            <h3>কোনো গ্রাহক পাওয়া যায়নি</h3>
            <p>অনুগ্রহ করে মোবাইল নাম্বার, নাম বা কাস্টমার আইডি ঠিকভাবে লিখুন।</p>
          </div>
        `;
      } else {
        gridEl.innerHTML = list.map(c => {
          const isDue = Number(c.due) > 0;
          const expInfo = this.getExpiryStatus(c.expireDate);
          return `
            <div class="customer-card">
              <div>
                <div class="card-top">
                  <div class="client-meta">
                    <div class="client-avatar-badge">${(c.name || 'P').charAt(0)}</div>
                    <div class="client-titles">
                      <h3>${c.name}</h3>
                      <span class="client-id-tag">ID: ${c.id}</span>
                    </div>
                  </div>
                  <span class="${expInfo.isExpired ? expInfo.badgeClass : (c.status === 'active' ? 'status-badge status-active' : 'status-badge status-suspended')}">
                    ${expInfo.isExpired ? expInfo.label : (c.status === 'active' ? 'Active' : 'Suspended')}
                  </span>
                </div>

                <ul class="card-details-list">
                  <li><span class="detail-icon">📞</span> <strong class="highlight-val">${c.phone}</strong></li>
                  <li><span class="detail-icon">📍</span> ${c.area}</li>
                  <li><span class="detail-icon">🌐</span> PPPoE: <code>${c.pppoe || 'N/A'}</code> | পাসওয়ার্ড: <code style="color: var(--accent-600); font-weight: 700; background: #ffedd5; padding: 1px 6px; border-radius: 4px;">${c.pppoePass || '123456'}</code></li>
                  <li><span class="detail-icon">📦</span> ${c.package}</li>
                  <li>
                    <span class="detail-icon">⏳</span> 
                    <span>মেয়াদ: <strong>${c.expireDate || 'নির্ধারিত নেই'}</strong> <span class="${expInfo.badgeClass}" style="margin-left: 4px;">${expInfo.label}</span></span>
                  </li>
                </ul>

                <div class="card-finance-box">
                  <div class="finance-item">
                    <span class="finance-label">মাসিক ফি</span>
                    <span class="finance-bill">৳ ${Number(c.fee).toLocaleString('bn-BD')}</span>
                  </div>
                  <div class="finance-item" style="text-align: right;">
                    <span class="finance-label">বর্তমান বকেয়া</span>
                    <span class="finance-due ${!isDue ? 'paid' : ''}">
                      ${isDue ? `৳ ${Number(c.due).toLocaleString('bn-BD')}` : 'পরিশোধিত ✓'}
                    </span>
                  </div>
                </div>
              </div>

              <div class="card-actions">
                ${canRenew ? `
                  <button class="btn btn-renew" onclick="app.openRenewModal('${c.id}')" title="২/৩ দিন বা নির্দিষ্ট মেয়াদের লাইন রিনিউ করুন">
                    🔄 রিনিউ
                  </button>
                ` : ''}
                ${canPay ? `
                  <button class="btn btn-pay" onclick="app.openPayModal('${c.id}')" title="বিল বা বকেয়া আদায়">
                    💰 বিল
                  </button>
                ` : ''}
                ${canCharge ? `
                  <button class="btn btn-charge" onclick="app.openChargeModal('${c.id}')" title="নতুন রাউটার, তার বা ইনস্টলেশন ফি যোগ">
                    ➕ চার্জ
                  </button>
                ` : ''}
                <a href="tel:${c.phone}" class="btn btn-call" title="সরাসরি গ্রাহককে ফোন দিন">
                  📞 কল
                </a>
              </div>
            </div>
          `;
        }).join('');
      }
    }

    // 2. Render Admin Data Table
    if (tableBody) {
      tableBody.innerHTML = list.map(c => {
        const expInfo = this.getExpiryStatus(c.expireDate);
        return `
          <tr>
            <td><strong>${c.id}</strong></td>
            <td>${c.name}</td>
            <td><a href="tel:${c.phone}">${c.phone}</a></td>
            <td>${c.area}</td>
            <td>
              <code>${c.pppoe || '-'}</code><br>
              <small style="color: var(--accent-600); font-weight: 700;">পাসওয়ার্ড: ${c.pppoePass || '-'}</small>
            </td>
            <td>৳ ${c.fee}</td>
            <td><strong style="color: ${c.due > 0 ? 'var(--danger-500)' : 'var(--success-600)'}">৳ ${c.due}</strong></td>
            <td>
              <span class="status-badge ${c.status === 'active' ? 'status-active' : 'status-suspended'}">${c.status}</span><br>
              <span class="${expInfo.badgeClass}" style="margin-top: 4px; display: inline-block;">${c.expireDate || 'N/A'}</span>
            </td>
            <td>
              ${canRenew ? `<button class="btn btn-renew btn-sm" onclick="app.openRenewModal('${c.id}')" style="margin-right: 3px;">রিনিউ</button>` : ''}
              ${canPay ? `<button class="btn btn-primary btn-sm" onclick="app.openPayModal('${c.id}')" style="margin-right: 3px;">বিল</button>` : ''}
              ${canCharge ? `<button class="btn btn-outline btn-sm" onclick="app.openChargeModal('${c.id}')" style="margin-right: 3px;">চার্জ</button>` : ''}
              ${canEditCust ? `<button class="btn btn-outline btn-sm" onclick="app.openEditCustomerModal('${c.id}')">এডিট</button>` : ''}
            </td>
          </tr>
        `;
      }).join('');
    }
  },

  // =========================================================================
  // MODULE 1: EXPENSE & SALARY MANAGEMENT
  // =========================================================================
  openExpenseModal() {
    if (!this.hasPermission('expense_manage')) {
      alert('খরচ বা বেতন এন্ট্রি করার অনুমতি আপনার নেই!');
      return;
    }
    // Populate staff dropdown for salary
    const staffSelect = document.getElementById('exp-staff-select');
    if (staffSelect) {
      staffSelect.innerHTML = this.staffUsers.map(u => `
        <option value="${u.name}" data-salary="${u.salary}">${u.name} (বেতন: ৳ ${u.salary})</option>
      `).join('');
    }

    this.onExpenseCategoryChange(document.getElementById('exp-category-select').value);
    document.getElementById('exp-amount-input').value = '';
    document.getElementById('exp-note-input').value = '';
    this.openModal('expense-modal');
  },

  onExpenseCategoryChange(category) {
    const staffGroup = document.getElementById('exp-staff-group');
    const isSalary = category === 'স্টাফ বেতন';
    if (staffGroup) {
      staffGroup.style.display = isSalary ? 'block' : 'none';
      if (isSalary) {
        const staffSelect = document.getElementById('exp-staff-select');
        if (staffSelect && staffSelect.selectedIndex >= 0) {
          const defaultSalary = staffSelect.options[staffSelect.selectedIndex].getAttribute('data-salary');
          if (defaultSalary) document.getElementById('exp-amount-input').value = defaultSalary;
        }
      }
    }
  },

  onStaffSalarySelect(staffName) {
    const staffSelect = document.getElementById('exp-staff-select');
    const selectedOption = staffSelect.options[staffSelect.selectedIndex];
    const salary = selectedOption.getAttribute('data-salary');
    if (salary) {
      document.getElementById('exp-amount-input').value = salary;
    }
  },

  saveExpense() {
    if (!this.hasPermission('expense_manage')) {
      alert('খরচ সংরক্ষণ করার অনুমতি আপনার নেই!');
      return;
    }
    const category = document.getElementById('exp-category-select').value;
    const amount = Number(document.getElementById('exp-amount-input').value) || 0;
    const method = document.getElementById('exp-method-select').value;
    const note = document.getElementById('exp-note-input').value.trim();
    let staffName = '-';

    if (category === 'স্টাফ বেতন') {
      staffName = document.getElementById('exp-staff-select').value;
    }

    if (amount <= 0) {
      alert('অনুগ্রহ করে সঠিক খরচের পরিমাণ লিখুন!');
      return;
    }

    const nextId = `EXP-${500 + this.expenses.length + 1}`;
    const voucherNo = `V-${100 + this.expenses.length + 1}`;
    const todayStr = new Date().toISOString().slice(0, 10);

    const expenseRecord = {
      id: nextId,
      voucherNo,
      date: todayStr,
      category,
      staffName,
      amount,
      method,
      paidBy: this.currentUser.name,
      description: note || category
    };

    this.expenses.unshift(expenseRecord);
    this.saveStorage();
    this.render();
    this.renderExpenses();
    this.closeModal('expense-modal');
    alert('খরচ সফলভাবে এন্ট্রি করা হয়েছে!');
  },

  deleteExpense(id) {
    if (this.currentUser.role !== 'admin') {
      alert('শুধুমাত্র এডমিন খরচ ডিলিট করতে পারে!');
      return;
    }
    if (confirm('আপনি কি নিশ্চিত এই খরচের রেকর্ড মুছে ফেলতে চান?')) {
      this.expenses = this.expenses.filter(e => e.id !== id);
      this.saveStorage();
      this.render();
      this.renderExpenses();
    }
  },

  renderExpenses() {
    const tableBody = document.getElementById('expenses-table-body');
    const badge = document.getElementById('expense-total-badge');
    if (!tableBody) return;

    let totalExp = 0;
    tableBody.innerHTML = this.expenses.map(e => {
      totalExp += Number(e.amount) || 0;
      return `
        <tr>
          <td><code>${e.voucherNo}</code></td>
          <td>${e.date}</td>
          <td><span class="user-role-badge role-admin">${e.category}</span></td>
          <td>
            <strong>${e.description}</strong>
            ${e.staffName !== '-' ? `<br><small style="color: var(--primary-600);">স্টাফ: ${e.staffName}</small>` : ''}
          </td>
          <td><strong style="color: var(--danger-500); font-size: 1rem;">৳ ${Number(e.amount).toLocaleString('bn-BD')}</strong></td>
          <td>${e.method}</td>
          <td>${e.paidBy}</td>
          <td>
            <button class="btn btn-outline btn-sm admin-only" onclick="app.deleteExpense('${e.id}')" style="color: var(--danger-500);">মুছুন</button>
          </td>
        </tr>
      `;
    }).join('');

    if (badge) badge.textContent = `মোট খরচ: ৳ ${totalExp.toLocaleString('bn-BD')}`;
  },

  // =========================================================================
  // MODULE 2: QUICK EXTRA BILL / CHARGE FOR STAFF & ADMIN
  // =========================================================================
  openChargeModal(custId) {
    if (!this.hasPermission('bill_add')) {
      alert('অতিরিক্ত চার্জ বা বিল যোগ করার অনুমতি আপনার নেই!');
      return;
    }
    const cust = this.customers.find(c => c.id === custId);
    if (!cust) return;
    this.selectedCustForCharge = cust;

    document.getElementById('charge-cust-id').value = cust.id;
    document.getElementById('charge-cust-name').textContent = cust.name;
    document.getElementById('charge-cust-phone').textContent = `ID: ${cust.id} • ${cust.phone} • ${cust.area}`;
    document.getElementById('charge-cust-due').textContent = `৳ ${Number(cust.due).toLocaleString('bn-BD')}`;
    document.getElementById('charge-type-select').value = 'নতুন সংযোগ ফি';
    document.getElementById('charge-amount-input').value = 500;
    document.getElementById('charge-note-input').value = '';

    this.openModal('charge-modal');
  },

  onChargeTypeSelect(type) {
    const input = document.getElementById('charge-amount-input');
    if (type === 'নতুন সংযোগ ফি') input.value = 500;
    else if (type === 'রাউটার / ONU চার্জ') input.value = 1500;
    else if (type === 'অতিরিক্ত ফাইবার তার') input.value = 300;
    else if (type === 'মাসিক বিল সমন্বয়' && this.selectedCustForCharge) input.value = this.selectedCustForCharge.fee;
  },

  saveExtraCharge() {
    if (!this.hasPermission('bill_add')) {
      alert('অতিরিক্ত চার্জ যোগ করার অনুমতি আপনার নেই!');
      return;
    }
    const cust = this.selectedCustForCharge;
    if (!cust) return;

    const chargeType = document.getElementById('charge-type-select').value;
    const amount = Number(document.getElementById('charge-amount-input').value) || 0;
    const note = document.getElementById('charge-note-input').value.trim();

    if (amount <= 0) {
      alert('সঠিক টাকার পরিমাণ প্রদান করুন!');
      return;
    }

    // Add amount to customer due
    cust.due = Number(cust.due) + amount;

    // Log in payments history as a charge record
    this.saveStorage();
    this.render();
    this.closeModal('charge-modal');
    alert(`সফল! ${cust.name}-এর একাউন্টে ৳ ${amount} অতিরিক্ত চার্জ যোগ করা হয়েছে। বর্তমান মোট বকেয়া: ৳ ${cust.due}`);
  },

  // =========================================================================
  // STANDALONE NEW BILL WITH LIVE CUSTOMER SEARCH (FOR ADMIN & STAFF)
  // =========================================================================
  selectedCustForNewBill: null,

  openNewBillModal() {
    if (!this.hasPermission('bill_add')) {
      alert('নতুন বিল যোগ করার অনুমতি আপনার নেই!');
      return;
    }
    this.selectedCustForNewBill = null;
    const searchInput = document.getElementById('new-bill-search-input');
    const resultsBox = document.getElementById('new-bill-search-results');
    const previewBox = document.getElementById('new-bill-selected-box');

    if (searchInput) searchInput.value = '';
    if (resultsBox) {
      resultsBox.innerHTML = '';
      resultsBox.classList.remove('open');
    }
    if (previewBox) previewBox.classList.remove('active');

    document.getElementById('new-bill-type-select').value = 'মাসিক ইন্টারনেট ফি';
    
    // Refresh and reset package selector
    this.renderPackageDropdowns();
    const pkgSelect = document.getElementById('new-bill-package-select');
    if (pkgSelect) pkgSelect.value = '';

    // ৩. বিলের পরিমাণ খালি থাকবে
    const amountInput = document.getElementById('new-bill-amount-input');
    if (amountInput) {
      amountInput.value = '';
      amountInput.placeholder = 'প্যাকেজ নির্বাচন করলে টাকার পরিমাণ স্বয়ংক্রিয় বসবে';
    }

    // ৪. বিলের তারিখ ক্যালেন্ডার থেকে ডিফল্ট আজকের তারিখ সেট
    const todayDate = new Date().toISOString().slice(0, 10); // YYYY-MM-DD
    const dateInput = document.getElementById('new-bill-date-input');
    if (dateInput) dateInput.value = todayDate;

    document.getElementById('new-bill-note-input').value = '';

    this.openModal('new-bill-modal');
  },

  handleNewBillCustSearch(query) {
    const q = (query || '').trim().toLowerCase();
    const resultsBox = document.getElementById('new-bill-search-results');
    if (!resultsBox) return;

    if (!q) {
      resultsBox.innerHTML = '';
      resultsBox.classList.remove('open');
      return;
    }

    const matches = this.customers.filter(c => {
      const matchName = (c.name || '').toLowerCase().includes(q);
      const matchPhone = (c.phone || '').includes(q);
      const matchId = (c.id || '').toLowerCase().includes(q);
      const matchPppoe = (c.pppoe || '').toLowerCase().includes(q);
      const matchArea = (c.area || '').toLowerCase().includes(q);
      return matchName || matchPhone || matchId || matchPppoe || matchArea;
    }).slice(0, 8); // Top 8 matches

    if (matches.length === 0) {
      resultsBox.innerHTML = `
        <div style="padding: 0.85rem; color: var(--text-muted); font-size: 0.85rem; text-align: center;">
          কোনো গ্রাহক পাওয়া যায়নি।
        </div>
      `;
      resultsBox.classList.add('open');
      return;
    }

    resultsBox.innerHTML = matches.map(c => `
      <div class="cust-pick-item" onclick="app.selectCustForNewBill('${c.id}')">
        <div>
          <strong>${c.name}</strong> <small style="color: var(--primary-600);">(${c.id})</small><br>
          <small>📞 ${c.phone} • 📍 ${c.area} • 📦 ${c.package}</small>
        </div>
        <div style="text-align: right;">
          <span class="user-role-badge ${c.due > 0 ? 'role-admin' : 'role-staff'}">
            ${c.due > 0 ? `বকেয়া: ৳ ${c.due}` : 'পেইড ✓'}
          </span>
        </div>
      </div>
    `).join('');

    resultsBox.classList.add('open');
  },

  selectCustForNewBill(custId) {
    const cust = this.customers.find(c => c.id === custId);
    if (!cust) return;

    this.selectedCustForNewBill = cust;
    const searchInput = document.getElementById('new-bill-search-input');
    const resultsBox = document.getElementById('new-bill-search-results');
    const previewBox = document.getElementById('new-bill-selected-box');

    if (searchInput) searchInput.value = `${cust.name} (${cust.phone})`;
    if (resultsBox) resultsBox.classList.remove('open');

    if (previewBox) {
      document.getElementById('new-bill-cust-name').textContent = cust.name;
      document.getElementById('new-bill-cust-meta').textContent = `ID: ${cust.id} • 📞 ${cust.phone} • 📍 ${cust.area} • 📦 ${cust.package} (ফি: ৳ ${cust.fee})`;
      document.getElementById('new-bill-cust-due').textContent = `৳ ${Number(cust.due).toLocaleString('bn-BD')}`;
      previewBox.classList.add('active');
    }

    // ৩. প্যাকেজ ড্রপডাউনে গ্রাহকের বর্তমান প্যাকেজ স্বয়ংক্রিয় সিলেক্ট করা
    const pkgSelect = document.getElementById('new-bill-package-select');
    const amountInput = document.getElementById('new-bill-amount-input');

    if (pkgSelect) {
      let matched = false;
      if (pkgSelect.options) {
        for (let i = 0; i < pkgSelect.options.length; i++) {
          if (pkgSelect.options[i].value === cust.package) {
            pkgSelect.selectedIndex = i;
            matched = true;
            break;
          }
        }
      }
      if (!matched) {
        if (cust.package) {
          pkgSelect.value = cust.package;
        } else if (cust.fee) {
          const matchByPrice = this.packages.find(p => p.price === cust.fee);
          if (matchByPrice) {
            pkgSelect.value = matchByPrice.name;
          }
        }
      }
    }

    // টাকার ইনপুটে প্যাকেজের মূল্য স্বয়ংক্রিয় সেট করা
    if (amountInput) {
      amountInput.value = cust.fee || '';
      amountInput.placeholder = `টাকার পরিমাণ লিখুন (প্যাকেজ ফি: ৳ ${cust.fee})`;
    }
  },

  onNewBillPackageSelect(packageName) {
    const amountInput = document.getElementById('new-bill-amount-input');
    if (!amountInput) return;

    if (packageName === 'custom') {
      amountInput.value = '';
      amountInput.placeholder = 'কাস্টম টাকার পরিমাণ লিখুন';
      amountInput.focus();
      return;
    }

    if (!packageName) {
      amountInput.value = '';
      amountInput.placeholder = 'প্যাকেজ নির্বাচন করলে টাকার পরিমাণ স্বয়ংক্রিয় বসবে';
      return;
    }

    const pkg = this.packages.find(p => p.name === packageName);
    if (pkg) {
      amountInput.value = pkg.price;
    } else {
      const select = document.getElementById('new-bill-package-select');
      if (select && select.selectedIndex >= 0) {
        const price = select.options[select.selectedIndex].getAttribute('data-price');
        if (price) amountInput.value = price;
      }
    }
  },

  onNewBillTypeChange(type) {
    // বিলের ধরন অনুযায়ী প্রয়োজনে নোট আপডেট করা যেতে পারে
  },

  submitNewBill() {
    if (!this.hasPermission('bill_add')) {
      alert('নতুন বিল যোগ করার অনুমতি আপনার নেই!');
      return;
    }
    const cust = this.selectedCustForNewBill;
    if (!cust) {
      alert('দয়া করে সার্চ করে একজন গ্রাহক নির্বাচন করুন!');
      document.getElementById('new-bill-search-input').focus();
      return;
    }

    const billType = document.getElementById('new-bill-type-select').value;
    const selectedPkg = document.getElementById('new-bill-package-select') ? document.getElementById('new-bill-package-select').value : '';
    const amount = Number(document.getElementById('new-bill-amount-input').value) || 0;
    const billDate = document.getElementById('new-bill-date-input').value.trim();
    const note = document.getElementById('new-bill-note-input').value.trim();

    if (amount <= 0) {
      alert('দয়া করে প্যাকেজ নির্বাচন করুন অথবা সঠিক বিলের পরিমাণ লিখুন!');
      const pkgSelect = document.getElementById('new-bill-package-select');
      if (pkgSelect && !pkgSelect.value) {
        pkgSelect.focus();
      } else {
        document.getElementById('new-bill-amount-input').focus();
      }
      return;
    }

    // Add to customer's due
    cust.due = Number(cust.due) + amount;

    this.saveStorage();
    this.render();
    this.closeModal('new-bill-modal');

    const pkgText = selectedPkg && selectedPkg !== 'custom' ? ` (${selectedPkg})` : '';
    alert(`🎉 সফল! গ্রাহক "${cust.name}"-এর একাউন্টে ৳ ${amount.toLocaleString('bn-BD')}${pkgText} নতুন বিল যুক্ত হয়েছে। বর্তমান মোট বকেয়া: ৳ ${cust.due.toLocaleString('bn-BD')}`);
  },

  // =========================================================================
  // MODULE 3: COMPLAINT & SUPPORT TICKET MANAGEMENT
  // =========================================================================
  openComplaintModal() {
    if (!this.hasPermission('complaint_manage')) {
      alert('অভিযোগ টিকিট তৈরি করার অনুমতি আপনার নেই!');
      return;
    }
    // Populate customer dropdown
    const custSelect = document.getElementById('comp-cust-select');
    if (custSelect) {
      custSelect.innerHTML = this.customers.map(c => `
        <option value="${c.id}">${c.name} (${c.phone}) - ${c.area}</option>
      `).join('');
    }

    // Populate staff dropdown
    const staffSelect = document.getElementById('comp-staff-select');
    if (staffSelect) {
      staffSelect.innerHTML = this.staffUsers.filter(u => u.status === 'active').map(u => `
        <option value="${u.name}">${u.name} (${u.phone})</option>
      `).join('');
    }

    document.getElementById('comp-notes-input').value = '';
    this.openModal('complaint-modal');
  },

  saveComplaint() {
    if (!this.hasPermission('complaint_manage')) {
      alert('অভিযোগ সংরক্ষণ করার অনুমতি আপনার নেই!');
      return;
    }
    const custId = document.getElementById('comp-cust-select').value;
    const cust = this.customers.find(c => c.id === custId);
    if (!cust) return;

    const issue = document.getElementById('comp-issue-select').value;
    const priority = document.getElementById('comp-priority-select').value;
    const assignedStaff = document.getElementById('comp-staff-select').value;
    const notes = document.getElementById('comp-notes-input').value.trim();

    const ticketId = `TCK-${200 + this.complaints.length + 1}`;
    const timestamp = new Date().toLocaleString('bn-BD');

    const newTicket = {
      id: ticketId,
      date: timestamp,
      custId: cust.id,
      custName: cust.name,
      custPhone: cust.phone,
      area: cust.area,
      issue,
      priority,
      status: 'Pending',
      assignedStaff,
      notes,
      solutionNote: ''
    };

    this.complaints.unshift(newTicket);
    this.saveStorage();
    this.render();
    this.renderComplaints();
    this.closeModal('complaint-modal');
    alert(`অভিযোগ টিকিট সফলভাবে তৈরি হয়েছে! টিকিট আইডি: ${ticketId}`);
  },

  filterComplaints(status, btnEl) {
    this.complaintFilter = status;
    if (btnEl) {
      document.querySelectorAll('#tab-complaints-view .chip-btn').forEach(b => b.classList.remove('active'));
      btnEl.classList.add('active');
    }
    this.renderComplaints();
  },

  openResolveModal(ticketId) {
    const ticket = this.complaints.find(t => t.id === ticketId);
    if (!ticket) return;

    document.getElementById('resolve-ticket-id').value = ticket.id;
    document.getElementById('resolve-modal-cust').textContent = `${ticket.custName} (${ticket.area}) • ID: ${ticket.custId}`;
    document.getElementById('resolve-modal-issue').textContent = `সমস্যা: ${ticket.issue}`;
    document.getElementById('resolve-status-select').value = ticket.status === 'Pending' ? 'In Progress' : 'Resolved';
    document.getElementById('resolve-note-input').value = ticket.solutionNote || '';

    this.openModal('resolve-modal');
  },

  saveComplaintResolution() {
    const ticketId = document.getElementById('resolve-ticket-id').value;
    const ticket = this.complaints.find(t => t.id === ticketId);
    if (!ticket) return;

    const status = document.getElementById('resolve-status-select').value;
    const note = document.getElementById('resolve-note-input').value.trim();

    ticket.status = status;
    ticket.solutionNote = note;

    this.saveStorage();
    this.render();
    this.renderComplaints();
    this.closeModal('resolve-modal');
    alert(`টিকিট ${ticket.id} এর স্ট্যাটাস '${status}' হিসেবে আপডেট করা হয়েছে!`);
  },

  renderComplaints() {
    const tableBody = document.getElementById('complaints-table-body');
    if (!tableBody) return;

    let filtered = this.complaints;
    if (this.complaintFilter !== 'all') {
      filtered = this.complaints.filter(c => c.status === this.complaintFilter);
    }

    tableBody.innerHTML = filtered.map(t => {
      let badgeClass = 'badge-pending';
      if (t.status === 'In Progress') badgeClass = 'badge-progress';
      if (t.status === 'Resolved') badgeClass = 'badge-resolved';

      let priorityClass = 'priority-normal';
      if (t.priority === 'Urgent') priorityClass = 'priority-urgent';
      if (t.priority === 'High') priorityClass = 'priority-high';

      return `
        <tr>
          <td><strong>${t.id}</strong></td>
          <td><small>${t.date}</small></td>
          <td>
            <strong>${t.custName}</strong><br>
            <small style="color: var(--text-muted);">${t.area} • 📞 <a href="tel:${t.custPhone}">${t.custPhone}</a></small>
          </td>
          <td>
            <span style="font-weight: 600; color: var(--primary-900);">${t.issue}</span>
            ${t.notes ? `<br><small style="color: var(--text-muted);">${t.notes}</small>` : ''}
          </td>
          <td><span class="status-badge ${priorityClass}">${t.priority}</span></td>
          <td>${t.assignedStaff}</td>
          <td><span class="status-badge ${badgeClass}">${t.status}</span></td>
          <td><small>${t.solutionNote || '-'}</small></td>
          <td>
            <div style="display: flex; gap: 0.35rem;">
              <a href="tel:${t.custPhone}" class="btn btn-call btn-sm" title="কল দিন">📞</a>
              <button class="btn btn-primary btn-sm" onclick="app.openResolveModal('${t.id}')">আপডেট</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  },

  // =========================================================================
  // MODULE 4: STAFF & USER MANAGEMENT (ADMIN ONLY)
  // =========================================================================
  openStaffModal(editId = null) {
    if (this.currentUser.role !== 'admin') {
      alert('শুধুমাত্র এডমিন স্টাফ তৈরি ও সংশোধন করতে পারবেন!');
      return;
    }

    const isEdit = Boolean(editId);
    document.getElementById('staff-modal-title').textContent = isEdit ? '✏️ স্টাফের তথ্য ও পারমিশন সংশোধন' : '➕ নতুন স্টাফ যোগ করুন';
    document.getElementById('staff-edit-id').value = isEdit ? editId : '';

    const staff = isEdit ? this.staffUsers.find(s => s.id === editId) : null;

    document.getElementById('staff-name-input').value = staff ? staff.name : '';
    document.getElementById('staff-username-input').value = staff ? staff.username : '';
    document.getElementById('staff-phone-input').value = staff ? staff.phone : '';
    document.getElementById('staff-password-input').value = staff ? (staff.password || '123') : '123';
    document.getElementById('staff-role-select').value = staff ? staff.role : 'staff';
    document.getElementById('staff-salary-input').value = staff ? staff.salary : 10000;

    // Permissions Checklist
    const permKeys = ['bill_pay', 'bill_add', 'cust_renew', 'cust_manage', 'expense_manage', 'complaint_manage', 'package_manage', 'reports_view'];
    const currentPerms = staff ? (staff.permissions || []) : ['bill_pay', 'bill_add', 'cust_renew', 'complaint_manage'];
    const isAdminRole = (staff ? staff.role : 'staff') === 'admin';

    permKeys.forEach(k => {
      const checkbox = document.getElementById(`perm-${k.replace('_', '-')}`);
      if (checkbox) {
        checkbox.checked = isAdminRole || currentPerms.includes('all') || currentPerms.includes(k);
        checkbox.disabled = isAdminRole; // Disabled if admin because admin has all permissions
      }
    });

    this.openModal('staff-user-modal');
  },

  onStaffRoleChange(role) {
    const isAdmin = role === 'admin';
    const permKeys = ['bill_pay', 'bill_add', 'cust_renew', 'cust_manage', 'expense_manage', 'complaint_manage', 'package_manage', 'reports_view'];
    permKeys.forEach(k => {
      const checkbox = document.getElementById(`perm-${k.replace('_', '-')}`);
      if (checkbox) {
        if (isAdmin) {
          checkbox.checked = true;
          checkbox.disabled = true;
        } else {
          checkbox.disabled = false;
        }
      }
    });
  },

  saveStaff() {
    if (this.currentUser.role !== 'admin') {
      alert('শুধুমাত্র এডমিন স্টাফ সংরক্ষণ করতে পারবেন!');
      return;
    }

    const editId = document.getElementById('staff-edit-id').value;
    const name = document.getElementById('staff-name-input').value.trim();
    const username = document.getElementById('staff-username-input').value.trim().toLowerCase();
    const phone = document.getElementById('staff-phone-input').value.trim();
    const password = document.getElementById('staff-password-input').value.trim();
    const role = document.getElementById('staff-role-select').value;
    const salary = Number(document.getElementById('staff-salary-input').value) || 0;

    if (!name || !username || !phone || !password) {
      alert('দয়া করে স্টাফের নাম, ইউজারনেম, মোবাইল নম্বর এবং পাসওয়ার্ড পূরণ করুন!');
      return;
    }

    // Collect permissions
    let permissions = [];
    if (role === 'admin') {
      permissions = ['all'];
    } else {
      const permKeys = ['bill_pay', 'bill_add', 'cust_renew', 'cust_manage', 'expense_manage', 'complaint_manage', 'package_manage', 'reports_view'];
      permKeys.forEach(k => {
        const checkbox = document.getElementById(`perm-${k.replace('_', '-')}`);
        if (checkbox && checkbox.checked) {
          permissions.push(k);
        }
      });
      if (permissions.length === 0) {
        if (!confirm('আপনি এই স্টাফকে কোনো পারমিশন দেননি! সে শুধু গ্রাহক তালিকা দেখতে পারবে। আপনি কি নিশ্চিত?')) {
          return;
        }
      }
    }

    if (editId) {
      const staff = this.staffUsers.find(s => s.id === editId);
      if (staff) {
        // If username was changed, ensure uniqueness
        if (staff.username !== username && this.staffUsers.some(s => s.id !== editId && s.username === username)) {
          alert('এই ইউজারনেমটি ইতিমধ্যে ব্যবহৃত হয়েছে! অন্য একটি ইউজারনেম দিন।');
          return;
        }
        staff.name = name;
        staff.username = username;
        staff.phone = phone;
        staff.password = password;
        staff.role = role;
        staff.permissions = permissions;
        staff.salary = salary;
        if (this.currentUser && this.currentUser.id === editId) {
          this.currentUser = staff;
        }
      }
    } else {
      // Check username uniqueness
      if (this.staffUsers.some(s => s.username === username)) {
        alert('এই ইউজারনেমটি ইতিমধ্যে অন্য একজন ব্যবহার করছেন! অন্য একটি ইউজারনেম দিন।');
        return;
      }

      const nextId = `ST-${100 + this.staffUsers.length + 1}`;
      const newStaff = {
        id: nextId,
        name,
        username,
        password,
        phone,
        role,
        permissions,
        salary,
        status: 'active',
        joined: new Date().toISOString().slice(0, 10)
      };
      this.staffUsers.push(newStaff);
    }

    this.saveStorage();
    this.updateUserUI();
    this.renderStaffUsers();
    this.closeModal('staff-user-modal');
    alert('স্টাফের তথ্য ও পারমিশন সফলভাবে সংরক্ষণ করা হয়েছে!');
  },

  toggleStaffStatus(staffId) {
    const staff = this.staffUsers.find(s => s.id === staffId);
    if (!staff) return;

    if (staff.role === 'admin' && staff.username === 'admin') {
      alert('মূল এডমিন একাউন্ট নিষ্ক্রিয় করা যাবে না!');
      return;
    }

    staff.status = staff.status === 'active' ? 'inactive' : 'active';
    this.saveStorage();
    this.renderStaffUsers();
    this.updateUserUI();
    alert(`স্টাফ '${staff.name}' এর স্ট্যাটাস '${staff.status}' করা হয়েছে।`);
  },

  renderStaffUsers() {
    const tableBody = document.getElementById('staff-users-table-body');
    if (!tableBody) return;

    const permLabels = {
      'bill_pay': 'বিল আদায়',
      'bill_add': 'নতুন বিল/চার্জ',
      'cust_renew': 'লাইন রিনিউ',
      'cust_manage': 'গ্রাহক যোগ',
      'expense_manage': 'খরচ এন্ট্রি',
      'complaint_manage': 'কমপ্লেইন',
      'package_manage': 'প্যাকেজ/জোন',
      'reports_view': 'রিপোর্ট'
    };

    tableBody.innerHTML = this.staffUsers.map(s => {
      let permDisplay = '';
      if (s.role === 'admin') {
        permDisplay = '<span class="status-badge status-active" style="background: #ede9fe; color: #6d28d9; border: 1px solid #c4b5fd;">👑 সম্পূর্ণ এক্সেস</span>';
      } else {
        const perms = s.permissions || [];
        if (perms.length === 0) {
          permDisplay = '<span style="color: var(--text-muted); font-size: 0.75rem;">কোনো পারমিশন নেই</span>';
        } else {
          permDisplay = `<div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 250px;">` +
            perms.map(p => `<span class="chip-count" style="font-size: 0.72rem; padding: 2px 6px; background: #e0f2fe; color: #0369a1; border-radius: 4px;">${permLabels[p] || p}</span>`).join('') +
            `</div>`;
        }
      }

      return `
        <tr>
          <td><code>${s.id}</code></td>
          <td><strong>${s.name}</strong></td>
          <td>
            <code>${s.username}</code><br>
            <small style="color: var(--primary-700); font-weight: 600;">পিন: ${s.password || '123'}</small>
          </td>
          <td><a href="tel:${s.phone}">${s.phone}</a></td>
          <td><span class="user-role-badge ${s.role === 'admin' ? 'role-admin' : 'role-staff'}">${s.role.toUpperCase()}</span></td>
          <td>${permDisplay}</td>
          <td><strong>৳ ${Number(s.salary).toLocaleString('bn-BD')}</strong></td>
          <td>
            <span class="status-badge ${s.status === 'active' ? 'status-active' : 'status-suspended'}">
              ${s.status === 'active' ? 'Active' : 'Inactive'}
            </span>
          </td>
          <td>
            <div style="display: flex; gap: 0.35rem;">
              <button class="btn btn-outline btn-sm" onclick="app.openStaffModal('${s.id}')" title="তথ্য ও পারমিশন এডিট">
                ✏️ এডিট
              </button>
              <button class="btn btn-outline btn-sm" onclick="app.toggleStaffStatus('${s.id}')" style="color: ${s.status === 'active' ? 'var(--danger-600)' : 'var(--success-600)'};">
                ${s.status === 'active' ? '🚫 বন্ধ' : '✓ চালু'}
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  },

  // =========================================================================
  // MODULE: PACKAGE & AREA/ZONE MANAGEMENT (ADMIN ONLY)
  // =========================================================================
  openPackageModal() {
    if (!this.hasPermission('package_manage')) {
      alert('প্যাকেজ পরিবর্তন বা যোগ করার অনুমতি আপনার নেই!');
      return;
    }
    document.getElementById('pkg-name-input').value = '';
    document.getElementById('pkg-speed-input').value = '';
    document.getElementById('pkg-price-input').value = '';
    this.openModal('new-package-modal');
  },

  savePackage() {
    if (!this.hasPermission('package_manage')) {
      alert('প্যাকেজ সংরক্ষণ করার অনুমতি আপনার নেই!');
      return;
    }

    const name = document.getElementById('pkg-name-input').value.trim();
    const speed = document.getElementById('pkg-speed-input').value.trim();
    const price = Number(document.getElementById('pkg-price-input').value) || 0;

    if (!name || !speed || price <= 0) {
      alert('দয়া করে প্যাকেজের নাম, স্পিড এবং সঠিক মূল্য লিখুন!');
      return;
    }

    const nextId = `PKG-${String(this.packages.length + 1).padStart(2, '0')}`;
    this.packages.push({ id: nextId, name, speed, price });
    this.saveStorage();
    this.renderPackageDropdowns();
    this.renderPackagesAndAreas();
    this.closeModal('new-package-modal');
    alert(`প্যাকেজ '${name}' সফলভাবে যোগ করা হয়েছে!`);
  },

  deletePackage(pkgId) {
    if (!this.hasPermission('package_manage')) return;
    if (confirm('আপনি কি নিশ্চিত এই প্যাকেজটি মুছে ফেলতে চান?')) {
      this.packages = this.packages.filter(p => p.id !== pkgId);
      this.saveStorage();
      this.renderPackageDropdowns();
      this.renderPackagesAndAreas();
    }
  },

  renderPackageDropdowns() {
    const select = document.getElementById('cust-package');
    if (select) {
      select.innerHTML = this.packages.map(p => `
        <option value="${p.name}" data-price="${p.price}">${p.name} - ৳ ${p.price}/মাস</option>
      `).join('');
    }

    const newBillPkgSelect = document.getElementById('new-bill-package-select');
    if (newBillPkgSelect) {
      const currentVal = newBillPkgSelect.value;
      newBillPkgSelect.innerHTML = `<option value="">-- প্যাকেজ নির্বাচন করুন --</option>` +
        this.packages.map(p => `
          <option value="${p.name}" data-price="${p.price}" ${p.name === currentVal ? 'selected' : ''}>
            📦 ${p.name} (${p.speed}) - ৳ ${p.price}/মাস
          </option>
        `).join('') +
        `<option value="custom" ${currentVal === 'custom' ? 'selected' : ''}>✏️ অন্যান্য / কাস্টম পরিমাণ</option>`;
    }
  },

  openAreaModal() {
    if (!this.hasPermission('package_manage')) {
      alert('নতুন এলাকা/জোন যুক্ত করার অনুমতি আপনার নেই!');
      return;
    }
    document.getElementById('area-name-input').value = '';
    this.openModal('new-area-modal');
  },

  saveArea() {
    if (!this.hasPermission('package_manage')) {
      alert('নতুন এলাকা সংরক্ষণ করার অনুমতি আপনার নেই!');
      return;
    }

    const name = document.getElementById('area-name-input').value.trim();
    if (!name) {
      alert('দয়া করে এলাকা বা জোনের নাম লিখুন!');
      return;
    }
    if (this.areas.includes(name)) {
      alert('এই এলাকাটি ইতিমধ্যে তালিকায় বিদ্যমান রয়েছে!');
      return;
    }

    this.areas.push(name);
    this.saveStorage();
    this.renderAreaDropdowns();
    this.renderPackagesAndAreas();
    this.closeModal('new-area-modal');
    alert(`এলাকা '${name}' সফলভাবে যুক্ত হয়েছে!`);
  },

  deleteArea(areaName) {
    if (this.currentUser.role !== 'admin') return;
    if (confirm(`আপনি কি নিশ্চিত '${areaName}' এলাকাটি মুছে ফেলতে চান?`)) {
      this.areas = this.areas.filter(a => a !== areaName);
      this.saveStorage();
      this.renderAreaDropdowns();
      this.renderPackagesAndAreas();
    }
  },

  renderAreaDropdowns() {
    const filterSelect = document.getElementById('area-filter-select');
    if (filterSelect) {
      const currentVal = filterSelect.value;
      filterSelect.innerHTML = `<option value="">📍 সকল এলাকা</option>` + this.areas.map(a => `
        <option value="${a}" ${a === currentVal ? 'selected' : ''}>${a}</option>
      `).join('');
    }
  },

  renderPackagesAndAreas() {
    const pkgTable = document.getElementById('packages-table-body');
    if (pkgTable) {
      pkgTable.innerHTML = this.packages.map(p => `
        <tr>
          <td><strong>${p.name}</strong></td>
          <td><span class="user-role-badge role-staff">${p.speed}</span></td>
          <td><strong style="color: var(--primary-700);">৳ ${p.price}</strong></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="app.deletePackage('${p.id}')" style="color: var(--danger-500);">মুছুন</button>
          </td>
        </tr>
      `).join('');
    }

    const areaTable = document.getElementById('areas-table-body');
    if (areaTable) {
      areaTable.innerHTML = this.areas.map(a => {
        const count = this.customers.filter(c => c.area === a).length;
        return `
          <tr>
            <td><strong>📍 ${a}</strong></td>
            <td><span class="chip-count">${count} জন গ্রাহক</span></td>
            <td>
              <button class="btn btn-outline btn-sm" onclick="app.deleteArea('${a}')" style="color: var(--danger-500);">মুছুন</button>
            </td>
          </tr>
        `;
      }).join('');
    }
  },

  renderStaffReport() {
    const tableBody = document.getElementById('staff-table-body');
    if (!tableBody) return;

    const staffStats = {};
    this.staffUsers.forEach(u => {
      staffStats[u.name] = { username: u.username, phone: u.phone, todayAmount: 0, totalAmount: 0, count: 0 };
    });

    const todayStr = new Date().toISOString().slice(0, 10);
    this.payments.forEach(p => {
      if (!staffStats[p.staff]) {
        staffStats[p.staff] = { username: 'staff', phone: '-', todayAmount: 0, totalAmount: 0, count: 0 };
      }
      staffStats[p.staff].totalAmount += Number(p.amount) || 0;
      staffStats[p.staff].count++;
      if (p.date.includes(todayStr) || p.date.includes('2026-09-08')) {
        staffStats[p.staff].todayAmount += Number(p.amount) || 0;
      }
    });

    tableBody.innerHTML = Object.entries(staffStats).map(([name, data]) => `
      <tr>
        <td><strong>${name}</strong></td>
        <td><code>${data.username}</code></td>
        <td>${data.phone}</td>
        <td><strong style="color: var(--success-600);">৳ ${data.todayAmount.toLocaleString('bn-BD')}</strong></td>
        <td>৳ ${data.totalAmount.toLocaleString('bn-BD')}</td>
        <td><span class="chip-count">${data.count} টি</span></td>
      </tr>
    `).join('');
  },

  renderPaymentHistory() {
    const tableBody = document.getElementById('payment-history-body');
    if (!tableBody) return;

    tableBody.innerHTML = this.payments.slice().reverse().map(p => `
      <tr>
        <td><code>${p.receiptNo}</code></td>
        <td>${p.date}</td>
        <td>${p.custName}</td>
        <td><strong style="color: var(--success-600);">৳ ${Number(p.amount).toLocaleString('bn-BD')}</strong></td>
        <td><span class="user-role-badge role-staff">${p.method}</span></td>
        <td>${p.staff}</td>
        <td>
          <span class="status-badge ${p.synced ? 'status-active' : 'status-suspended'}">
            ${p.synced ? 'Synced ✓' : 'Local Queue ⚠️'}
          </span>
        </td>
        <td>
          <button class="btn btn-outline btn-sm" onclick="app.viewReceipt('${p.receiptNo}')">রিসিট</button>
        </td>
      </tr>
    `).join('');
  },

  // =========================================================================
  // MODULE: CUSTOMER LINE RENEWAL & GRACE VALIDITY EXTENSION
  // =========================================================================
  selectedCustForRenew: null,
  currentRenewDays: 1,

  openRenewModal(custId = null) {
    if (!this.hasPermission('cust_renew') && !this.hasPermission('bill_pay')) {
      alert('গ্রাহকের লাইন রিনিউ করার অনুমতি আপনার নেই!');
      return;
    }

    const searchInput = document.getElementById('renew-search-input');
    const resultsBox = document.getElementById('renew-search-results');
    const previewBox = document.getElementById('renew-selected-box');

    if (resultsBox) {
      resultsBox.innerHTML = '';
      resultsBox.classList.remove('open');
    }

    // Set default start date to today
    const startDateInput = document.getElementById('renew-start-date-input');
    if (startDateInput) startDateInput.value = this.getTodayStr();

    if (custId) {
      const cust = this.customers.find(c => c.id === custId);
      if (!cust) return;
      this.selectCustForRenew(cust.id);
    } else {
      this.selectedCustForRenew = null;
      if (searchInput) {
        searchInput.value = '';
        setTimeout(() => searchInput.focus(), 150);
      }
      if (previewBox) previewBox.classList.remove('active');
      const calcBox = document.getElementById('renew-pkg-calc-box');
      if (calcBox) calcBox.style.display = 'none';
      this.setRenewDays(1);
    }

    // Default to package bill adjustment
    const billTypeSelect = document.getElementById('renew-bill-type');
    if (billTypeSelect) billTypeSelect.value = 'package';
    this.onRenewBillTypeChange('package');

    const noteInput = document.getElementById('renew-note-input');
    if (noteInput) noteInput.value = '';

    this.openModal('renew-modal');
  },

  handleRenewCustSearch(query) {
    const resultsBox = document.getElementById('renew-search-results');
    if (!resultsBox) return;

    const q = (query || '').trim().toLowerCase();
    if (!q) {
      resultsBox.innerHTML = '';
      resultsBox.classList.remove('open');
      return;
    }

    const matches = this.customers.filter(c => {
      const matchName = (c.name || '').toLowerCase().includes(q);
      const matchPhone = (c.phone || '').includes(q);
      const matchId = (c.id || '').toLowerCase().includes(q);
      const matchPppoe = (c.pppoe || '').toLowerCase().includes(q);
      return matchName || matchPhone || matchId || matchPppoe;
    }).slice(0, 6);

    if (matches.length === 0) {
      resultsBox.innerHTML = `<div class="search-result-item" style="color: var(--text-muted); cursor: default;">কোনো গ্রাহক পাওয়া যায়নি</div>`;
      resultsBox.classList.add('open');
      return;
    }

    resultsBox.innerHTML = matches.map(c => {
      const exp = this.getExpiryStatus(c.expireDate);
      return `
        <div class="search-result-item" onclick="app.selectCustForRenew('${c.id}')">
          <div class="result-name">
            <strong>${c.name}</strong> 
            <span class="${exp.badgeClass}" style="margin-left: 6px; font-size: 0.72rem;">${exp.label}</span>
          </div>
          <div class="result-meta">ID: ${c.id} • 📞 ${c.phone} • 📍 ${c.area} • 📦 ${c.package} • বকেয়া: ৳ ${c.due}</div>
        </div>
      `;
    }).join('');
    resultsBox.classList.add('open');
  },

  selectCustForRenew(custId) {
    const cust = this.customers.find(c => c.id === custId);
    if (!cust) return;
    this.selectedCustForRenew = cust;

    const searchInput = document.getElementById('renew-search-input');
    const resultsBox = document.getElementById('renew-search-results');
    const previewBox = document.getElementById('renew-selected-box');

    if (searchInput) searchInput.value = `${cust.name} (${cust.phone})`;
    if (resultsBox) resultsBox.classList.remove('open');

    const nameEl = document.getElementById('renew-cust-name');
    const metaEl = document.getElementById('renew-cust-meta');
    const dueEl = document.getElementById('renew-cust-due');
    const badgeEl = document.getElementById('renew-cust-status-badge');
    const expireInfoEl = document.getElementById('renew-cust-expire-info');

    if (nameEl) nameEl.textContent = cust.name;
    if (metaEl) metaEl.textContent = `ID: ${cust.id} • 📞 ${cust.phone} • 📍 ${cust.area} • 📦 ${cust.package} (৳ ${cust.fee}/মাস)`;
    if (dueEl) dueEl.textContent = `৳ ${Number(cust.due).toLocaleString('bn-BD')}`;

    if (badgeEl) {
      badgeEl.textContent = cust.status === 'active' ? 'Active' : 'Suspended';
      badgeEl.className = `status-badge ${cust.status === 'active' ? 'status-active' : 'status-suspended'}`;
    }

    const expInfo = this.getExpiryStatus(cust.expireDate);
    if (expireInfoEl) {
      expireInfoEl.innerHTML = `📅 বর্তমান মেয়াদ: <strong>${cust.expireDate || 'নির্ধারিত নেই'}</strong> (${expInfo.label})`;
      expireInfoEl.style.color = expInfo.isExpired ? 'var(--danger-600)' : (expInfo.daysDiff <= 3 ? 'var(--warning-700)' : 'var(--primary-700)');
    }

    if (previewBox) previewBox.classList.add('active');

    // Default start date for customer:
    // If expired or expires today, start from today.
    // If customer has future validity, start from customer's current expireDate.
    const todayStr = this.getTodayStr();
    const startDateInput = document.getElementById('renew-start-date-input');
    const custStartDate = (cust.expireDate && cust.expireDate > todayStr) ? cust.expireDate : todayStr;
    if (startDateInput) startDateInput.value = custStartDate;

    // Recalculate days based on currently selected duration (or default to 1 day)
    this.setRenewDays(this.currentRenewDays || 1);
  },

  updateRenewPackageCalculation(days, startDateStr = '', endDateStr = '') {
    const cust = this.selectedCustForRenew;
    const calcBox = document.getElementById('renew-pkg-calc-box');
    const pkgBadge = document.getElementById('renew-calc-pkg-badge');
    const dailyRateEl = document.getElementById('renew-calc-daily-rate');
    const formulaEl = document.getElementById('renew-calc-formula-text');
    const totalBadge = document.getElementById('renew-calc-total-badge');
    const billAmountInput = document.getElementById('renew-bill-amount');
    const billTypeSelect = document.getElementById('renew-bill-type');
    const daysHint = document.getElementById('renew-days-hint');

    const numDays = Math.max(1, Number(days) || 1);
    this.currentRenewDays = numDays;

    if (!cust) {
      if (calcBox) calcBox.style.display = 'none';
      return 0;
    }

    if (calcBox) calcBox.style.display = 'block';

    const monthlyFee = Number(cust.fee) || 0;
    const dailyRate = Math.round(monthlyFee / 30);
    const packageRenewFee = Math.round((monthlyFee / 30) * numDays);

    if (pkgBadge) pkgBadge.textContent = `${cust.package || 'প্যাকেজ'} (৳ ${monthlyFee.toLocaleString('bn-BD')}/মাস)`;
    if (dailyRateEl) dailyRateEl.textContent = `দৈনিক রেট: ৳ ${dailyRate.toLocaleString('bn-BD')}`;
    if (formulaEl) formulaEl.textContent = `মাসিক ফি ৳ ${monthlyFee.toLocaleString('bn-BD')} ÷ ৩০ দিন × ${numDays.toLocaleString('bn-BD')} দিন =`;
    if (totalBadge) totalBadge.textContent = `৳ ${packageRenewFee.toLocaleString('bn-BD')}`;

    const sDate = startDateStr || (document.getElementById('renew-start-date-input') ? document.getElementById('renew-start-date-input').value : '');
    const eDate = endDateStr || (document.getElementById('renew-new-date-input') ? document.getElementById('renew-new-date-input').value : '');
    if (daysHint) {
      daysHint.textContent = `* মোট মেয়াদ: ${numDays} দিন (${sDate} থেকে ${eDate})`;
    }

    const currentBillType = billTypeSelect ? billTypeSelect.value : 'package';
    if (currentBillType === 'package') {
      if (billAmountInput) {
        billAmountInput.value = packageRenewFee;
        billAmountInput.readOnly = true;
        billAmountInput.style.backgroundColor = '#f1f5f9';
      }
    } else if (currentBillType === 'free') {
      if (billAmountInput) {
        billAmountInput.value = 0;
        billAmountInput.readOnly = true;
        billAmountInput.style.backgroundColor = '#f1f5f9';
      }
    } else if (currentBillType === 'custom') {
      if (billAmountInput) {
        billAmountInput.readOnly = false;
        billAmountInput.style.backgroundColor = '#ffffff';
      }
    }

    return packageRenewFee;
  },

  setRenewDays(days, btnEl) {
    const numDays = Math.max(1, Number(days) || 1);
    this.currentRenewDays = numDays;

    if (btnEl) {
      if (typeof document !== 'undefined' && document.querySelectorAll) {
        document.querySelectorAll('.renew-chip').forEach(b => b.classList.remove('active'));
      }
      btnEl.classList.add('active');
    } else if (typeof document !== 'undefined' && document.querySelectorAll) {
      document.querySelectorAll('.renew-chip').forEach(b => {
        if (b.textContent.includes(`+ ${numDays} দিন`) || (numDays === 30 && b.textContent.includes('৩০ দিন'))) {
          b.classList.add('active');
        } else {
          b.classList.remove('active');
        }
      });
    }

    const cust = this.selectedCustForRenew;
    const todayStr = this.getTodayStr();

    // Determine start date:
    const startDateInput = document.getElementById('renew-start-date-input');
    let startDateStr = startDateInput && startDateInput.value ? startDateInput.value : '';
    if (!startDateStr) {
      if (cust && cust.expireDate && cust.expireDate > todayStr) {
        startDateStr = cust.expireDate;
      } else {
        startDateStr = todayStr;
      }
      if (startDateInput) startDateInput.value = startDateStr;
    }

    const baseDate = new Date(startDateStr + 'T00:00:00');
    baseDate.setDate(baseDate.getDate() + numDays);

    const newYear = baseDate.getFullYear();
    const newMonth = String(baseDate.getMonth() + 1).padStart(2, '0');
    const newDay = String(baseDate.getDate()).padStart(2, '0');
    const newDateStr = `${newYear}-${newMonth}-${newDay}`;

    const dateInput = document.getElementById('renew-new-date-input');
    if (dateInput) dateInput.value = newDateStr;

    this.updateRenewPackageCalculation(numDays, startDateStr, newDateStr);
  },

  onRenewStartDateChange(newStartDate) {
    if (!newStartDate) return;
    const days = this.currentRenewDays || 1;

    const baseDate = new Date(newStartDate + 'T00:00:00');
    baseDate.setDate(baseDate.getDate() + days);

    const newYear = baseDate.getFullYear();
    const newMonth = String(baseDate.getMonth() + 1).padStart(2, '0');
    const newDay = String(baseDate.getDate()).padStart(2, '0');
    const newDateStr = `${newYear}-${newMonth}-${newDay}`;

    const endDateInput = document.getElementById('renew-new-date-input');
    if (endDateInput) endDateInput.value = newDateStr;

    this.updateRenewPackageCalculation(days, newStartDate, newDateStr);
  },

  onRenewEndDateChange(newEndDate) {
    if (!newEndDate) return;
    const startDateInput = document.getElementById('renew-start-date-input');
    const startDateStr = startDateInput && startDateInput.value ? startDateInput.value : this.getTodayStr();

    const start = new Date(startDateStr + 'T00:00:00');
    const end = new Date(newEndDate + 'T00:00:00');
    const diffMs = end - start;
    const diffDays = Math.max(1, Math.round(diffMs / (1000 * 60 * 60 * 24)));
    this.currentRenewDays = diffDays;

    if (typeof document !== 'undefined' && document.querySelectorAll) {
      document.querySelectorAll('.renew-chip').forEach(b => {
        if (b.textContent.includes(`+ ${diffDays} দিন`) || (diffDays === 30 && b.textContent.includes('৩০ দিন'))) {
          b.classList.add('active');
        } else {
          b.classList.remove('active');
        }
      });
    }

    this.updateRenewPackageCalculation(diffDays, startDateStr, newEndDate);
  },

  onRenewBillTypeChange(type) {
    const amountGroup = document.getElementById('renew-bill-amount-group');
    const amountInput = document.getElementById('renew-bill-amount');
    const hint = document.getElementById('renew-bill-amount-hint');

    if (type === 'package') {
      if (amountGroup) amountGroup.style.display = 'block';
      if (hint) hint.textContent = '* গ্রাহকের মাসিক প্যাকেজ ফি অনুযায়ী দিন অনুপাতে স্বয়ংক্রিয় হিসাব করা হয়েছে।';
      const days = this.currentRenewDays || 1;
      const startDate = document.getElementById('renew-start-date-input') ? document.getElementById('renew-start-date-input').value : '';
      const endDate = document.getElementById('renew-new-date-input') ? document.getElementById('renew-new-date-input').value : '';
      this.updateRenewPackageCalculation(days, startDate, endDate);
    } else if (type === 'free') {
      if (amountGroup) amountGroup.style.display = 'none';
      if (amountInput) {
        amountInput.value = 0;
        amountInput.readOnly = true;
      }
    } else if (type === 'custom') {
      if (amountGroup) amountGroup.style.display = 'block';
      if (hint) hint.textContent = '* আপনি ইচ্ছামতো যেকোনো টাকার পরিমাণ বকেয়ায় যোগ করতে পারেন।';
      if (amountInput) {
        amountInput.readOnly = false;
        amountInput.style.backgroundColor = '#ffffff';
        amountInput.focus();
      }
    }
  },

  submitRenew() {
    if (!this.hasPermission('cust_renew') && !this.hasPermission('bill_pay')) {
      alert('গ্রাহকের লাইন রিনিউ করার অনুমতি আপনার নেই!');
      return;
    }
    const cust = this.selectedCustForRenew;
    if (!cust) {
      alert('দয়া করে সার্চ করে একজন গ্রাহক নির্বাচন করুন!');
      const searchInput = document.getElementById('renew-search-input');
      if (searchInput) searchInput.focus();
      return;
    }

    const startDate = document.getElementById('renew-start-date-input') ? document.getElementById('renew-start-date-input').value : '';
    const newExpireDate = document.getElementById('renew-new-date-input') ? document.getElementById('renew-new-date-input').value : '';
    if (!newExpireDate) {
      alert('অনুগ্রহ করে মেয়াদের শেষ তারিখ নির্ধারণ করুন!');
      return;
    }

    const billType = document.getElementById('renew-bill-type') ? document.getElementById('renew-bill-type').value : 'package';
    const billAmount = Number(document.getElementById('renew-bill-amount') ? document.getElementById('renew-bill-amount').value : 0) || 0;
    const note = document.getElementById('renew-note-input') ? document.getElementById('renew-note-input').value.trim() : '';

    // Calculate days between start and end
    const sDate = startDate ? new Date(startDate + 'T00:00:00') : new Date();
    const eDate = new Date(newExpireDate + 'T00:00:00');
    const days = Math.max(1, Math.round((eDate - sDate) / (1000 * 60 * 60 * 24)));

    cust.expireDate = newExpireDate;
    cust.status = 'active'; // Always activate line on renewal!

    let addedBillText = '';
    if ((billType === 'package' || billType === 'custom') && billAmount > 0) {
      cust.due = Number(cust.due) + billAmount;
      addedBillText = ` এবং প্যাকেজ চার্জ ৳ ${billAmount.toLocaleString('bn-BD')} বকেয়ায় যোগ হয়েছে`;
    }

    this.saveStorage();
    this.render();
    this.closeModal('renew-modal');

    const expInfo = this.getExpiryStatus(newExpireDate);
    const noteText = note ? ` (নোট: ${note})` : '';
    alert(`🎉 সফল! গ্রাহক "${cust.name}" এর লাইন ${startDate ? startDate + ' থেকে ' : ''}${newExpireDate} (${days} দিন, ${expInfo.label}) পর্যন্ত রিনিউ করা হয়েছে${addedBillText}।${noteText}`);
  },

  // =========================================================================
  // EXISTING CORE BILLING & POS METHODS
  // =========================================================================
  openPayModal(custId) {
    if (!this.hasPermission('bill_pay')) {
      alert('বিল আদায় করার অনুমতি আপনার নেই!');
      return;
    }
    const cust = this.customers.find(c => c.id === custId);
    if (!cust) return;
    this.selectedCustForPay = cust;

    document.getElementById('pay-modal-cust-name').textContent = cust.name;
    document.getElementById('pay-modal-cust-id').textContent = `ID: ${cust.id} • ${cust.phone}`;
    document.getElementById('pay-modal-total-due').textContent = `৳ ${Number(cust.due).toLocaleString('bn-BD')}`;

    const defaultAmount = cust.due > 0 ? cust.due : cust.fee;
    document.getElementById('pay-amount-input').value = defaultAmount;
    document.getElementById('pay-discount-input').value = 0;
    document.getElementById('pay-note-input').value = '';

    this.openModal('pay-modal');
  },

  setPayAmount(type) {
    const cust = this.selectedCustForPay;
    if (!cust) return;
    const input = document.getElementById('pay-amount-input');

    if (type === 'full') {
      input.value = cust.due > 0 ? cust.due : cust.fee;
    } else if (type === 'fee') {
      input.value = cust.fee;
    } else if (typeof type === 'number') {
      input.value = type;
    }
  },

  submitPayment(printImmediately = true) {
    if (!this.hasPermission('bill_pay')) {
      alert('বিল আদায় করার অনুমতি আপনার নেই!');
      return;
    }
    const cust = this.selectedCustForPay;
    if (!cust) return;

    const amount = Number(document.getElementById('pay-amount-input').value) || 0;
    const discount = Number(document.getElementById('pay-discount-input').value) || 0;
    const method = document.getElementById('pay-method-select').value || 'Cash';
    const note = document.getElementById('pay-note-input').value.trim();

    if (amount <= 0 && discount <= 0) {
      alert('অনুগ্রহ করে সঠিক টাকার পরিমাণ লিখুন!');
      return;
    }

    const rand = Math.floor(1000 + Math.random() * 9000);
    const receiptNo = `PI-${new Date().getFullYear().toString().slice(2)}${String(new Date().getMonth()+1).padStart(2,'0')}-${rand}`;
    const timestamp = new Date().toLocaleString('bn-BD');

    const totalDeduction = amount + discount;
    const previousDue = cust.due;
    cust.due = Math.max(0, cust.due - totalDeduction);

    const paymentRecord = {
      receiptNo,
      custId: cust.id,
      custName: cust.name,
      custPhone: cust.phone,
      area: cust.area,
      package: cust.package,
      previousDue,
      amount,
      discount,
      remainingDue: cust.due,
      method,
      note,
      staff: this.currentUser.name,
      date: timestamp,
      timestamp: Date.now(),
      synced: navigator.onLine && Boolean(this.gasApiUrl)
    };

    this.payments.push(paymentRecord);

    if (!paymentRecord.synced) {
      this.syncQueue.push(paymentRecord);
    } else {
      this.pushToGoogleSheet(paymentRecord);
    }

    this.saveStorage();
    this.checkSyncQueue();
    this.render();
    this.closeModal('pay-modal');
    this.renderAndShowReceipt(paymentRecord);
  },

  renderAndShowReceipt(p) {
    const receiptHTML = `
      <div class="receipt-header">
        <img src="logo.png" alt="Pirgacha Internet" class="receipt-logo-img">
        <div class="receipt-title">PIRGACHA INTERNET</div>
        <div class="receipt-subtitle">Connect to the world</div>
        <div style="font-size: 0.72rem; color: #333;">পীরগাছা, রংপুর | হেল্পলাইন: 01700-000000</div>
      </div>

      <div class="receipt-row">
        <span>রিসিট নং:</span>
        <strong>${p.receiptNo}</strong>
      </div>
      <div class="receipt-row">
        <span>তারিখ:</span>
        <span>${p.date}</span>
      </div>
      <div class="receipt-row">
        <span>আদায়কারী স্টাফ:</span>
        <span>${p.staff}</span>
      </div>

      <div class="receipt-divider"></div>

      <div class="receipt-row">
        <span>গ্রাহকের নাম:</span>
        <strong>${p.custName}</strong>
      </div>
      <div class="receipt-row">
        <span>কাস্টমার আইডি:</span>
        <span>${p.custId}</span>
      </div>
      <div class="receipt-row">
        <span>মোবাইল:</span>
        <span>${p.custPhone || '-'}</span>
      </div>
      <div class="receipt-row">
        <span>প্যাকেজ:</span>
        <span>${p.package}</span>
      </div>

      <div class="receipt-divider"></div>

      <div class="receipt-row">
        <span>পূর্বের বকেয়া:</span>
        <span>৳ ${p.previousDue}</span>
      </div>
      <div class="receipt-total-row">
        <span>জমা গ্রহণ (${p.method}):</span>
        <span>৳ ${p.amount}</span>
      </div>
      ${p.discount > 0 ? `
        <div class="receipt-row" style="color: #666;">
          <span>বিশেষ ছাড় (ডিসকাউন্ট):</span>
          <span>৳ ${p.discount}</span>
        </div>
      ` : ''}
      <div class="receipt-row" style="font-weight: 700; margin-top: 4px;">
        <span>বর্তমান অবশিষ্ট বকেয়া:</span>
        <span>৳ ${p.remainingDue}</span>
      </div>

      <div class="receipt-footer">
        <div>পীরগাছা ইন্টারনেটের সাথে থাকার জন্য ধন্যবাদ!</div>
        <div style="margin-top: 4px; font-size: 0.65rem;">সফটওয়্যার প্রস্তুতকারক: Pirgacha Smart Billing</div>
      </div>
    `;

    document.getElementById('receipt-preview-content').innerHTML = receiptHTML;
    document.getElementById('printable-receipt').innerHTML = receiptHTML;
    this.openModal('receipt-modal');
  },

  viewReceipt(receiptNo) {
    const p = this.payments.find(item => item.receiptNo === receiptNo);
    if (p) this.renderAndShowReceipt(p);
  },

  openAddCustomerModal() {
    if (!this.hasPermission('cust_manage')) {
      alert('নতুন গ্রাহক যোগ করার অনুমতি আপনার নেই!');
      return;
    }
    document.getElementById('customer-modal-title').textContent = '➕ নতুন গ্রাহক যোগ করুন';
    document.getElementById('cust-edit-id').value = '';
    document.getElementById('cust-name').value = '';
    document.getElementById('cust-phone').value = '';
    document.getElementById('cust-area').value = 'পীরগাছা বাজার';
    document.getElementById('cust-pppoe').value = '';
    document.getElementById('cust-pppoe-pass').value = '123456';
    document.getElementById('cust-fee').value = 800;
    document.getElementById('cust-initial-due').value = 0;
    const expDateEl = document.getElementById('cust-expire-date');
    if (expDateEl) expDateEl.value = '2026-09-30';
    this.openModal('customer-modal');
  },

  openEditCustomerModal(custId) {
    if (!this.hasPermission('cust_manage')) {
      alert('গ্রাহকের তথ্য সংশোধন করার অনুমতি আপনার নেই!');
      return;
    }
    const cust = this.customers.find(c => c.id === custId);
    if (!cust) return;

    document.getElementById('customer-modal-title').textContent = '✏️ গ্রাহকের তথ্য সংশোধন';
    document.getElementById('cust-edit-id').value = cust.id;
    document.getElementById('cust-name').value = cust.name;
    document.getElementById('cust-phone').value = cust.phone;
    document.getElementById('cust-area').value = cust.area;
    document.getElementById('cust-pppoe').value = cust.pppoe || '';
    document.getElementById('cust-pppoe-pass').value = cust.pppoePass || '123456';
    document.getElementById('cust-fee').value = cust.fee;
    document.getElementById('cust-initial-due').value = cust.due;
    const expDateEl = document.getElementById('cust-expire-date');
    if (expDateEl) expDateEl.value = cust.expireDate || '';
    this.openModal('customer-modal');
  },

  onPackageSelect(packageName) {
    const select = document.getElementById('cust-package');
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    if (price) document.getElementById('cust-fee').value = price;
  },

  saveCustomer() {
    if (!this.hasPermission('cust_manage')) {
      alert('গ্রাহক সংরক্ষণ করার অনুমতি আপনার নেই!');
      return;
    }

    const editId = document.getElementById('cust-edit-id').value;
    const name = document.getElementById('cust-name').value.trim();
    const phone = document.getElementById('cust-phone').value.trim();
    const area = document.getElementById('cust-area').value.trim();
    const pppoe = document.getElementById('cust-pppoe').value.trim();
    const pppoePass = document.getElementById('cust-pppoe-pass').value.trim() || '123456';
    const packageVal = document.getElementById('cust-package').value;
    const fee = Number(document.getElementById('cust-fee').value) || 0;
    const due = Number(document.getElementById('cust-initial-due').value) || 0;
    const expireDateInput = document.getElementById('cust-expire-date');
    const expireDate = expireDateInput ? (expireDateInput.value.trim() || '2026-09-30') : '2026-09-30';

    if (!name || !phone || !pppoe) {
      alert('দয়া করে গ্রাহকের নাম, মোবাইল নম্বর এবং PPPoE ইউজারনেম সঠিকভাবে পূরণ করুন!');
      return;
    }

    if (editId) {
      const cust = this.customers.find(c => c.id === editId);
      if (cust) {
        cust.name = name;
        cust.phone = phone;
        cust.area = area;
        cust.pppoe = pppoe;
        cust.pppoePass = pppoePass;
        cust.package = packageVal;
        cust.fee = fee;
        cust.due = due;
        cust.expireDate = expireDate;
      }
    } else {
      const nextId = `PI-${1000 + this.customers.length + 1}`;
      const newCust = {
        id: nextId,
        name,
        phone,
        area,
        package: packageVal,
        fee,
        pppoe,
        pppoePass,
        due,
        expireDate,
        status: 'active',
        joined: new Date().toISOString().slice(0, 10)
      };
      this.customers.unshift(newCust);
    }

    this.saveStorage();
    this.render();
    this.closeModal('customer-modal');
    alert('গ্রাহকের তথ্য সফলভাবে সংরক্ষণ করা হয়েছে!');
  },

  generateMonthlyBills() {
    if (!confirm('আপনি কি সকল সক্রিয় গ্রাহকের জন্য চলতি মাসের নতুন বিল জেনারেট করতে চান? এতে মাসিক ফি পূর্বের বকেয়ার সাথে যোগ হবে।')) {
      return;
    }

    let updatedCount = 0;
    this.customers.forEach(c => {
      if (c.status === 'active') {
        c.due = Number(c.due) + Number(c.fee);
        updatedCount++;
      }
    });

    this.saveStorage();
    this.render();
    alert(`সফল! ${updatedCount} জন সক্রিয় গ্রাহকের নতুন মাসের বিল বকেয়া তালিকায় যুক্ত হয়েছে।`);
  },

  // Modal Helpers
  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('open');
  },

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('open');
  },

  openUserModal() {
    this.openModal('user-modal');
  },

  openSettingsModal() {
    this.openModal('settings-modal');
  },

  // Offline Sync Management
  checkSyncQueue() {
    const banner = document.getElementById('sync-banner');
    const countEl = document.getElementById('sync-count');
    if (banner && countEl) {
      if (this.syncQueue.length > 0) {
        banner.classList.add('visible');
        countEl.textContent = this.syncQueue.length;
      } else {
        banner.classList.remove('visible');
      }
    }
  },

  async syncPendingQueue(silent = false) {
    if (!navigator.onLine) {
      if (!silent) alert('আপনার ডিভাইস এখনো অফলাইনে আছে! ইন্টারনেট সংযোগ পেলে আবার চেষ্টা করুন।');
      return;
    }

    if (!this.gasApiUrl) {
      if (!silent) {
        alert('গুগল শিট API URL সেট করা নেই! দয়া করে "শিট সেটিংস" থেকে Google Apps Script Web App URL প্রদান করুন।');
        this.openSettingsModal();
      }
      return;
    }

    const btn = document.getElementById('btn-sync-now');
    if (btn) btn.textContent = '🔄 সিংক হচ্ছে...';

    try {
      const res = await fetch(this.gasApiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: JSON.stringify({
          action: 'batch_sync_payments',
          payments: this.syncQueue
        })
      });

      const data = await res.json();
      if (data.status === 'success') {
        this.payments.forEach(p => { p.synced = true; });
        this.syncQueue = [];
        this.saveStorage();
        this.checkSyncQueue();
        this.render();
        if (!silent) alert('অভিনন্দন! অফলাইনে সংগৃহীত সকল বিল গুগল শিটে সফলভাবে সিংক হয়ে গেছে।');
      } else {
        throw new Error(data.message || 'Sync failed');
      }
    } catch (err) {
      console.warn('Sync failed:', err);
      if (!silent) alert('গুগল শিটের সাথে সংযোগ করা যায়নি। ইন্টারনেট চেক করুন বা কিছু পরে আবার চেষ্টা করুন।');
    } finally {
      if (btn) btn.textContent = '🔄 এখনি সিংক করুন';
    }
  },

  async pushToGoogleSheet(payment) {
    if (!this.gasApiUrl || !navigator.onLine) return;
    try {
      await fetch(this.gasApiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: JSON.stringify({
          action: 'record_payment',
          payment: payment
        })
      });
    } catch (err) {
      console.warn('Realtime push failed, added to queue:', err);
      this.syncQueue.push(payment);
      this.saveStorage();
      this.checkSyncQueue();
    }
  },

  saveSettings() {
    const urlInput = document.getElementById('setting-gas-url');
    if (urlInput) {
      this.gasApiUrl = urlInput.value.trim();
      localStorage.setItem('pirgacha_gas_url', this.gasApiUrl);
      this.closeModal('settings-modal');
      alert('গুগল শিট API সেটিংস সফলভাবে সংরক্ষিত হয়েছে!');
      if (this.syncQueue.length > 0 && navigator.onLine) {
        this.syncPendingQueue();
      }
    }
  },

  resetDemoData() {
    if (confirm('আপনি কি নিশ্চিত যে ডেমো ডাটা রিসেট করতে চান?')) {
      localStorage.removeItem('pirgacha_customers');
      localStorage.removeItem('pirgacha_staff_users');
      localStorage.removeItem('pirgacha_payments');
      localStorage.removeItem('pirgacha_expenses');
      localStorage.removeItem('pirgacha_complaints');
      localStorage.removeItem('pirgacha_sync_queue');
      this.customers = [...INITIAL_CUSTOMERS];
      this.staffUsers = JSON.parse(JSON.stringify(INITIAL_STAFF));
      this.payments = [...INITIAL_PAYMENTS];
      this.expenses = [...INITIAL_EXPENSES];
      this.complaints = [...INITIAL_COMPLAINTS];
      this.syncQueue = [];
      this.saveStorage();
      this.updateUserUI();
      this.render();
      this.closeModal('settings-modal');
      alert('ডেমো ডাটা সফলভাবে রিসেট হয়েছে!');
    }
  }
};

// Start application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  app.init();
});
