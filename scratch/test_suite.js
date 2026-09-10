const fs = require('fs');
const assert = require('assert');

// 1. Verify index.html
const html = fs.readFileSync('index.html', 'utf8');

console.log('1. Checking index.html:');
assert(!html.includes('id="staff-mode-banner"'), 'staff-mode-banner should be removed');
assert(!html.includes('id="header-mode-toggle-btn"'), 'header-mode-toggle-btn should be removed');
assert(html.includes('id="header-logout-btn"'), 'header-logout-btn must exist');
assert(html.includes('id="login-modal"'), 'login-modal must exist');
assert(html.includes('id="staff-password-input"'), 'staff-password-input must exist in staff modal');
assert(html.includes('id="staff-permissions-group"'), 'staff-permissions-group must exist');
assert(html.includes('id="perm-bill-pay"'), 'perm-bill-pay checkbox must exist');
assert(html.includes('id="perm-bill-add"'), 'perm-bill-add checkbox must exist');
assert(html.includes('id="perm-cust-manage"'), 'perm-cust-manage checkbox must exist');
assert(html.includes('id="perm-expense-manage"'), 'perm-expense-manage checkbox must exist');
assert(html.includes('id="perm-complaint-manage"'), 'perm-complaint-manage checkbox must exist');
assert(html.includes('id="perm-package-manage"'), 'perm-package-manage checkbox must exist');
assert(html.includes('id="perm-reports-view"'), 'perm-reports-view checkbox must exist');
assert(html.includes('id="perm-cust-renew"'), 'perm-cust-renew checkbox must exist');
assert(html.includes('id="renew-modal"'), 'renew-modal must exist');
assert(html.includes('renew-chips-grid'), 'renew-chips-grid must exist');
assert(html.includes('id="count-expired"'), 'count-expired chip count element must exist');
assert(html.includes('id="cust-expire-date"'), 'cust-expire-date input must exist in customer modal');
assert(html.includes('⚡ + ১ দিন'), '1-day quick renew chip must exist');
assert(html.includes('id="renew-start-date-input"'), 'renew-start-date-input must exist');
assert(html.includes('id="renew-pkg-calc-box"'), 'renew-pkg-calc-box must exist');
console.log('   ✓ All index.html element checks PASSED');

// 2. Verify style.css
const css = fs.readFileSync('style.css', 'utf8');
console.log('2. Checking style.css:');
assert(css.includes('.header-logout-btn'), '.header-logout-btn class must exist');
assert(css.includes('.login-backdrop'), '.login-backdrop class must exist');
assert(css.includes('.login-window'), '.login-window class must exist');
assert(css.includes('.permissions-checklist'), '.permissions-checklist class must exist');
assert(css.includes('.perm-check-item'), '.perm-check-item class must exist');
assert(css.includes('.btn-renew'), '.btn-renew class must exist');
assert(css.includes('.renew-chip'), '.renew-chip class must exist');
assert(css.includes('.status-expired'), '.status-expired class must exist');
assert(css.includes('.status-warning'), '.status-warning class must exist');
const openBraces = (css.match(/\{/g) || []).length;
const closeBraces = (css.match(/\}/g) || []).length;
assert.strictEqual(openBraces, closeBraces, 'CSS braces must be balanced');
console.log('   ✓ All style.css checks PASSED (Braces balanced: ' + openBraces + ')');

// 3. Verify GoogleAppsScript.gs
const gas = fs.readFileSync('GoogleAppsScript.gs', 'utf8');
console.log('3. Checking GoogleAppsScript.gs:');
assert(gas.includes("'Password'"), 'Password header must exist in Users sheet');
assert(gas.includes("'Permissions'"), 'Permissions header must exist in Users sheet');
assert(gas.includes("'Expire_Date'"), 'Expire_Date header must exist in Customers sheet');
console.log('   ✓ GoogleAppsScript.gs checks PASSED');

// 4. Test app.js runtime logic by mocking a browser environment
console.log('4. Testing app.js runtime logic:');

// Mock localStorage
const storage = {};
global.localStorage = {
  getItem: (k) => (k in storage ? storage[k] : null),
  setItem: (k, v) => { storage[k] = String(v); },
  removeItem: (k) => { delete storage[k]; },
  clear: () => { Object.keys(storage).forEach(k => delete storage[k]); }
};
global.sessionStorage = { ...global.localStorage };

