<?php

require_once 'config.php';

class FacebookAdsAPI {
    private $accessToken;
    private $apiBaseUrl;
    private $adAccountId;
    
    public function __construct($accountId = null) {
        $config = getConfig();
        $this->accessToken = $config['access_token'];
        $this->apiBaseUrl = $config['api_base_url'];
        $this->adAccountId = $accountId ?? $config['ad_account_id'];
    }
    
    private function makeRequest($endpoint, $params = []) {
        $params['access_token'] = $this->accessToken;
        $url = $this->apiBaseUrl . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['error' => 'cURL Error: ' . $error];
        }
        
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if ($httpCode !== 200) {
            return ['error' => 'API Error: ' . ($data['error']['message'] ?? 'Unknown error')];
        }
        
        return $data;
    }
    
    public function getCampaigns() {
        $fields = 'id,name,status,objective,daily_budget,lifetime_budget,created_time';
        $endpoint = "/{$this->adAccountId}/campaigns";
        $params = [
            'fields' => $fields,
            'limit' => 100
        ];
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getCampaignInsights($campaignId, $datePreset = 'lifetime') {
        $fields = 'spend,impressions,clicks,cpc,cpm,ctr';
        $endpoint = "/{$campaignId}/insights";
        $params = [
            'fields' => $fields,
            'date_preset' => $datePreset
        ];
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getAdSets($campaignId = null) {
        $fields = 'id,name,status,campaign_id,daily_budget,lifetime_budget,created_time';
        
        if ($campaignId) {
            $endpoint = "/{$campaignId}/adsets";
        } else {
            $endpoint = "/{$this->adAccountId}/adsets";
        }
        
        $params = [
            'fields' => $fields,
            'limit' => 100
        ];
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getAds($adSetId = null) {
        $fields = 'id,name,status,adset_id,creative{id,name},created_time';
        
        if ($adSetId) {
            $endpoint = "/{$adSetId}/ads";
        } else {
            $endpoint = "/{$this->adAccountId}/ads";
        }
        
        $params = [
            'fields' => $fields,
            'limit' => 100
        ];
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getAdInsights($adId, $datePreset = 'today') {
        $fields = 'spend,impressions,clicks,cpc,cpm,ctr';
        $endpoint = "/{$adId}/insights";
        $params = [
            'fields' => $fields,
            'date_preset' => $datePreset
        ];
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getAdSetInsights($adSetId, $datePreset = 'today') {
        $fields = 'spend,impressions,clicks,cpc,cpm,ctr';
        $endpoint = "/{$adSetId}/insights";
        $params = [
            'fields' => $fields,
            'date_preset' => $datePreset
        ];
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getAllData() {
        $campaigns = $this->getCampaigns();
        
        if (isset($campaigns['error'])) {
            return ['error' => $campaigns['error']];
        }
        
        $data = [
            'campaigns' => [],
            'adsets' => [],
            'ads' => [],
            'insights' => [
                'campaign' => [],
                'adset' => [],
                'ad' => []
            ]
        ];
        
        if (isset($campaigns['data'])) {
            foreach ($campaigns['data'] as $campaign) {
                $data['campaigns'][] = $campaign;
                
                $campaignLifetimeInsights = $this->getCampaignInsights($campaign['id'], 'lifetime');
                if (isset($campaignLifetimeInsights['data'][0])) {
                    $data['insights']['campaign'][$campaign['id']]['lifetime'] = $campaignLifetimeInsights['data'][0];
                }
                
                $campaignTodayInsights = $this->getCampaignInsights($campaign['id'], 'today');
                if (isset($campaignTodayInsights['data'][0])) {
                    $data['insights']['campaign'][$campaign['id']]['today'] = $campaignTodayInsights['data'][0];
                }
                
                $adsets = $this->getAdSets($campaign['id']);
                if (isset($adsets['data'])) {
                    foreach ($adsets['data'] as $adset) {
                        $adset['campaign_name'] = $campaign['name'];
                        $data['adsets'][] = $adset;
                        
                        $todayInsights = $this->getAdSetInsights($adset['id'], 'today');
                        if (isset($todayInsights['data'][0])) {
                            $data['insights']['adset'][$adset['id']]['today'] = $todayInsights['data'][0];
                        }
                        
                        $lifetimeInsights = $this->getAdSetInsights($adset['id'], 'lifetime');
                        if (isset($lifetimeInsights['data'][0])) {
                            $data['insights']['adset'][$adset['id']]['lifetime'] = $lifetimeInsights['data'][0];
                        }
                        
                        $ads = $this->getAds($adset['id']);
                        if (isset($ads['data'])) {
                            foreach ($ads['data'] as $ad) {
                                $ad['adset_name'] = $adset['name'];
                                $ad['campaign_name'] = $campaign['name'];
                                $data['ads'][] = $ad;
                                
                                $adTodayInsights = $this->getAdInsights($ad['id'], 'today');
                                if (isset($adTodayInsights['data'][0])) {
                                    $data['insights']['ad'][$ad['id']]['today'] = $adTodayInsights['data'][0];
                                }
                                
                                $adLifetimeInsights = $this->getAdInsights($ad['id'], 'lifetime');
                                if (isset($adLifetimeInsights['data'][0])) {
                                    $data['insights']['ad'][$ad['id']]['lifetime'] = $adLifetimeInsights['data'][0];
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $data;
    }
}
