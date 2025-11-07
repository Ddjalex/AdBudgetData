<?php

class AccountManager {
    private $accountsFile = 'accounts.json';
    
    public function getAccounts() {
        if (!file_exists($this->accountsFile)) {
            return [];
        }
        
        $json = file_get_contents($this->accountsFile);
        return json_decode($json, true) ?? [];
    }
    
    public function getActiveAccount() {
        $accounts = $this->getAccounts();
        
        // Priority 1: Return actively selected account from discovered accounts
        foreach ($accounts as $account) {
            if ($account['active']) {
                return $account;
            }
        }
        
        // Priority 2: If no active account found, return first account if available
        if (!empty($accounts)) {
            return $accounts[0];
        }
        
        // Priority 3: Fallback to manual Ad Account ID from settings (config.php)
        if (defined('FB_AD_ACCOUNT_ID') && FB_AD_ACCOUNT_ID && FB_AD_ACCOUNT_ID !== 'YOUR_AD_ACCOUNT_ID_HERE') {
            $accountId = FB_AD_ACCOUNT_ID;
            $id = str_replace('act_', '', $accountId);
            
            // Make sure it has act_ prefix
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
                'is_fallback' => true  // Flag to indicate this is from manual config
            ];
        }
        
        return null;
    }
    
    public function getAccountById($id) {
        $accounts = $this->getAccounts();
        foreach ($accounts as $account) {
            if ($account['id'] === $id) {
                return $account;
            }
        }
        return null;
    }
    
    public function addAccount($name, $accountId) {
        if (!str_starts_with($accountId, 'act_')) {
            $accountId = 'act_' . $accountId;
        }
        
        $id = str_replace('act_', '', $accountId);
        
        $accounts = $this->getAccounts();
        
        foreach ($accounts as $account) {
            if ($account['account_id'] === $accountId) {
                return ['success' => false, 'message' => 'Account already exists'];
            }
        }
        
        $accounts[] = [
            'id' => $id,
            'name' => $name,
            'account_id' => $accountId,
            'account_status' => 1,
            'currency' => 'USD',
            'timezone_name' => '',
            'business_name' => '',
            'active' => empty($accounts)
        ];
        
        file_put_contents($this->accountsFile, json_encode($accounts, JSON_PRETTY_PRINT));
        return ['success' => true, 'message' => 'Account added successfully'];
    }
    
    public function setActiveAccount($id) {
        $accounts = $this->getAccounts();
        $found = false;
        
        foreach ($accounts as &$account) {
            $account['active'] = ($account['id'] === $id);
            if ($account['id'] === $id) {
                $found = true;
            }
        }
        
        if ($found) {
            file_put_contents($this->accountsFile, json_encode($accounts, JSON_PRETTY_PRINT));
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Account not found'];
    }
    
    public function deleteAccount($id) {
        $accounts = $this->getAccounts();
        $newAccounts = [];
        $deleted = false;
        
        foreach ($accounts as $account) {
            if ($account['id'] !== $id) {
                $newAccounts[] = $account;
            } else {
                $deleted = true;
            }
        }
        
        if ($deleted && !empty($newAccounts)) {
            $newAccounts[0]['active'] = true;
        }
        
        file_put_contents($this->accountsFile, json_encode($newAccounts, JSON_PRETTY_PRINT));
        return ['success' => $deleted];
    }
}
