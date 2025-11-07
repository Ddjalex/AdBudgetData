<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

require_once 'config.php';
require_once 'api.php';
require_once 'account_manager.php';

$accountManager = new AccountManager();
$activeAccount = $accountManager->getActiveAccount();
$allAccounts = $accountManager->getAccounts();

$errorMessage = null;
$data = null;
$isConfigured = false;

require_once 'time_filter.php';
$filterType = $_GET['filter'] ?? 'all';
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$timeRange = getTimeRangeForFilter($filterType, $startDate, $endDate);
$dateSince = $timeRange['since'];
$dateUntil = $timeRange['until'];

if (FB_ACCESS_TOKEN !== 'YOUR_ACCESS_TOKEN_HERE' && $activeAccount) {
    $isConfigured = true;
    
    // Only load data if explicitly requested
    if (isset($_GET['load_data']) && $_GET['load_data'] === '1') {
        try {
            set_time_limit(90);
            $api = new FacebookAdsAPI($activeAccount['account_id']);
            $data = $api->getAllData($dateSince, $dateUntil);
            
            if (isset($data['error'])) {
                $errorMessage = $data['error'];
                $data = null;
            }
        } catch (Exception $e) {
            $errorMessage = 'Error: ' . $e->getMessage();
            $data = null;
        }
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

function formatDate($dateString) {
    if (empty($dateString)) {
        return 'N/A';
    }
    try {
        $date = new DateTime($dateString);
        return $date->format('M d, Y');
    } catch (Exception $e) {
        return 'N/A';
    }
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
            <div style="position: absolute; top: 30px; right: 30px; display: flex; gap: 10px; z-index: 1000;">
                <a href="employee_productivity.php" style="background: #8b5cf6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-block;">Employee Productivity</a>
                <a href="manage_accounts.php" style="background: #42b72a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-block;">Accounts</a>
                <a href="settings.php" style="background: #1877f2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-block;">Settings</a>
            </div>
        </div>

        <?php if (!empty($allAccounts) && count($allAccounts) > 1): ?>
            <div class="account-selector">
                <label>Active Ad Account:</label>
                <select id="accountSelect" onchange="switchAccount(this.value)">
                    <?php foreach ($allAccounts as $account): ?>
                        <option value="<?php echo htmlspecialchars($account['id']); ?>" <?php echo $account['active'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($account['name']); ?> (<?php echo htmlspecialchars($account['account_id']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php elseif (!empty($activeAccount)): ?>
            <div class="account-info-bar">
                <strong>Tracking:</strong> <?php echo htmlspecialchars($activeAccount['name']); ?> 
                <span style="color: #65676b;">(<?php echo htmlspecialchars($activeAccount['account_id']); ?>)</span>
            </div>
        <?php endif; ?>

        <?php if (!$isConfigured): ?>
            <div class="alert alert-warning">
                <strong>Configuration Required</strong><br>
                <?php if (!$activeAccount): ?>
                    Please add your first Ad Account. <a href="manage_accounts.php" style="color: #856404; text-decoration: underline; font-weight: 600;">Click here to add an account</a>
                <?php else: ?>
                    Please update your Facebook API credentials. <a href="settings.php" style="color: #856404; text-decoration: underline; font-weight: 600;">Click here to configure settings</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($isConfigured && $data === null && !isset($_GET['load_data'])): ?>
            <div class="alert alert-info" style="background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460;">
                <strong>Ready to Load Data</strong><br>
                Load your Facebook Ads data with time period filtering.
                <br><br>
                <form method="GET" id="filterForm" style="margin-top: 15px;">
                    <input type="hidden" name="load_data" value="1">
                    <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 180px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #0c5460;">Time Period</label>
                            <select name="filter" id="filterSelect" onchange="toggleDateRange()" style="width: 100%; padding: 8px; border: 1px solid #bee5eb; border-radius: 4px;">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                                <option value="all" selected>All Time</option>
                                <option value="custom">Custom Date Range</option>
                            </select>
                        </div>
                        <div id="dateRangeInputs" style="display: none; flex: 2; min-width: 350px;">
                            <div style="display: flex; gap: 15px;">
                                <div style="flex: 1;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #0c5460;">Start Date</label>
                                    <input type="date" name="start_date" style="width: 100%; padding: 8px; border: 1px solid #bee5eb; border-radius: 4px;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #0c5460;">End Date</label>
                                    <input type="date" name="end_date" style="width: 100%; padding: 8px; border: 1px solid #bee5eb; border-radius: 4px;">
                                </div>
                            </div>
                        </div>
                        <div>
                            <button type="submit" id="loadDataBtn" onclick="showLoading()" style="background: #1877f2; color: white; padding: 10px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer;">
                                📊 Load Data
                            </button>
                        </div>
                    </div>
                    <p style="margin-top: 10px; font-size: 13px; color: #0c5460;">
                        Select a time period to filter your campaign data. This may take 30-60 seconds.
                    </p>
                </form>
                <div id="loadingMessage" style="display: none; margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px;">
                    <strong>⏳ Loading data from Facebook...</strong><br>
                    Please wait, this may take up to 60 seconds. Do not refresh the page.
                </div>
            </div>
            <script>
            function toggleDateRange() {
                const select = document.getElementById('filterSelect');
                const dateRange = document.getElementById('dateRangeInputs');
                if (select.value === 'custom') {
                    dateRange.style.display = 'flex';
                } else {
                    dateRange.style.display = 'none';
                }
            }
            
            function showLoading() {
                document.getElementById('loadDataBtn').style.opacity = '0.5';
                document.getElementById('loadDataBtn').style.pointerEvents = 'none';
                document.getElementById('loadingMessage').style.display = 'block';
            }
            </script>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <strong>Error</strong><br>
                <?php echo htmlspecialchars($errorMessage); ?>
                <br><br>
                <a href="/" style="display: inline-block; background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                    ← Back to Dashboard
                </a>
            </div>
        <?php endif; ?>

        <?php if ($isConfigured && $data !== null && !$errorMessage): ?>
            <?php
            $totalLifetimeSpend = 0;
            $totalTodaySpend = 0;
            $totalBudget = 0;
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
                    
                    if (isset($campaign['lifetime_budget'])) {
                        $totalBudget += $campaign['lifetime_budget'] / 100;
                    } elseif (isset($campaign['daily_budget'])) {
                        $totalBudget += ($campaign['daily_budget'] / 100) * 30;
                    }
                    
                    if (strtolower($campaign['status']) === 'active') {
                        $activeCampaigns++;
                    }
                }
            }
            
            $remainingBudget = $totalBudget - $totalLifetimeSpend;
            $budgetUsedPercent = $totalBudget > 0 ? ($totalLifetimeSpend / $totalBudget) * 100 : 0;
            
            $progressBarColor = '#28a745';
            if ($budgetUsedPercent >= 90) {
                $progressBarColor = '#dc3545';
            } elseif ($budgetUsedPercent >= 80) {
                $progressBarColor = '#ffc107';
            } elseif ($budgetUsedPercent >= 60) {
                $progressBarColor = '#17a2b8';
            }
            ?>
            
            <div class="kpi-cards-container">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: #e3f2fd;">💰</div>
                    <div class="kpi-content">
                        <div class="kpi-label">Total Budget</div>
                        <div class="kpi-value"><?php echo formatCurrency($totalBudget); ?></div>
                        <div class="kpi-sublabel">Allocated for campaigns</div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: #fff3cd;">📊</div>
                    <div class="kpi-content">
                        <div class="kpi-label">Total Spend</div>
                        <div class="kpi-value"><?php echo formatCurrency($totalLifetimeSpend); ?></div>
                        <div class="kpi-sublabel">Lifetime across campaigns</div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: <?php echo $remainingBudget > 0 ? '#d4edda' : '#f8d7da'; ?>;">
                        <?php echo $remainingBudget > 0 ? '✓' : '⚠'; ?>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-label">Remaining Budget</div>
                        <div class="kpi-value" style="color: <?php echo $remainingBudget > 0 ? '#28a745' : '#dc3545'; ?>;">
                            <?php echo formatCurrency($remainingBudget); ?>
                        </div>
                        <div class="kpi-sublabel">Available to spend</div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: #e7f3ff;">📅</div>
                    <div class="kpi-content">
                        <div class="kpi-label">Today's Spend</div>
                        <div class="kpi-value"><?php echo formatCurrency($totalTodaySpend); ?></div>
                        <div class="kpi-sublabel">All active campaigns</div>
                    </div>
                </div>
            </div>

            <div class="budget-progress-container">
                <div class="progress-header">
                    <h3>Budget Utilization</h3>
                    <span class="progress-percentage"><?php echo number_format($budgetUsedPercent, 1); ?>%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: <?php echo min($budgetUsedPercent, 100); ?>%; background: <?php echo $progressBarColor; ?>;">
                        <?php if ($budgetUsedPercent > 5): ?>
                            <span class="progress-bar-text"><?php echo formatCurrency($totalLifetimeSpend); ?> / <?php echo formatCurrency($totalBudget); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($budgetUsedPercent >= 80): ?>
                    <div class="progress-warning">
                        ⚠ You've used <?php echo number_format($budgetUsedPercent, 1); ?>% of your total budget
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="summary-cards-container">
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
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Daily Budget</th>
                                    <th>Lifetime Budget</th>
                                    <th>Total Spend</th>
                                    <th>Budget Remaining</th>
                                    <th>Today's Unallocated</th>
                                    <th>Campaign ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['campaigns'] as $campaign): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($campaign['name']); ?></strong></td>
                                        <td><?php echo getStatusBadge($campaign['status']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['objective'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($campaign['start_time'] ?? ''); ?></td>
                                        <td>
                                            <?php 
                                            if (isset($campaign['stop_time']) && !empty($campaign['stop_time'])) {
                                                echo formatDate($campaign['stop_time']);
                                            } else {
                                                echo '<span style="color: #28a745; font-weight: 600;">Continuous</span>';
                                            }
                                            ?>
                                        </td>
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
                                                $color = $remaining > 0 ? 'green' : 'red';
                                                echo '<span style="color: ' . $color . '; font-weight: 600;">' . formatCurrency($remaining) . '</span>';
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $todaySpend = isset($data['insights']['campaign'][$campaign['id']]['today']['spend']) 
                                                ? (float)$data['insights']['campaign'][$campaign['id']]['today']['spend'] 
                                                : 0;
                                            $dailyBudget = isset($campaign['daily_budget']) ? $campaign['daily_budget'] / 100 : 0;
                                            $unallocated = $dailyBudget - $todaySpend;
                                            if ($dailyBudget > 0 && strtolower($campaign['status']) === 'active') {
                                                $color = $unallocated > 0 ? '#28a745' : '#dc3545';
                                                echo '<span style="color: ' . $color . '; font-weight: 600;">' . formatCurrency($unallocated) . '</span>';
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
                                            <a href="https://www.facebook.com/ads/manager/account/campaigns/?act=<?php echo urlencode(str_replace('act_', '', $activeAccount['account_id'])); ?>&selected_ad_ids=<?php echo urlencode($ad['id']); ?>" 
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

        function switchAccount(accountId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'manage_accounts.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'set_active';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'account_id';
            idInput.value = accountId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
