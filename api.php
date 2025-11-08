<?php

require_once 'config.php';

class FacebookAdsAPI {
    private $accessToken;
    private $apiBaseUrl;
    private $adAccountId;
    
    public function __construct($accountId = null) {
        $this->accessToken = FB_ACCESS_TOKEN;
        $this->apiBaseUrl = 'https://graph.facebook.com/' . FB_API_VERSION;
        $this->adAccountId = $accountId;
    }
    
    private function makeRequest($endpoint, $params = []) {
        if (empty($this->accessToken) || $this->accessToken === 'YOUR_ACCESS_TOKEN_HERE') {
            return ['error' => 'Access token not configured'];
        }
        
        $params['access_token'] = $this->accessToken;
        
        // Exponential Backoff: Retry up to 3 times with 10s, 20s, 40s delays
        $maxRetries = 3;
        $retryDelays = [10, 20, 40]; // seconds
        $attempt = 0;
        
        while ($attempt <= $maxRetries) {
            $url = $this->apiBaseUrl . $endpoint . '?' . http_build_query($params);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_HEADER, true); // Get headers for rate limit info
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return ['error' => 'cURL Error: ' . $error];
            }
            
            curl_close($ch);
            
            // Separate headers and body
            $headers = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);
            $data = json_decode($body, true);
            
            // Success - return data
            if ($httpCode === 200) {
                return $data;
            }
            
            // Check if this is a rate limit error
            $isRateLimitError = false;
            if ($httpCode === 400 && isset($data['error'])) {
                $errorCode = $data['error']['code'] ?? 0;
                $errorMessage = $data['error']['message'] ?? '';
                
                // Facebook Rate Limit Error Codes: 4, 17, 32, 613
                if (in_array($errorCode, [4, 17, 32, 613]) || 
                    strpos($errorMessage, 'User request limit reached') !== false ||
                    strpos($errorMessage, 'rate limit') !== false) {
                    $isRateLimitError = true;
                }
            }
            
            // If rate limit error and retries remaining, wait and retry
            if ($isRateLimitError && $attempt < $maxRetries) {
                $waitTime = $retryDelays[$attempt];
                error_log("Facebook API Rate Limit hit. Waiting {$waitTime} seconds before retry attempt " . ($attempt + 2) . "...");
                
                // Check connection abort every second during the wait
                for ($i = 0; $i < $waitTime; $i++) {
                    if (connection_aborted()) {
                        error_log("Connection aborted during retry wait. Stopping API requests.");
                        exit;
                    }
                    sleep(1);
                }
                
                $attempt++;
                continue;
            }
            
            // Non-rate-limit error or out of retries - return error
            return ['error' => 'API Error: ' . ($data['error']['message'] ?? 'Unknown error')];
        }
        
        // All retries exhausted
        return ['error' => 'User request limit reached. Please wait 15-30 minutes before trying again.'];
    }
    
    public function getCampaigns() {
        $fields = 'id,name,effective_status,budget,daily_budget,lifetime_budget,created_time,start_time,stop_time';
        $endpoint = "/{$this->adAccountId}/campaigns";
        $params = [
            'fields' => $fields,
            'limit' => 100
        ];
        
        return $this->makeRequest($endpoint, $params);
    }
    
    public function getCampaignInsights($campaignId, $datePreset = 'lifetime', $dateSince = null, $dateUntil = null) {
        $fields = 'spend';
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
        $fields = 'id,name,effective_status,campaign_id,budget,daily_budget,lifetime_budget,created_time,start_time,end_time';
        
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
        $fields = 'id,name,effective_status,adset_id,created_time';
        
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
        $fields = 'spend';
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
        $fields = 'spend';
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
                if (connection_aborted()) {
                    exit;
                }
                
                $data['campaigns'][] = $campaign;
                
                // OPTIMIZATION: Skip insights for PAUSED/DELETED campaigns to reduce API calls
                $isActive = isset($campaign['effective_status']) && $campaign['effective_status'] === 'ACTIVE';
                
                if ($isActive) {
                    if (connection_aborted()) {
                        exit;
                    }
                    
                    // OPTIMIZATION: Only fetch campaign-level insights to reduce API calls by 80-90%
                    // This prevents rate limits while maintaining budget tracking functionality
                    $campaignLifetimeInsights = $this->getCampaignInsights($campaign['id'], 'lifetime', $dateSince, $dateUntil);
                    if (isset($campaignLifetimeInsights['data'][0])) {
                        $data['insights']['campaign'][$campaign['id']]['lifetime'] = $campaignLifetimeInsights['data'][0];
                    }
                    
                    if (connection_aborted()) {
                        exit;
                    }
                    
                    $campaignTodayInsights = $this->getCampaignInsights($campaign['id'], 'today', $dateSince, $dateUntil);
                    if (isset($campaignTodayInsights['data'][0])) {
                        $data['insights']['campaign'][$campaign['id']]['today'] = $campaignTodayInsights['data'][0];
                    }
                }
                
                if (connection_aborted()) {
                    exit;
                }
                
                // Fetch ad sets structure (no insights - saves 2 API calls per ad set)
                $adsets = $this->getAdSets($campaign['id']);
                if (isset($adsets['data'])) {
                    foreach ($adsets['data'] as $adset) {
                        if (connection_aborted()) {
                            exit;
                        }
                        
                        $adset['campaign_name'] = $campaign['name'];
                        $data['adsets'][] = $adset;
                        
                        // Fetch ads structure (no insights - saves 2 API calls per ad)
                        $ads = $this->getAds($adset['id']);
                        if (isset($ads['data'])) {
                            foreach ($ads['data'] as $ad) {
                                $ad['adset_name'] = $adset['name'];
                                $ad['campaign_name'] = $campaign['name'];
                                $data['ads'][] = $ad;
                            }
                        }
                    }
                }
            }
        }
        
        return $data;
    }
    
    public function getCampaignsCreatedInRange($since, $until) {
        $fields = 'id,name,effective_status,budget,daily_budget,lifetime_budget,created_time,start_time,stop_time';
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
        $fields = 'id,name,effective_status,campaign_id,budget,daily_budget,lifetime_budget,created_time,start_time,end_time';
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
        $fields = 'id,name,effective_status,adset_id,created_time';
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
    
    public static function calculateTotalAllocatedBudget($entity, $parentCampaign = null) {
        $allocatedBudget = 0;
        
        // P1: Check Ad Set's own budget fields (Highest Priority)
        $lifetimeBudget = isset($entity['lifetime_budget']) ? floatval($entity['lifetime_budget']) : 0;
        $dailyBudget = isset($entity['daily_budget']) ? floatval($entity['daily_budget']) : 0;
        $budget = isset($entity['budget']) ? floatval($entity['budget']) : 0;
        
        // Try Ad Set's lifetime_budget first
        if ($lifetimeBudget > 0) {
            $allocatedBudget = $lifetimeBudget / 100;
        } 
        // Try Ad Set's daily_budget
        elseif ($dailyBudget > 0) {
            $allocatedBudget = self::calculateDailyBudgetAllocation(
                $dailyBudget,
                $entity['start_time'] ?? null,
                $entity['end_time'] ?? null
            );
        }
        // Try Ad Set's generic budget field
        elseif ($budget > 0) {
            $allocatedBudget = $budget / 100;
        }
        // P2: Check Parent Campaign's budget (Second Priority)
        elseif ($parentCampaign !== null) {
            $campaignLifetimeBudget = isset($parentCampaign['lifetime_budget']) ? floatval($parentCampaign['lifetime_budget']) : 0;
            $campaignDailyBudget = isset($parentCampaign['daily_budget']) ? floatval($parentCampaign['daily_budget']) : 0;
            $campaignBudget = isset($parentCampaign['budget']) ? floatval($parentCampaign['budget']) : 0;
            
            if ($campaignLifetimeBudget > 0) {
                $allocatedBudget = $campaignLifetimeBudget / 100;
            } elseif ($campaignDailyBudget > 0) {
                $allocatedBudget = self::calculateDailyBudgetAllocation(
                    $campaignDailyBudget,
                    $parentCampaign['start_time'] ?? ($entity['start_time'] ?? null),
                    $parentCampaign['stop_time'] ?? ($entity['end_time'] ?? null)
                );
            } elseif ($campaignBudget > 0) {
                $allocatedBudget = $campaignBudget / 100;
            }
        }
        // Special case: Check for nested adset data (for Ads entities)
        elseif (isset($entity['adset'])) {
            $adsetLifetimeBudget = isset($entity['adset']['lifetime_budget']) ? floatval($entity['adset']['lifetime_budget']) : 0;
            $adsetDailyBudget = isset($entity['adset']['daily_budget']) ? floatval($entity['adset']['daily_budget']) : 0;
            
            if ($adsetLifetimeBudget > 0) {
                $allocatedBudget = $adsetLifetimeBudget / 100;
            } elseif ($adsetDailyBudget > 0) {
                $allocatedBudget = self::calculateDailyBudgetAllocation(
                    $adsetDailyBudget,
                    $entity['adset']['start_time'] ?? null,
                    $entity['adset']['end_time'] ?? null
                );
            }
        }
        // P3: Final fallback is $0.00 (already set by default)
        
        return round($allocatedBudget, 2);
    }
    
    private static function calculateDailyBudgetAllocation($dailyBudgetCents, $startTime, $endTime) {
        $dailyBudgetUSD = $dailyBudgetCents / 100;
        
        $startTimestamp = $startTime ? strtotime($startTime) : null;
        $endTimestamp = $endTime ? strtotime($endTime) : null;
        
        if ($startTimestamp && $endTimestamp && $endTimestamp > $startTimestamp) {
            // Campaign/AdSet has both start and end dates
            $durationInSeconds = $endTimestamp - $startTimestamp;
            $durationInDays = max(1, ceil($durationInSeconds / 86400));
            return $dailyBudgetUSD * $durationInDays;
        } elseif ($startTimestamp) {
            // Campaign/AdSet is ongoing (no end date)
            $currentTime = time();
            if ($currentTime > $startTimestamp) {
                $durationInSeconds = $currentTime - $startTimestamp;
                $durationInDays = max(1, ceil($durationInSeconds / 86400));
                return $dailyBudgetUSD * $durationInDays;
            } else {
                // Hasn't started yet, use 30-day estimate
                return $dailyBudgetUSD * 30;
            }
        } else {
            // No timing information, use 30-day estimate
            return $dailyBudgetUSD * 30;
        }
    }
}
