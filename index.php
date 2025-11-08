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
                if (strpos($data['error'], 'User request limit reached') !== false) {
                    $errorMessage = "⚠️ Facebook API Rate Limit Reached! Please wait 15-30 minutes before trying again. Facebook limits how many requests you can make per hour. Try selecting a narrower time period (Today, Yesterday, This Week) instead of All Time to reduce the number of API calls.";
                } else {
                    $errorMessage = $data['error'];
                }
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

        <?php if ($isConfigured): ?>
            <div style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <form method="GET" id="filterForm">
                    <input type="hidden" name="load_data" value="1">
                    <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 180px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #1c1e21;">Time Period</label>
                            <select name="filter" id="filterSelect" onchange="toggleDateRange()" style="width: 100%; padding: 10px; border: 1px solid #dddfe2; border-radius: 6px; font-size: 14px;">
                                <option value="today" <?php echo $filterType === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo $filterType === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="this_week" <?php echo $filterType === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="this_month" <?php echo $filterType === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="custom" <?php echo $filterType === 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                            </select>
                        </div>
                        <div id="dateRangeInputs" style="display: <?php echo $filterType === 'custom' ? 'flex' : 'none'; ?>; flex: 2; min-width: 350px; gap: 15px;">
                            <div style="flex: 1;">
                                <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #1c1e21;">Start Date</label>
                                <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #dddfe2; border-radius: 6px;">
                            </div>
                            <div style="flex: 1;">
                                <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #1c1e21;">End Date</label>
                                <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #dddfe2; border-radius: 6px;">
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" id="loadDataBtn" style="background: #1877f2; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer;">
                                📊 Load Data
                            </button>
                            <button type="button" id="stopLoadBtn" onclick="stopLoading()" style="display: none; background: #dc3545; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer;">
                                ⛔ Stop Loading
                            </button>
                        </div>
                    </div>
                </form>
                <div id="loadingMessage" style="display: none; margin-top: 15px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px;">
                    <strong>⏳ Loading data from Facebook...</strong><br>
                    This may take up to 60 seconds. You can click "Stop Loading" to cancel and change the time period.
                </div>
            </div>
            <script>
            let loadingAborted = false;
            
            function toggleDateRange() {
                const select = document.getElementById('filterSelect');
                const dateRange = document.getElementById('dateRangeInputs');
                if (select.value === 'custom') {
                    dateRange.style.display = 'flex';
                } else {
                    dateRange.style.display = 'none';
                }
            }
            
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                loadingAborted = false;
                document.getElementById('loadDataBtn').style.display = 'none';
                document.getElementById('stopLoadBtn').style.display = 'inline-block';
                document.getElementById('loadingMessage').style.display = 'block';
            });
            
            function stopLoading() {
                loadingAborted = true;
                window.stop();
                
                document.getElementById('loadDataBtn').style.display = 'inline-block';
                document.getElementById('stopLoadBtn').style.display = 'none';
                document.getElementById('loadingMessage').style.display = 'none';
                
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('load_data');
                window.history.replaceState({}, '', currentUrl.pathname + currentUrl.search);
            }
            
            window.addEventListener('load', function() {
                document.getElementById('loadDataBtn').style.display = 'inline-block';
                document.getElementById('stopLoadBtn').style.display = 'none';
                document.getElementById('loadingMessage').style.display = 'none';
            });
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
                    
                    $campaignStatus = $campaign['effective_status'] ?? $campaign['status'] ?? '';
                    if (strtolower($campaignStatus) === 'active') {
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
                                        <td><?php echo getStatusBadge($campaign['effective_status'] ?? $campaign['status'] ?? ''); ?></td>
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
                                            $campaignStatus = $campaign['effective_status'] ?? $campaign['status'] ?? '';
                                            if ($dailyBudget > 0 && strtolower($campaignStatus) === 'active') {
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
                    <p style="margin: -10px 0 20px; color: #65676b; font-size: 14px;">
                        📊 <strong>Budget-focused view</strong> - Performance metrics (spend, impressions, clicks) have been removed to prevent API rate limits and ensure fast loading.
                    </p>
                    
                    <?php if (isset($data['adsets']) && count($data['adsets']) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ad Set Name</th>
                                    <th>Campaign</th>
                                    <th>Status</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Daily Budget</th>
                                    <th>Lifetime Budget</th>
                                    <th>Ad Set ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['adsets'] as $adset): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($adset['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($adset['campaign_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo getStatusBadge($adset['effective_status'] ?? $adset['status']); ?></td>
                                        <td><?php echo formatDate($adset['start_time'] ?? ''); ?></td>
                                        <td>
                                            <?php 
                                            if (isset($adset['end_time']) && !empty($adset['end_time'])) {
                                                echo formatDate($adset['end_time']);
                                            } else {
                                                echo '<span style="color: #28a745; font-weight: 600;">Continuous</span>';
                                            }
                                            ?>
                                        </td>
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
                                            <?php 
                                            if (isset($adset['lifetime_budget'])) {
                                                echo formatBudgetFromCents($adset['lifetime_budget']);
                                            } else {
                                                echo 'N/A';
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
                    <p style="margin: -10px 0 20px; color: #65676b; font-size: 14px;">
                        📊 <strong>Budget-focused view</strong> - Performance metrics (spend, impressions, clicks) have been removed to prevent API rate limits and ensure fast loading.
                    </p>
                    
                    <?php if (isset($data['ads']) && count($data['ads']) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ad Name</th>
                                    <th>Ad Set</th>
                                    <th>Campaign</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Ad ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['ads'] as $ad): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($ad['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($ad['adset_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($ad['campaign_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo getStatusBadge($ad['effective_status'] ?? $ad['status']); ?></td>
                                        <td><?php echo formatDate($ad['created_time'] ?? ''); ?></td>
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
