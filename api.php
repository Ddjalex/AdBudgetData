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
        if (empty($this->accessToken) || $this->accessToken === 'YOUR_ACCESS_TOKEN_HERE') {
            return ['error' => 'Access token not configured'];
        }
        
        $params['access_token'] = $this->accessToken;
        $url = $this->apiBaseUrl . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        
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
        $fields = 'id,name,status,objective,daily_budget,lifetime_budget,created_time,start_time,stop_time';
        $endpoint = "/{$this->adAccountId}/campaigns";
        $params = [
            'fields' => $fields,
            'limit' => 100
        ];
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getCampaignInsights($campaignId, $datePreset = 'lifetime', $dateSince = null, $dateUntil = null) {
        $fields = 'spend,impressions,clicks,cpc,cpm,ctr';
        $endpoint = "/{$campaignId}/insights";
        $params = [
            'fields' => $fields
        ];
        
        if ($dateSince && $dateUntil && $datePreset === 'lifetime') {
            $params['time_range'] = json_encode([
                'since' => $dateSince,
                'until' => $dateUntil
            ]);
        } else {
            $params['date_preset'] = $datePreset;
        }
        
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
    
    public function getAdInsights($adId, $datePreset = 'today', $dateSince = null, $dateUntil = null) {
        $fields = 'spend,impressions,clicks,cpc,cpm,ctr';
        $endpoint = "/{$adId}/insights";
        $params = [
            'fields' => $fields
        ];
        
        if ($dateSince && $dateUntil && $datePreset === 'lifetime') {
            $params['time_range'] = json_encode([
                'since' => $dateSince,
                'until' => $dateUntil
            ]);
        } else {
            $params['date_preset'] = $datePreset;
        }
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getAdSetInsights($adSetId, $datePreset = 'today', $dateSince = null, $dateUntil = null) {
        $fields = 'spend,impressions,clicks,cpc,cpm,ctr';
        $endpoint = "/{$adSetId}/insights";
        $params = [
            'fields' => $fields
        ];
        
        if ($dateSince && $dateUntil && $datePreset === 'lifetime') {
            $params['time_range'] = json_encode([
                'since' => $dateSince,
                'until' => $dateUntil
            ]);
        } else {
            $params['date_preset'] = $datePreset;
        }
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getAllData($dateSince = null, $dateUntil = null) {
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
                
                $campaignLifetimeInsights = $this->getCampaignInsights($campaign['id'], 'lifetime', $dateSince, $dateUntil);
                if (isset($campaignLifetimeInsights['data'][0])) {
                    $data['insights']['campaign'][$campaign['id']]['lifetime'] = $campaignLifetimeInsights['data'][0];
                }
                
                $campaignTodayInsights = $this->getCampaignInsights($campaign['id'], 'today', $dateSince, $dateUntil);
                if (isset($campaignTodayInsights['data'][0])) {
                    $data['insights']['campaign'][$campaign['id']]['today'] = $campaignTodayInsights['data'][0];
                }
                
                $adsets = $this->getAdSets($campaign['id']);
                if (isset($adsets['data'])) {
                    foreach ($adsets['data'] as $adset) {
                        $adset['campaign_name'] = $campaign['name'];
                        $data['adsets'][] = $adset;
                        
                        $todayInsights = $this->getAdSetInsights($adset['id'], 'today', $dateSince, $dateUntil);
                        if (isset($todayInsights['data'][0])) {
                            $data['insights']['adset'][$adset['id']]['today'] = $todayInsights['data'][0];
                        }
                        
                        $lifetimeInsights = $this->getAdSetInsights($adset['id'], 'lifetime', $dateSince, $dateUntil);
                        if (isset($lifetimeInsights['data'][0])) {
                            $data['insights']['adset'][$adset['id']]['lifetime'] = $lifetimeInsights['data'][0];
                        }
                        
                        $ads = $this->getAds($adset['id']);
                        if (isset($ads['data'])) {
                            foreach ($ads['data'] as $ad) {
                                $ad['adset_name'] = $adset['name'];
                                $ad['campaign_name'] = $campaign['name'];
                                $data['ads'][] = $ad;
                                
                                $adTodayInsights = $this->getAdInsights($ad['id'], 'today', $dateSince, $dateUntil);
                                if (isset($adTodayInsights['data'][0])) {
                                    $data['insights']['ad'][$ad['id']]['today'] = $adTodayInsights['data'][0];
                                }
                                
                                $adLifetimeInsights = $this->getAdInsights($ad['id'], 'lifetime', $dateSince, $dateUntil);
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
    
    public function getCampaignsCreatedInRange($since, $until) {
        $fields = 'id,name,status,effective_status,objective,daily_budget,lifetime_budget,created_time,start_time,stop_time';
        $endpoint = "/{$this->adAccountId}/campaigns";
        
        $filtering = json_encode([
            [
                'field' => 'created_time',
                'operator' => 'GREATER_THAN',
                'value' => strtotime($since)
            ],
            [
                'field' => 'created_time',
                'operator' => 'LESS_THAN',
                'value' => strtotime($until . ' 23:59:59')
            ]
        ]);
        
        $params = [
            'fields' => $fields,
            'filtering' => $filtering,
            'limit' => 500
        ];
        
        $response = $this->makeRequest($endpoint, $params);
        return isset($response['data']) ? $response['data'] : $response;
    }
    
    public function getAdSetsCreatedInRange($since, $until) {
        $fields = 'id,name,status,effective_status,campaign_id,daily_budget,lifetime_budget,budget_remaining,created_time,start_time,end_time';
        $endpoint = "/{$this->adAccountId}/adsets";
        
        $filtering = json_encode([
            [
                'field' => 'created_time',
                'operator' => 'GREATER_THAN',
                'value' => strtotime($since)
            ],
            [
                'field' => 'created_time',
                'operator' => 'LESS_THAN',
                'value' => strtotime($until . ' 23:59:59')
            ]
        ]);
        
        $params = [
            'fields' => $fields,
            'filtering' => $filtering,
            'limit' => 500
        ];
        
        $response = $this->makeRequest($endpoint, $params);
        return isset($response['data']) ? $response['data'] : $response;
    }
    
    public function getAdsCreatedInRange($since, $until) {
        $fields = 'id,name,status,effective_status,adset_id,adset{daily_budget,lifetime_budget,start_time,end_time},creative{id,name},created_time';
        $endpoint = "/{$this->adAccountId}/ads";
        
        $filtering = json_encode([
            [
                'field' => 'created_time',
                'operator' => 'GREATER_THAN',
                'value' => strtotime($since)
            ],
            [
                'field' => 'created_time',
                'operator' => 'LESS_THAN',
                'value' => strtotime($until . ' 23:59:59')
            ]
        ]);
        
        $params = [
            'fields' => $fields,
            'filtering' => $filtering,
            'limit' => 500
        ];
        
        $response = $this->makeRequest($endpoint, $params);
        return isset($response['data']) ? $response['data'] : $response;
    }
    
    public static function calculateTotalAllocatedBudget($entity) {
        $allocatedBudget = 0;
        
        $lifetimeBudget = isset($entity['lifetime_budget']) ? floatval($entity['lifetime_budget']) : 0;
        $dailyBudget = isset($entity['daily_budget']) ? floatval($entity['daily_budget']) : 0;
        
        if ($lifetimeBudget > 0) {
            $allocatedBudget = $lifetimeBudget / 100;
        } elseif ($dailyBudget > 0) {
            $dailyBudgetUSD = $dailyBudget / 100;
            
            $startTime = isset($entity['start_time']) ? strtotime($entity['start_time']) : null;
            $endTime = isset($entity['end_time']) ? strtotime($entity['end_time']) : null;
            
            if ($startTime && $endTime && $endTime > $startTime) {
                $durationInSeconds = $endTime - $startTime;
                $durationInDays = max(1, ceil($durationInSeconds / 86400));
                $allocatedBudget = $dailyBudgetUSD * $durationInDays;
            } elseif ($startTime) {
                $currentTime = time();
                if ($currentTime > $startTime) {
                    $durationInSeconds = $currentTime - $startTime;
                    $durationInDays = max(1, ceil($durationInSeconds / 86400));
                    $allocatedBudget = $dailyBudgetUSD * $durationInDays;
                } else {
                    $allocatedBudget = $dailyBudgetUSD * 30;
                }
            } else {
                $allocatedBudget = $dailyBudgetUSD * 30;
            }
        } elseif (isset($entity['adset'])) {
            $adsetLifetimeBudget = isset($entity['adset']['lifetime_budget']) ? floatval($entity['adset']['lifetime_budget']) : 0;
            $adsetDailyBudget = isset($entity['adset']['daily_budget']) ? floatval($entity['adset']['daily_budget']) : 0;
            
            if ($adsetLifetimeBudget > 0) {
                $allocatedBudget = $adsetLifetimeBudget / 100;
            } elseif ($adsetDailyBudget > 0) {
                $dailyBudgetUSD = $adsetDailyBudget / 100;
                
                $startTime = isset($entity['adset']['start_time']) ? strtotime($entity['adset']['start_time']) : null;
                $endTime = isset($entity['adset']['end_time']) ? strtotime($entity['adset']['end_time']) : null;
                
                if ($startTime && $endTime && $endTime > $startTime) {
                    $durationInSeconds = $endTime - $startTime;
                    $durationInDays = max(1, ceil($durationInSeconds / 86400));
                    $allocatedBudget = $dailyBudgetUSD * $durationInDays;
                } elseif ($startTime) {
                    $currentTime = time();
                    if ($currentTime > $startTime) {
                        $durationInSeconds = $currentTime - $startTime;
                        $durationInDays = max(1, ceil($durationInSeconds / 86400));
                        $allocatedBudget = $dailyBudgetUSD * $durationInDays;
                    } else {
                        $allocatedBudget = $dailyBudgetUSD * 30;
                    }
                } else {
                    $allocatedBudget = $dailyBudgetUSD * 30;
                }
            }
        }
        
        return round($allocatedBudget, 2);
    }
}
