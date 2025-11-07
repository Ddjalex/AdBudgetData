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
        foreach ($accounts as $account) {
            if ($account['active']) {
                return $account;
            }
        }
        return !empty($accounts) ? $accounts[0] : null;
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
    
    public function syncDiscoveredAccounts($discoveredAccounts) {
        $existingAccounts = $this->getAccounts();
        $currentActiveId = null;
        
        foreach ($existingAccounts as $account) {
            if ($account['active']) {
                $currentActiveId = $account['id'];
                break;
            }
        }
        
        $syncedAccounts = [];
        $foundCurrentActive = false;
        $firstActiveAccountId = null;
        
        foreach ($discoveredAccounts as $discovered) {
            $accountId = $discovered['id'];
            $id = str_replace('act_', '', $accountId);
            $accountStatus = $discovered['account_status'] ?? 1;
            
            $businessName = '';
            if (isset($discovered['business']) && is_array($discovered['business'])) {
                $businessName = $discovered['business']['name'] ?? '';
            }
            
            $isActive = false;
            if ($accountStatus == 1) {
                if ($currentActiveId === $id) {
                    $isActive = true;
                    $foundCurrentActive = true;
                } elseif (empty($firstActiveAccountId)) {
                    $firstActiveAccountId = $id;
                }
            }
            
            $syncedAccounts[] = [
                'id' => $id,
                'name' => $discovered['name'] ?? 'Ad Account ' . $id,
                'account_id' => $accountId,
                'account_status' => $accountStatus,
                'currency' => $discovered['currency'] ?? 'USD',
                'timezone_name' => $discovered['timezone_name'] ?? '',
                'business_name' => $businessName,
                'active' => $isActive
            ];
        }
        
        if (!$foundCurrentActive && $firstActiveAccountId !== null) {
            foreach ($syncedAccounts as &$account) {
                if ($account['id'] === $firstActiveAccountId) {
                    $account['active'] = true;
                    break;
                }
            }
        }
        
        file_put_contents($this->accountsFile, json_encode($syncedAccounts, JSON_PRETTY_PRINT));
        return ['success' => true, 'count' => count($syncedAccounts)];
    }
}