// Mock minimal DOM
const elements = {};
function createMockEl(id) {
  return {
    id,
    value: '',
    textContent: '',
    innerHTML: '',
    style: {},
    classList: {
      classes: new Set(),
      add(c) { this.classes.add(c); },
      remove(c) { this.classes.delete(c); },
      toggle(c, force) { if (force !== undefined) { if (force) this.classes.add(c); else this.classes.delete(c); } else { if (this.classes.has(c)) this.classes.delete(c); else this.classes.add(c); } },
      contains(c) { return this.classes.has(c); }
    },
    setAttribute(a, v) { this[a] = v; },
    getAttribute(a) { return this[a]; },
    checked: false,
    disabled: false,
    addEventListener: () => {},
    focus() {}
  };
}

global.document = {
  getElementById: (id) => {
    if (!elements[id]) elements[id] = createMockEl(id);
    return elements[id];
  },
  querySelector: (selector) => null,
  querySelectorAll: (selector) => [],
  addEventListener: () => {}
};
global.window = {
  addEventListener: () => {},
  alert: (msg) => { global.lastAlert = msg; },
  confirm: () => true
};
global.alert = (msg) => { global.lastAlert = msg; };
global.confirm = () => true;
global.navigator = { onLine: true };

const vm = require('vm');
const appCode = fs.readFileSync('app.js', 'utf8');
vm.runInThisContext(appCode);

// Test 4.1: Unauthenticated state on init
app.init();
assert.strictEqual(app.currentUser, null, 'User must be null before logging in');
assert(document.getElementById('login-modal').classList.contains('open'), 'Login modal must be open');
console.log('   ✓ 4.1 Unauthenticated state & Login screen display PASSED');

// Test 4.2: Failed login
document.getElementById('login-username').value = 'admin';
document.getElementById('login-password').value = 'wrong_password';
app.submitLogin();
assert.strictEqual(app.currentUser, null, 'User must still be null after wrong password');
assert.strictEqual(document.getElementById('login-error-alert').style.display, 'block', 'Error alert displayed on wrong password');
console.log('   ✓ 4.2 Invalid credentials rejected with error alert PASSED');

// Test 4.3: Successful Admin Login via Demo Chip
app.fillDemoLogin('admin', '123');
assert(app.currentUser !== null, 'Admin must be logged in');
assert.strictEqual(app.currentUser.role, 'admin', 'Role must be admin');
assert(app.hasPermission('all'), 'Admin must have all permissions');
assert(app.hasPermission('cust_manage'), 'Admin has cust_manage');
assert(app.hasPermission('expense_manage'), 'Admin has expense_manage');
assert(app.hasPermission('reports_view'), 'Admin has reports_view');
assert(!document.getElementById('login-modal').classList.contains('open'), 'Login modal closed');
console.log('   ✓ 4.3 Admin login & full permissions PASSED');

// Test 4.4: Admin edits staff permissions
app.openStaffModal('ST-102'); // Edit Tarek
assert.strictEqual(document.getElementById('staff-username-input').value, 'tarek');
assert.strictEqual(document.getElementById('staff-password-input').value, '123');
// Grant cust_manage and expense_manage
document.getElementById('perm-cust-manage').checked = true;
document.getElementById('perm-expense-manage').checked = true;
app.saveStaff();

const updatedTarek = app.staffUsers.find(u => u.id === 'ST-102');
assert(updatedTarek.permissions.includes('cust_manage'), 'Tarek now has cust_manage');
assert(updatedTarek.permissions.includes('expense_manage'), 'Tarek now has expense_manage');
console.log('   ✓ 4.4 Admin granular permission assignment & saving PASSED');

// Test 4.5: Logout
app.logout();
assert.strictEqual(app.currentUser, null, 'Session cleared on logout');
assert(document.getElementById('login-modal').classList.contains('open'), 'Login modal reopened on logout');
console.log('   ✓ 4.5 Logout clears session & displays login modal PASSED');

// Test 4.6: Login as Staff (Tarek)
app.fillDemoLogin('tarek', '123');
assert(app.currentUser !== null, 'Tarek must be logged in');
assert.strictEqual(app.currentUser.role, 'staff', 'Role must be staff');
assert.strictEqual(document.getElementById('header-user-role').textContent, 'STAFF');

// Permission checks for Tarek
assert(app.hasPermission('bill_pay'), 'Tarek has bill_pay');
assert(app.hasPermission('bill_add'), 'Tarek has bill_add');
assert(app.hasPermission('complaint_manage'), 'Tarek has complaint_manage');
assert(app.hasPermission('cust_manage'), 'Tarek has cust_manage (granted earlier)');
assert(app.hasPermission('expense_manage'), 'Tarek has expense_manage (granted earlier)');
assert(!app.hasPermission('package_manage'), 'Tarek does NOT have package_manage');

