/**
 * ============================================================================
 * Pirgacha Internet - Google Apps Script Backend Engine (Updated)
 * Connects Google Sheets Database with Billing, Expenses & Support
 * ============================================================================
 * 
 * Instructions:
 * 1. Open Google Sheets (https://sheets.new) and name it "Pirgacha Internet Database"
 * 2. Click "Extensions" (এক্সটেনশন) > "Apps Script"
 * 3. Delete any code in Code.gs and PASTE ALL CODE FROM THIS FILE
 * 4. From the top dropdown, select "autoSetupSheets" and click "Run" (এটি ১ ক্লিকে ৭টি শিট ও হেডার বানিয়ে দেবে)
 * 5. Click "Deploy" (ডিপ্লয়) > "New deployment"
 * 6. Select type: "Web app"
 * 7. Set:
 *    - Execute as: "Me"
 *    - Who has access: "Anyone"
 * 8. Click "Deploy" and copy the "Web app URL"
 * 9. Paste that URL into Pirgacha Billing App > "শিট সেটিংস" (Settings)
 * ============================================================================
 */

const SHEET_NAMES = {
  USERS: 'Users',
  CUSTOMERS: 'Customers',
  PAYMENTS: 'Payments',
  EXPENSES: 'Expenses',
  COMPLAINTS: 'Complaints',
  BILLS: 'Monthly_Bills',
  PACKAGES: 'Packages'
};

/**
 * Handle GET Requests: Fetch latest database records
 */
function doGet(e) {
  try {
    const ss = SpreadsheetApp.getActiveSpreadsheet();
    const customersSheet = ss.getSheetByName(SHEET_NAMES.CUSTOMERS);
    const paymentsSheet = ss.getSheetByName(SHEET_NAMES.PAYMENTS);
    const expensesSheet = ss.getSheetByName(SHEET_NAMES.EXPENSES);
    const complaintsSheet = ss.getSheetByName(SHEET_NAMES.COMPLAINTS);
    const usersSheet = ss.getSheetByName(SHEET_NAMES.USERS);

    return createJsonResponse({
      status: 'success',
      customers: customersSheet ? getSheetDataAsObjects(customersSheet) : [],
      payments: paymentsSheet ? getSheetDataAsObjects(paymentsSheet) : [],
      expenses: expensesSheet ? getSheetDataAsObjects(expensesSheet) : [],
      complaints: complaintsSheet ? getSheetDataAsObjects(complaintsSheet) : [],
      users: usersSheet ? getSheetDataAsObjects(usersSheet) : []
    });
  } catch (err) {
    return createJsonResponse({ status: 'error', message: err.toString() });
  }
}

/**
 * Handle POST Requests: Record payments, expenses, complaints, sync queue
 */
function doPost(e) {
  try {
    const ss = SpreadsheetApp.getActiveSpreadsheet();
    let data;

    if (e.postData && e.postData.contents) {
      try {
        data = JSON.parse(e.postData.contents);
      } catch (parseErr) {
        data = e.parameter;
      }
    } else {
      data = e.parameter;
    }

    const action = data.action;

    // Action 1: Record Single Payment
    if (action === 'record_payment') {
      recordPaymentEntry(ss, data.payment);
      return createJsonResponse({ status: 'success', message: 'Payment recorded successfully' });
    }

    // Action 2: Batch Sync Offline Payments
    if (action === 'batch_sync_payments') {
      const paymentsList = data.payments || [];
      paymentsList.forEach(p => {
        recordPaymentEntry(ss, p);
      });
      return createJsonResponse({ 
        status: 'success', 
        message: 'Successfully synced ' + paymentsList.length + ' offline payments' 
      });
    }

    // Action 3: Record Expense
    if (action === 'record_expense') {
      const expSheet = ss.getSheetByName(SHEET_NAMES.EXPENSES);
      const exp = data.expense;
      if (expSheet && exp) {
        expSheet.appendRow([
          exp.voucherNo || 'V-' + Date.now(),
          exp.date || new Date().toISOString().slice(0,10),
          exp.category || 'অন্যান্য',
          exp.staffName || '-',
          Number(exp.amount) || 0,
          exp.method || 'ক্যাশ',
          exp.paidBy || 'Admin',
          exp.description || ''
        ]);
      }
      return createJsonResponse({ status: 'success', message: 'Expense recorded successfully' });
    }

    // Action 4: Save Complaint
    if (action === 'save_complaint') {
      const compSheet = ss.getSheetByName(SHEET_NAMES.COMPLAINTS);
      const c = data.complaint;
      if (compSheet && c) {
        compSheet.appendRow([
          c.id || 'TCK-' + Date.now(),
          c.date || new Date().toLocaleString(),
          c.custId || '',
          c.custName || '',
          c.custPhone || '',
          c.area || '',
          c.issue || '',
          c.priority || 'Normal',
          c.status || 'Pending',
          c.assignedStaff || '',
          c.notes || '',
          c.solutionNote || ''
        ]);
      }
      return createJsonResponse({ status: 'success', message: 'Complaint saved successfully' });
    }

    // Action 5: Add / Update Customer
    if (action === 'save_customer') {
      saveCustomerEntry(ss, data.customer);
      return createJsonResponse({ status: 'success', message: 'Customer saved successfully' });
    }

    return createJsonResponse({ status: 'error', message: 'Invalid action: ' + action });
  } catch (err) {
    return createJsonResponse({ status: 'error', message: err.toString() });
  }
}

