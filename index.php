<?php

require_once 'config.php';
require_once 'api.php';

$errorMessage = null;
$data = null;
$isConfigured = false;

if (FB_ACCESS_TOKEN !== 'YOUR_ACCESS_TOKEN_HERE' && 
    FB_AD_ACCOUNT_ID !== 'act_YOUR_AD_ACCOUNT_ID') {
    $isConfigured = true;
    
    try {
        $api = new FacebookAdsAPI();
        $data = $api->getAllData();
        
        if (isset($data['error'])) {
            $errorMessage = $data['error'];
            $data = null;
        }
    } catch (Exception $e) {
        $errorMessage = 'Error: ' . $e->getMessage();
    }
}

function formatCurrency($amount) {
    return '$' . number_format((float)$amount, 2);
}

function formatBudgetFromCents($cents) {
    return '$' . number_format($cents / 100, 2);
}

function formatNumber($number) {
    return number_format($number);
}

function getStatusBadge($status) {
    $statusClass = 'status-' . strtolower($status);
    return "<span class='status-badge {$statusClass}'>{$status}</span>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Ads Budget Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Facebook Ads Budget Tracker</h1>
            <p>Track and analyze your Facebook advertising campaigns, ad sets, and ads in real-time</p>
        </div>

        <?php if (!$isConfigured): ?>
            <div class="alert alert-warning">
                <strong>Configuration Required</strong><br>
                Please update your Facebook API credentials in <code>config.php</code>:
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>FB_APP_ID</li>
                    <li>FB_APP_SECRET</li>
                    <li>FB_ACCESS_TOKEN</li>
                    <li>FB_AD_ACCOUNT_ID</li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <strong>Error</strong><br>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($isConfigured && !$errorMessage): ?>
            <?php
            $totalLifetimeSpend = 0;
            $totalTodaySpend = 0;
            $totalCampaigns = isset($data['campaigns']) ? count($data['campaigns']) : 0;
            $activeCampaigns = 0;
            
            if (isset($data['campaigns'])) {
                foreach ($data['campaigns'] as $campaign) {
                    if (isset($data['insights']['campaign'][$campaign['id']]['lifetime']['spend'])) {
                        $totalLifetimeSpend += (float)$data['insights']['campaign'][$campaign['id']]['lifetime']['spend'];
                    }
                    if (isset($data['insights']['campaign'][$campaign['id']]['today']['spend'])) {
                        $totalTodaySpend += (float)$data['insights']['campaign'][$campaign['id']]['today']['spend'];
                    }
                    if (strtolower($campaign['status']) === 'active') {
                        $activeCampaigns++;
                    }
                }
            }
            ?>
            
            <div class="summary-cards-container">
                <div class="summary-card spend">
                    <div class="summary-card-title">Total Lifetime Spend</div>
                    <div class="summary-card-value"><?php echo formatCurrency($totalLifetimeSpend); ?></div>
                    <div class="summary-card-subtitle">Across all campaigns</div>
                </div>
                
                <div class="summary-card today">
                    <div class="summary-card-title">Today's Spend</div>
                    <div class="summary-card-value"><?php echo formatCurrency($totalTodaySpend); ?></div>
                    <div class="summary-card-subtitle">All active campaigns</div>
                </div>
                
                <div class="summary-card campaigns">
                    <div class="summary-card-title">Total Campaigns</div>
                    <div class="summary-card-value"><?php echo $totalCampaigns; ?></div>
                    <div class="summary-card-subtitle">In ad account</div>
                </div>
                
                <div class="summary-card active">
                    <div class="summary-card-title">Active Campaigns</div>
                    <div class="summary-card-value"><?php echo $activeCampaigns; ?></div>
                    <div class="summary-card-subtitle">Currently running</div>
                </div>
            </div>
            
            <div class="nav-tabs">
                <button class="tab-button active" onclick="showTab('campaigns')">Campaigns</button>
                <button class="tab-button" onclick="showTab('adsets')">Ad Sets</button>
                <button class="tab-button" onclick="showTab('ads')">Ads</button>
            </div>

            <div id="campaigns-tab" class="tab-content active">
                <div class="data-section">
                    <h2 class="section-title">Campaigns Overview</h2>
                    
                    <?php if (isset($data['campaigns']) && count($data['campaigns']) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Campaign Name</th>
                                    <th>Status</th>
                                    <th>Objective</th>
                                    <th>Daily Budget</th>
                                    <th>Lifetime Budget</th>
                                    <th>Total Spend</th>
                                    <th>Budget Remaining</th>
                                    <th>Campaign ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['campaigns'] as $campaign): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($campaign['name']); ?></strong></td>
                                        <td><?php echo getStatusBadge($campaign['status']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['objective'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                            if (isset($campaign['daily_budget'])) {
                                                echo formatBudgetFromCents($campaign['daily_budget']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($campaign['lifetime_budget'])) {
                                                echo formatBudgetFromCents($campaign['lifetime_budget']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="spend-total">
                                                <?php 
                                                if (isset($data['insights']['campaign'][$campaign['id']]['lifetime']['spend'])) {
                                                    echo formatCurrency($data['insights']['campaign'][$campaign['id']]['lifetime']['spend']);
                                                } else {
                                                    echo '$0.00';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $lifetimeSpend = isset($data['insights']['campaign'][$campaign['id']]['lifetime']['spend']) 
                                                ? (float)$data['insights']['campaign'][$campaign['id']]['lifetime']['spend'] 
                                                : 0;
                                            $budget = isset($campaign['lifetime_budget']) ? $campaign['lifetime_budget'] / 100 : 0;
                                            $remaining = $budget - $lifetimeSpend;
                                            if ($budget > 0) {
                                                echo formatCurrency($remaining);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($campaign['id']); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-text">No campaigns found</div>
                            <p>Create campaigns in Facebook Ads Manager to see them here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="adsets-tab" class="tab-content">
                <div class="data-section">
                    <h2 class="section-title">Ad Sets Overview</h2>
                    
                    <?php if (isset($data['adsets']) && count($data['adsets']) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ad Set Name</th>
                                    <th>Campaign</th>
                                    <th>Status</th>
                                    <th>Daily Budget</th>
                                    <th>Total Spend</th>
                                    <th>Today's Spend</th>
                                    <th>Impressions</th>
                                    <th>Clicks</th>
                                    <th>Ad Set ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['adsets'] as $adset): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($adset['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($adset['campaign_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo getStatusBadge($adset['status']); ?></td>
                                        <td>
                                            <?php 
                                            if (isset($adset['daily_budget'])) {
                                                echo formatBudgetFromCents($adset['daily_budget']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="spend-total">
                                                <?php 
                                                if (isset($data['insights']['adset'][$adset['id']]['lifetime']['spend'])) {
                                                    echo formatCurrency($data['insights']['adset'][$adset['id']]['lifetime']['spend']);
                                                } else {
                                                    echo '$0.00';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="spend-today">
                                                <?php 
                                                if (isset($data['insights']['adset'][$adset['id']]['today']['spend'])) {
                                                    echo formatCurrency($data['insights']['adset'][$adset['id']]['today']['spend']);
                                                } else {
                                                    echo '$0.00';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($data['insights']['adset'][$adset['id']]['today']['impressions'])) {
                                                echo formatNumber($data['insights']['adset'][$adset['id']]['today']['impressions']);
                                            } else {
                                                echo '0';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($data['insights']['adset'][$adset['id']]['today']['clicks'])) {
                                                echo formatNumber($data['insights']['adset'][$adset['id']]['today']['clicks']);
                                            } else {
                                                echo '0';
                                            }
                                            ?>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($adset['id']); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-text">No ad sets found</div>
                            <p>Create ad sets in your campaigns to see them here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="ads-tab" class="tab-content">
                <div class="data-section">
                    <h2 class="section-title">Ads Overview</h2>
                    
                    <?php if (isset($data['ads']) && count($data['ads']) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ad Name</th>
                                    <th>Ad Set</th>
                                    <th>Campaign</th>
                                    <th>Status</th>
                                    <th>Today's Spend</th>
                                    <th>Impressions</th>
                                    <th>Clicks</th>
                                    <th>Creative ID</th>
                                    <th>Ad ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['ads'] as $ad): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($ad['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($ad['adset_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($ad['campaign_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo getStatusBadge($ad['status']); ?></td>
                                        <td>
                                            <span class="spend-today">
                                                <?php 
                                                if (isset($data['insights']['ad'][$ad['id']]['today']['spend'])) {
                                                    echo formatCurrency($data['insights']['ad'][$ad['id']]['today']['spend']);
                                                } else {
                                                    echo '$0.00';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($data['insights']['ad'][$ad['id']]['today']['impressions'])) {
                                                echo formatNumber($data['insights']['ad'][$ad['id']]['today']['impressions']);
                                            } else {
                                                echo '0';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($data['insights']['ad'][$ad['id']]['today']['clicks'])) {
                                                echo formatNumber($data['insights']['ad'][$ad['id']]['today']['clicks']);
                                            } else {
                                                echo '0';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($ad['creative']['id'])) {
                                                echo '<code>' . htmlspecialchars($ad['creative']['id']) . '</code>';
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="https://www.facebook.com/ads/manager/account/campaigns/?act=<?php echo urlencode(str_replace('act_', '', FB_AD_ACCOUNT_ID)); ?>&selected_ad_ids=<?php echo urlencode($ad['id']); ?>" 
                                               class="ad-link" target="_blank" rel="noopener noreferrer">
                                                <?php echo htmlspecialchars($ad['id']); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-text">No ads found</div>
                            <p>Create ads in your ad sets to see them here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showTab(tabName) {
            const tabs = document.querySelectorAll('.tab-content');
            const buttons = document.querySelectorAll('.tab-button');
            
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            buttons.forEach(button => {
                button.classList.remove('active');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
