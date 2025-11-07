<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

require_once 'config.php';
require_once 'api.php';
require_once 'account_manager.php';
require_once 'employee_config.php';
require_once 'time_filter.php';

$accountManager = new AccountManager();
$allAccounts = $accountManager->getAccounts();

$errorMessage = null;
$productivityData = null;
$isConfigured = false;

$filterType = $_GET['filter'] ?? 'today';
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$timeRange = getTimeRangeForFilter($filterType, $startDate, $endDate);
$filterLabel = formatTimeFilterLabel($filterType, $startDate, $endDate);

if (FB_ACCESS_TOKEN !== 'YOUR_ACCESS_TOKEN_HERE' && !empty($allAccounts)) {
    $isConfigured = true;
    
    if (isset($_GET['load_data']) && $_GET['load_data'] === '1') {
        try {
            set_time_limit(90);
            $productivityData = [];
            
            foreach ($allAccounts as $account) {
                $employeeName = getEmployeeByAccountId($account['account_id']);
                $accountId = $account['account_id'];
                
                $productivityData[$accountId] = [
                    'employee_name' => $employeeName,
                    'account_id' => $accountId,
                    'account_name' => $account['name'],
                    'campaigns_created' => 0,
                    'adsets_created' => 0,
                    'ads_created' => 0,
                    'total_allocated_budget' => 0
                ];
                
                $api = new FacebookAdsAPI($accountId);
                
                $campaigns = $api->getCampaignsCreatedInRange($timeRange['since'], $timeRange['until']);
                if (!isset($campaigns['error'])) {
                    $activeCampaigns = array_filter($campaigns, function($campaign) {
                        return isset($campaign['effective_status']) && $campaign['effective_status'] === 'ACTIVE';
                    });
                    $productivityData[$accountId]['campaigns_created'] = count($activeCampaigns);
                }
                
                $adsets = $api->getAdSetsCreatedInRange($timeRange['since'], $timeRange['until']);
                if (!isset($adsets['error'])) {
                    $activeAdsets = array_filter($adsets, function($adset) {
                        return isset($adset['effective_status']) && $adset['effective_status'] === 'ACTIVE';
                    });
                    $productivityData[$accountId]['adsets_created'] = count($activeAdsets);
                    
                    foreach ($activeAdsets as $adset) {
                        $allocatedBudget = FacebookAdsAPI::calculateTotalAllocatedBudget($adset);
                        $productivityData[$accountId]['total_allocated_budget'] += $allocatedBudget;
                    }
                }
                
                $ads = $api->getAdsCreatedInRange($timeRange['since'], $timeRange['until']);
                if (!isset($ads['error'])) {
                    $activeAds = array_filter($ads, function($ad) {
                        return isset($ad['effective_status']) && $ad['effective_status'] === 'ACTIVE';
                    });
                    $productivityData[$accountId]['ads_created'] = count($activeAds);
                }
            }
            
        } catch (Exception $e) {
            $errorMessage = 'Error: ' . $e->getMessage();
            $productivityData = null;
        }
    }
}