// Test permission denial: Tarek tries to open package modal
global.lastAlert = null;
app.openPackageModal();
assert(global.lastAlert && global.lastAlert.includes('অনুমতি'), 'Tarek denied package modal access');
console.log('   ✓ 4.6 Staff view, permission verification & access enforcement PASSED');

// Test 4.7: Karim (Staff with minimal permissions)
app.logout();
app.fillDemoLogin('karim', '123');
assert.strictEqual(app.currentUser.username, 'karim');
assert(app.hasPermission('bill_pay'), 'Karim has bill_pay');
assert(!app.hasPermission('bill_add'), 'Karim does NOT have bill_add');
assert(!app.hasPermission('cust_manage'), 'Karim does NOT have cust_manage');
assert(!app.hasPermission('expense_manage'), 'Karim does NOT have expense_manage');

global.lastAlert = null;
app.openNewBillModal();
assert(global.lastAlert && global.lastAlert.includes('অনুমতি'), 'Karim denied new bill modal access');

global.lastAlert = null;
app.openAddCustomerModal();
assert(global.lastAlert && global.lastAlert.includes('অনুমতি'), 'Karim denied add customer access');
// Test 4.8: Package selection system in New Bill modal
app.fillDemoLogin('admin', '123');
assert(html.includes('id="new-bill-package-select"'), 'new-bill-package-select element must exist in index.html');
app.openNewBillModal();
assert(document.getElementById('new-bill-package-select').innerHTML.includes('Starter'), 'Package options rendered');

// Select customer PI-1001 (Rahim, Standard (10 Mbps), fee: 800)
app.selectCustForNewBill('PI-1001');
assert.strictEqual(document.getElementById('new-bill-amount-input').value, 800, 'Amount auto-filled with customer package fee');

// Manually select another package: Turbo (20 Mbps) -> 1200
app.onNewBillPackageSelect('Turbo (20 Mbps)');
assert.strictEqual(document.getElementById('new-bill-amount-input').value, 1200, 'Amount updated to 1200 when Turbo is selected');

// Test custom package selection
app.onNewBillPackageSelect('custom');
assert.strictEqual(document.getElementById('new-bill-amount-input').value, '', 'Amount cleared for custom entry');

// Set amount back to 1000 and submit bill
document.getElementById('new-bill-amount-input').value = 1000;
document.getElementById('new-bill-date-input').value = '2026-09-08';
const custBefore = app.customers.find(c => c.id === 'PI-1001');
const dueBefore = custBefore.due;
app.submitNewBill();
assert.strictEqual(custBefore.due, dueBefore + 1000, 'Due increased by bill amount');
console.log('   ✓ 4.8 New bill package selection system & auto-pricing PASSED');

// Test 4.9: Customer Line Renewal & Grace Validity Extension System
console.log('Testing 4.9: Customer Line Renewal & Validity Extension:');
// 1. Expiry Status Helper
const expPast = app.getExpiryStatus('2026-09-05');
assert(expPast.isExpired, 'Past date marked as expired');
assert(expPast.daysDiff < 0, 'Past date has negative daysDiff');

const expToday = app.getExpiryStatus('2026-09-08');
assert(expToday.isToday, 'Today date marked as isToday');

const expFuture = app.getExpiryStatus('2026-09-11');
assert(!expFuture.isExpired && expFuture.daysDiff === 3, '3 days future calculated correctly');

// 2. Filter for expired customers
app.currentFilter = 'expired';
const expiredCusts = app.getFilteredCustomers();
assert(expiredCusts.length > 0, 'Expired customers returned');
assert(expiredCusts.some(c => c.id === 'PI-1001'), 'Rahim is in expired list');
app.currentFilter = 'all';

// 3. Test 1-Day Renewal & Package Pro-Rata Auto Calculation for PI-1001 (Rahim, fee: 800)
app.fillDemoLogin('admin', '123');
const rahim = app.customers.find(c => c.id === 'PI-1001');
rahim.status = 'suspended';
rahim.expireDate = '2026-09-05';
rahim.fee = 800;
const rahimDueBefore = rahim.due;
app.openRenewModal('PI-1001');

// Verify start date initialized to today (2026-09-08) for expired customer
assert.strictEqual(document.getElementById('renew-start-date-input').value, '2026-09-08', 'Start date initialized to today for expired customer');