/**
 * Record Payment in Payments Sheet & Update Customer Balance
 */
function recordPaymentEntry(ss, p) {
  const paySheet = ss.getSheetByName(SHEET_NAMES.PAYMENTS);
  if (!paySheet) return;

  paySheet.appendRow([
    p.receiptNo || 'PI-REC-' + Date.now(),
    p.date || new Date().toLocaleString(),
    p.custId || '',
    p.custName || '',
    p.custPhone || '',
    Number(p.amount) || 0,
    Number(p.discount) || 0,
    Number(p.remainingDue) || 0,
    p.method || 'Cash',
    p.staff || 'Staff',
    p.note || ''
  ]);

  const custSheet = ss.getSheetByName(SHEET_NAMES.CUSTOMERS);
  if (custSheet) {
    const data = custSheet.getDataRange().getValues();
    for (let i = 1; i < data.length; i++) {
      if (data[i][0] == p.custId) {
        const currentDue = Number(data[i][8]) || 0; // Column 9: Total_Due
        const paid = (Number(p.amount) || 0) + (Number(p.discount) || 0);
        const newDue = Math.max(0, currentDue - paid);
        custSheet.getRange(i + 1, 9).setValue(newDue);
        break;
      }
    }
  }
}

/**
 * Save Customer Entry
 */
function saveCustomerEntry(ss, c) {
  const custSheet = ss.getSheetByName(SHEET_NAMES.CUSTOMERS);
  if (!custSheet) return;

  const data = custSheet.getDataRange().getValues();
  let found = false;

  for (let i = 1; i < data.length; i++) {
    if (data[i][0] == c.id) {
      found = true;
      custSheet.getRange(i + 1, 2).setValue(c.name);
      custSheet.getRange(i + 1, 3).setValue(c.phone);
      custSheet.getRange(i + 1, 4).setValue(c.area);
      custSheet.getRange(i + 1, 5).setValue(c.package);
      custSheet.getRange(i + 1, 6).setValue(c.fee);
      custSheet.getRange(i + 1, 7).setValue(c.pppoe);
      custSheet.getRange(i + 1, 8).setValue(c.pppoePass || '123456');
      custSheet.getRange(i + 1, 9).setValue(c.due);
      custSheet.getRange(i + 1, 10).setValue(c.status || 'active');
      break;
    }
  }

  if (!found) {
    custSheet.appendRow([
      c.id,
      c.name,
      c.phone,
      c.area,
      c.package,
      c.fee,
      c.pppoe || '',
      c.pppoePass || '123456',
      c.due || 0,
      c.status || 'active',
      new Date().toISOString().slice(0, 10)
    ]);
  }
}

/**
 * Helper: Convert Sheet Data to Objects
 */
function getSheetDataAsObjects(sheet) {
  const values = sheet.getDataRange().getValues();
  if (values.length < 2) return [];

  const headers = values[0];
  const results = [];

  for (let r = 1; r < values.length; r++) {
    const obj = {};
    for (let c = 0; c < headers.length; c++) {
      obj[headers[c]] = values[r][c];
    }
    results.push(obj);
  }
  return results;
}

/**
 * Helper: Create JSON Response
 */
