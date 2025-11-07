# Manual Ad Account ID Fallback - Implementation Summary

## ✅ Implementation Complete

I've successfully restored the manual Ad Account ID input field in `settings.php` with a complete **3-tier priority system** for account selection.

---

## 📋 Changes Made

### 1. **settings.php - Updated Form Field**

**Location:** Lines 183-187

**Changes:**
- **Label Updated:** "Ad Account ID" → "Primary Ad Account ID (Optional Fallback)"
- **Removed `required` attribute:** Now optional, users can leave it blank
- **Enhanced description:** Added clear guidance explaining this is a fallback option
- **Recommendation added:** Directs users to use "Manage Accounts" for multiple accounts

**Updated HTML:**
```html
<div class="form-group">
    <label for="ad_account_id">Primary Ad Account ID (Optional Fallback)</label>
    <input type="text" id="ad_account_id" name="ad_account_id" 
           value="<?php echo htmlspecialchars(str_replace('act_', '', FB_AD_ACCOUNT_ID)); ?>" 
           placeholder="1234567890">
    <small>
        Your Ad Account ID (the act_ prefix will be added automatically). 
        This is used as a fallback when no account is selected from auto-discovery. 
        <strong>Recommended:</strong> Use the "Manage Accounts" page to discover and manage multiple accounts.
    </small>
</div>
```

---

### 2. **account_manager.php - 3-Tier Priority System**

**Location:** Lines 18-57 (getActiveAccount method)

**Priority Logic:**

```
Priority 1 (Highest):    Active account from auto-discovered list
                         ↓ (if not found)
Priority 2:              First account from discovered accounts
                         ↓ (if list is empty)
Priority 3 (Fallback):   Manual Ad Account ID from settings (config.php)
                         ↓ (if not configured)
Return null
```

**Implementation Details:**

**Priority 1 - Active Discovered Account:**
```php
foreach ($accounts as $account) {
    if ($account['active']) {
        return $account;  // Return the actively selected account
    }
}
```

**Priority 2 - First Discovered Account:**
```php
if (!empty($accounts)) {
    return $accounts[0];  // Return first account if no active selection
}
```

**Priority 3 - Manual Fallback:**
```php
if (defined('FB_AD_ACCOUNT_ID') && FB_AD_ACCOUNT_ID && 
    FB_AD_ACCOUNT_ID !== 'YOUR_AD_ACCOUNT_ID_HERE') {
    
    $accountId = FB_AD_ACCOUNT_ID;
    $id = str_replace('act_', '', $accountId);
    
    // Ensure act_ prefix
    if (!str_starts_with($accountId, 'act_')) {
        $accountId = 'act_' . $accountId;
    }
    
    return [
        'id' => $id,
        'name' => 'Manual Account (Fallback)',
        'account_id' => $accountId,
        'account_status' => 1,
        'currency' => 'USD',
        'timezone_name' => '',
        'business_name' => '',
        'active' => true,
        'is_fallback' => true  // Special flag
    ];
}
```

---

## 🎯 How It Works

### Scenario 1: Multiple Discovered Accounts (Priority 1)
1. User clicks "Discover Ad Accounts" in Manage Accounts page
2. System finds 7 accounts and stores them in `accounts.json`
3. User selects "Addis MA" as active account
4. **Dashboard displays:** "Addis MA (act_495040511652184)"
5. **Source:** accounts.json (Priority 1 ✓)

### Scenario 2: Discovered Accounts, No Active Selection (Priority 2)
1. User has 3 accounts discovered
2. No account explicitly marked as active
3. **Dashboard displays:** First account from the list
4. **Source:** accounts.json[0] (Priority 2 ✓)

### Scenario 3: No Discovered Accounts, Manual ID Set (Priority 3)
1. User enters "123456789" in Settings → Ad Account ID
2. No accounts in `accounts.json` (empty or file doesn't exist)
3. **Dashboard displays:** "Manual Account (Fallback) (act_123456789)"
4. **Source:** config.php FB_AD_ACCOUNT_ID (Priority 3 ✓)
5. **Special flag:** `is_fallback: true` identifies this is from manual config

### Scenario 4: Nothing Configured
1. No discovered accounts
2. No manual Ad Account ID
3. **Dashboard displays:** Configuration warning
4. **Source:** null (prompts user to configure)

---

## 📝 Form Fields in settings.php

Your settings page now has **4 input fields:**

1. **App ID** (Required)
2. **App Secret** (Required)
3. **Access Token** (Required)
4. **Primary Ad Account ID (Optional Fallback)** ⭐ NEW - Now optional!

---

## 🔄 Data Flow

```
User saves settings.php
         ↓
Data saved to config.php as FB_AD_ACCOUNT_ID
         ↓
index.php loads → calls AccountManager->getActiveAccount()
         ↓
Check Priority 1: Active account in accounts.json? → YES → Use it ✓
         ↓ NO
Check Priority 2: Any accounts in accounts.json? → YES → Use first ✓
         ↓ NO
Check Priority 3: Manual ID in config.php? → YES → Use fallback ✓
         ↓ NO
Return null → Show configuration warning
```

---

## ✨ Key Features

1. **Non-Destructive:** Manual ID doesn't interfere with auto-discovery
2. **Clear Priority:** Always prefers discovered accounts over manual entry
3. **User-Friendly:** Clear labels and descriptions guide users
4. **Flexible:** Works for both single-account and multi-account users
5. **Failsafe:** Provides fallback when discovery isn't possible
6. **Trackable:** `is_fallback` flag identifies when fallback is active

---

## 🧪 Testing Scenarios

### Test 1: Manual Fallback Works
1. Delete `accounts.json` file
2. Set manual ID "495040511652184" in Settings
3. Visit dashboard
4. **Expected:** "Manual Account (Fallback) (act_495040511652184)"

### Test 2: Discovered Accounts Override Manual
1. Keep manual ID in settings
2. Discover accounts via "Manage Accounts"
3. Visit dashboard
4. **Expected:** Active discovered account (NOT manual fallback)

### Test 3: Empty Manual ID
1. Leave Ad Account ID field blank in Settings
2. Save settings
3. Don't discover any accounts
4. **Expected:** Configuration warning prompts to add account

---

## 📁 Modified Files

1. **settings.php** (Lines 183-189)
   - Updated form field label and description
   - Made field optional (removed `required`)

2. **account_manager.php** (Lines 18-57)
   - Implemented 3-tier priority system
   - Added fallback to config.php
   - Added `is_fallback` flag for identification

---

## 🎓 User Guidance

**For Single Account Users:**
- Option A: Use manual ID in Settings (quick setup)
- Option B: Use auto-discovery (gets account details)

**For Multi-Account Users:**
- **Recommended:** Use "Manage Accounts" → "Discover Ad Accounts"
- Manual ID acts as safety net if discovery fails

---

## ✅ Verification

Your implementation now includes:
- ✓ Manual Ad Account ID field restored in settings.php
- ✓ Field clearly labeled as "Optional Fallback"
- ✓ Field is now optional (not required)
- ✓ Priority system: Active > Discovered > Manual > Null
- ✓ Clear user guidance in field description
- ✓ Fallback flag for tracking
- ✓ Backward compatible with existing accounts.json
- ✓ Auto-adds 'act_' prefix if missing

---

## 🚀 Ready to Use

Your Facebook Ads Budget Tracker now has a robust account selection system that:
1. Prioritizes user selections from discovered accounts
2. Falls back to manual configuration when needed
3. Provides clear guidance to users
4. Works seamlessly with both single and multiple accounts