// Select 1 Day Renewal
app.setRenewDays(1);
assert.strictEqual(document.getElementById('renew-new-date-input').value, '2026-09-09', '1 day extension ends on 2026-09-09');
// Package calculation for 1 day of 800/mo = Math.round((800/30)*1) = 27
assert.strictEqual(document.getElementById('renew-bill-amount').value, 27, '1 day package pro-rata bill is 27 for 800 fee');
assert.strictEqual(document.getElementById('renew-calc-total-badge').textContent, '৳ ২৭', 'Badge shows ৳ ২৭');

// Select 2 Days Renewal
app.setRenewDays(2);
assert.strictEqual(document.getElementById('renew-new-date-input').value, '2026-09-10', '2 days extension ends on 2026-09-10');
// Package calculation for 2 days of 800/mo = Math.round((800/30)*2) = 53
assert.strictEqual(document.getElementById('renew-bill-amount').value, 53, '2 days package pro-rata bill is 53');
assert.strictEqual(document.getElementById('renew-calc-total-badge').textContent, '৳ ৫৩', 'Badge shows ৳ ৫৩');

// Select 3 Days Renewal
app.setRenewDays(3);
assert.strictEqual(document.getElementById('renew-new-date-input').value, '2026-09-11', '3 days extension ends on 2026-09-11');
// Package calculation for 3 days of 800/mo = Math.round((800/30)*3) = 80
assert.strictEqual(document.getElementById('renew-bill-amount').value, 80, '3 days package pro-rata bill is 80');
assert.strictEqual(document.getElementById('renew-calc-total-badge').textContent, '৳ ৮০', 'Badge shows ৳ ৮০');

// Test Start Date Change to future date (e.g. 2026-09-10)
app.onRenewStartDateChange('2026-09-10');
// With 3 days, end date should become 2026-09-13
assert.strictEqual(document.getElementById('renew-new-date-input').value, '2026-09-13', 'End date shifts with start date');
assert.strictEqual(document.getElementById('renew-bill-amount').value, 80, 'Package calculation preserved for 3 days');

// Set back to 1 Day from 2026-09-08
document.getElementById('renew-start-date-input').value = '2026-09-08';
app.setRenewDays(1);
assert.strictEqual(document.getElementById('renew-bill-amount').value, 27);

// Submit 1-day package renewal
app.submitRenew();
assert.strictEqual(rahim.expireDate, '2026-09-09', 'Rahim expireDate updated to 2026-09-09');
assert.strictEqual(rahim.status, 'active', 'Rahim line activated');
assert.strictEqual(rahim.due, rahimDueBefore + 27, 'Rahim due increased by package charge (27)');

// 4. Test Free Grace Renewal (PI-1002 Kamrul)
const kamrul = app.customers.find(c => c.id === 'PI-1002');
kamrul.expireDate = '2026-09-08';
const kamrulDueBefore = kamrul.due;
app.openRenewModal('PI-1002');

// Select 2 days and set free grace
app.setRenewDays(2);
document.getElementById('renew-bill-type').value = 'free';
app.onRenewBillTypeChange('free');
assert.strictEqual(document.getElementById('renew-bill-amount').value, 0, 'Amount set to 0 for free renewal');

app.submitRenew();
assert.strictEqual(kamrul.expireDate, '2026-09-10', 'Kamrul expireDate updated to 2026-09-10');
assert.strictEqual(kamrul.due, kamrulDueBefore, 'Kamrul due unchanged for free grace renewal');

// 5. Test Staff permissions for Renewal
app.logout();
app.fillDemoLogin('tarek', '123');
assert(app.hasPermission('cust_renew'), 'Tarek has cust_renew permission');
global.lastAlert = null;
app.openRenewModal('PI-1003');
assert(!global.lastAlert || !global.lastAlert.includes('অনুমতি'), 'Tarek allowed to open renew modal');

// 6. Test Quick Action bar renewal with customer search
app.fillDemoLogin('admin', '123');
assert(html.includes('id="btn-quick-renew"'), 'btn-quick-renew exists in index.html');
app.openRenewModal(); // Open without customer ID
assert.strictEqual(app.selectedCustForRenew, null, 'Customer not selected initially in quick renew modal');

// Search for 'কামরুল'
app.handleRenewCustSearch('কামরুল');
assert(document.getElementById('renew-search-results').innerHTML.includes('কামরুল'), 'Search results contain Kamrul');

// Select Kamrul
app.selectCustForRenew('PI-1002');
assert.strictEqual(app.selectedCustForRenew.id, 'PI-1002', 'Kamrul selected via search');
assert(document.getElementById('renew-selected-box').classList.contains('active'), 'Selected box activated');

console.log('   ✓ 4.9 Customer line renewal & grace validity extension PASSED');

console.log('\n🎉 ALL TESTS PASSED SUCCESSFULLY! Everything is verified.');