function formatCurrency($amount) {
    return '$' . number_format((float)$amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Productivity Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-controls {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 180px;
        }
        .filter-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1c1e21;
        }
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            font-size: 14px;
        }
        .date-range-group {
            display: none;
            gap: 15px;
            flex-wrap: wrap;
        }
        .date-range-group.active {
            display: flex;
        }
        .productivity-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .productivity-table th {
            background: #f0f2f5;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #1c1e21;
        }
        .productivity-table td {
            padding: 15px;
            border-bottom: 1px solid #e4e6eb;
        }
        .metric-value {
            font-size: 18px;
            font-weight: 700;
            color: #1877f2;
        }
        .budget-value {
            font-size: 18px;
            font-weight: 700;
            color: #28a745;
        }
        .summary-header {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-header h2 {
            margin: 0 0 5px 0;
            color: #1c1e21;
        }
        .summary-header p {
            margin: 0;
            color: #65676b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Employee Productivity & Budget Tracker</h1>
            <p>Track employee creation metrics and allocated budgets</p>
            <div style="position: absolute; top: 30px; right: 30px; display: flex; gap: 10px; z-index: 1000;">
                <a href="index.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">Dashboard</a>
                <a href="manage_accounts.php" style="background: #42b72a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">Accounts</a>
                <a href="settings.php" style="background: #1877f2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">Settings</a>
            </div>
        </div>

        <?php if (!$isConfigured): ?>
            <div class="alert alert-warning">
                <strong>Configuration Required</strong><br>
                <?php if (empty($allAccounts)): ?>
                    Please add your Ad Accounts. <a href="manage_accounts.php" style="color: #856404; text-decoration: underline; font-weight: 600;">Click here to add accounts</a>
                <?php else: ?>
                    Please update your Facebook API credentials. <a href="settings.php" style="color: #856404; text-decoration: underline; font-weight: 600;">Click here to configure settings</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($isConfigured): ?>
            <div class="filter-section">
                <form method="GET" id="filterForm">
                    <input type="hidden" name="load_data" value="1">
                    <div class="filter-controls">
                        <div class="filter-group">
                            <label>Time Period</label>
                            <select name="filter" id="filterSelect" onchange="toggleDateRange()">
                                <option value="today" <?php echo $filterType === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo $filterType === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="this_week" <?php echo $filterType === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="custom" <?php echo $filterType === 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                            </select>
                        </div>
                        
                        <div id="dateRangeInputs" class="date-range-group <?php echo $filterType === 'custom' ? 'active' : ''; ?>">
                            <div class="filter-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate ?? ''); ?>">
                            </div>
                            <div class="filter-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit" id="loadBtn" onclick="showLoading()" style="background: #1877f2; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer;">
                                Load Productivity Data
                            </button>
                        </div>
                    </div>
                </form>
                
                <div id="loadingMessage" style="display: none; margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px;">
                    <strong>Loading productivity data...</strong><br>
                    Please wait, this may take up to 60 seconds.
                </div>
            </div>

            <script>
            function toggleDateRange() {
                const select = document.getElementById('filterSelect');
                const dateRange = document.getElementById('dateRangeInputs');
                if (select.value === 'custom') {
                    dateRange.classList.add('active');
                } else {
                    dateRange.classList.remove('active');
                }
            }
            
            function showLoading() {
                document.getElementById('loadBtn').style.opacity = '0.5';
                document.getElementById('loadBtn').style.pointerEvents = 'none';
                document.getElementById('loadingMessage').style.display = 'block';
            }
            </script>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <strong>Error</strong><br>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($productivityData !== null && !$errorMessage): ?>
            <div class="summary-header">
                <h2>Employee Productivity Report</h2>
                <p><?php echo $filterLabel; ?> | Tracking <?php echo count($productivityData); ?> ad account(s) | <strong>Showing Active Created Entities Only</strong></p>
            </div>

            <table class="productivity-table">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Ad Account ID</th>
                        <th>Campaigns Created</th>
                        <th>Ad Sets Created</th>
                        <th>Ads Created</th>
                        <th>Total Entities</th>
                        <th>Total Allocated Budget</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalCampaigns = 0;
                    $totalAdSets = 0;
                    $totalAds = 0;
                    $totalBudget = 0;
                    
                    foreach ($productivityData as $accountId => $data): 
                        $totalEntities = $data['adsets_created'] + $data['ads_created'];
                        $totalCampaigns += $data['campaigns_created'];
                        $totalAdSets += $data['adsets_created'];
                        $totalAds += $data['ads_created'];
                        $totalBudget += $data['total_allocated_budget'];
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($data['employee_name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars(str_replace('act_', '', $accountId)); ?></code></td>
                            <td><span class="metric-value"><?php echo $data['campaigns_created']; ?></span></td>
                            <td><span class="metric-value"><?php echo $data['adsets_created']; ?></span></td>
                            <td><span class="metric-value"><?php echo $data['ads_created']; ?></span></td>
                            <td><span class="metric-value"><?php echo $totalEntities; ?></span></td>
                            <td><span class="budget-value"><?php echo formatCurrency($data['total_allocated_budget']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (count($productivityData) > 1): ?>
                        <tr style="background: #f0f2f5; font-weight: 700;">
                            <td colspan="2">TOTAL</td>
                            <td><span class="metric-value"><?php echo $totalCampaigns; ?></span></td>
                            <td><span class="metric-value"><?php echo $totalAdSets; ?></span></td>
                            <td><span class="metric-value"><?php echo $totalAds; ?></span></td>
                            <td><span class="metric-value"><?php echo $totalAdSets + $totalAds; ?></span></td>
                            <td><span class="budget-value"><?php echo formatCurrency($totalBudget); ?></span></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