function createJsonResponse(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

/**
 * ============================================================================
 * ONE-CLICK AUTO SETUP: Run this function once to create all sheets automatically!
 * ============================================================================
 */
function autoSetupSheets() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();

  function setupSheet(name, headers, sampleRows) {
    let sheet = ss.getSheetByName(name);
    if (!sheet) {
      sheet = ss.insertSheet(name);
    }
    sheet.clear();

    sheet.appendRow(headers);
    const headerRange = sheet.getRange(1, 1, 1, headers.length);
    headerRange.setBackground('#0A2540')
               .setFontColor('#FFFFFF')
               .setFontWeight('bold')
               .setFontSize(11);

    if (sampleRows && sampleRows.length > 0) {
      sampleRows.forEach(row => sheet.appendRow(row));
    }

    sheet.setFrozenRows(1);

    for (let col = 1; col <= headers.length; col++) {
      sheet.autoResizeColumn(col);
    }
  }

  // 1. Users Sheet
  setupSheet(SHEET_NAMES.USERS, 
    ['User_ID', 'Full_Name', 'Username', 'Password', 'Role', 'Permissions', 'Phone', 'Salary', 'Status'],
    [
      ['ST-101', 'রাশেদুল ইসলাম (এডমিন)', 'admin', '123', 'admin', 'all', '01700000000', 30000, 'active'],
      ['ST-102', 'মোঃ তারেক রহমান', 'tarek', '123', 'staff', 'bill_pay,bill_add,cust_renew,complaint_manage', '01711112222', 12000, 'active'],
      ['ST-103', 'মোঃ করিম মিয়া', 'karim', '123', 'staff', 'bill_pay,complaint_manage', '01733334444', 10000, 'active']
    ]
  );

  // 2. Customers Sheet
  setupSheet(SHEET_NAMES.CUSTOMERS,
    ['Cust_ID', 'Name', 'Phone', 'Area', 'Package', 'Monthly_Fee', 'PPPoE_User', 'PPPoE_Pass', 'Total_Due', 'Status', 'Expire_Date', 'Join_Date'],
    [
      ['PI-1001', 'মোঃ আব্দুর রহিম', '01712345678', 'পীরগাছা বাজার', 'Standard (10 Mbps)', 800, 'rahim_pi', '123456', 1600, 'suspended', '2026-09-05', '2025-01-10'],
      ['PI-1002', 'মোঃ কামরুল হাসান', '01898765432', 'কলেজ রোড', 'Starter (5 Mbps)', 500, 'kamrul_net', 'kamrul@12', 500, 'active', '2026-09-08', '2025-02-15'],
      ['PI-1003', 'নুরুল হুদা ট্রেডার্স', '01911223344', 'পীরগাছা বাজার', 'Turbo (20 Mbps)', 1200, 'nurul_biz', 'biz990', 0, 'active', '2026-09-30', '2024-11-20'],
      ['PI-1004', 'প্রকৌশলী সাজিদ আহমেদ', '01755667788', 'রেল স্টেশন মোড়', 'Premium (15 Mbps)', 1000, 'sajid_home', 'sajid@pi', 2000, 'suspended', '2026-09-06', '2025-03-01'],
      ['PI-1005', 'আল-মদিনা ফার্মেসি', '01633445566', 'হাসপাতাল রোড', 'Standard (10 Mbps)', 800, 'madina_pharmacy', 'madina#1', 800, 'active', '2026-09-09', '2025-01-25']
    ]
  );

  // 3. Payments Sheet
  setupSheet(SHEET_NAMES.PAYMENTS,
    ['Receipt_No', 'Date_Time', 'Cust_ID', 'Cust_Name', 'Cust_Phone', 'Amount_Paid', 'Discount', 'Remaining_Due', 'Method', 'Staff', 'Note'],
    [
      ['PI-REC-8901', '2026-09-08 09:30 AM', 'PI-1003', 'নুরুল হুদা ট্রেডার্স', '01911223344', 1200, 0, 0, 'Cash', 'মোঃ তারেক রহমান', 'ক্যাশ গ্রহণ']
    ]
  );

  // 4. Expenses Sheet (NEW)
  setupSheet(SHEET_NAMES.EXPENSES,
    ['Voucher_No', 'Date', 'Category', 'Staff_Name', 'Amount', 'Payment_Method', 'Paid_By', 'Description'],
    [
      ['V-101', '2026-09-08', 'বিদ্যুৎ বিল', '-', 1850, 'বিকাশ', 'Admin', 'অফিস সেপ্টেম্বর পল্লী বিদ্যুৎ বিল'],
      ['V-102', '2026-09-08', 'স্টাফ বেতন', 'মোঃ করিম মিয়া', 5000, 'ক্যাশ', 'Admin', 'চলতি মাসের অগ্রিম বেতন']
    ]
  );

  // 5. Complaints Sheet (NEW)
  setupSheet(SHEET_NAMES.COMPLAINTS,
    ['Ticket_ID', 'Date_Time', 'Cust_ID', 'Cust_Name', 'Cust_Phone', 'Area', 'Issue', 'Priority', 'Status', 'Assigned_Staff', 'Notes', 'Solution_Note'],
    [
      ['TCK-201', '2026-09-08 08:30 AM', 'PI-1001', 'মোঃ আব্দুর রহিম', '01712345678', 'পীরগাছা বাজার', 'ইন্টারনেট নেই / লাল বাতি (LOS)', 'Urgent', 'Pending', 'মোঃ তারেক রহমান', 'সকালে হঠাৎ লাল বাতি জ্বলছে', '']
    ]
  );

  // 6. Packages Sheet
  setupSheet(SHEET_NAMES.PACKAGES,
    ['Package_ID', 'Package_Name', 'Speed', 'Monthly_Price'],
    [
      ['PKG-01', 'Starter', '5 Mbps', 500],
      ['PKG-02', 'Standard', '10 Mbps', 800],
      ['PKG-03', 'Premium', '15 Mbps', 1000],
      ['PKG-04', 'Turbo', '20 Mbps', 1200]
    ]
  );

  SpreadsheetApp.getActiveSpreadsheet().toast('🎉 অভিনন্দন! Pirgacha Internet-এর সকল ডাটাবেস ও খরচ/কমপ্লেইন টেবিল প্রস্তুত হয়ে গেছে।', 'সাফল্য', 6);
}
