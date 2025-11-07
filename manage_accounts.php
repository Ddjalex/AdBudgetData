<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

require_once 'config.php';
require_once 'api.php';
require_once 'account_manager.php';

$accountManager = new AccountManager();
$message = null;
$messageType = 'success';
$discoveryError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'discover':
                try {
                    $api = new FacebookAdsAPI();
                    $result = $api->discoverAdAccounts();
                    
                    if (isset($result['error'])) {
                        $discoveryError = $result['error'];
                        $messageType = 'error';
                    } elseif (isset($result['data'])) {
                        $syncResult = $accountManager->syncDiscoveredAccounts($result['data']);
                        $message = "Successfully discovered and synced {$syncResult['count']} Ad Account(s)!";
                        $messageType = 'success';
                    } else {
                        $message = 'No Ad Accounts found for this access token.';
                        $messageType = 'warning';
                    }
                } catch (Exception $e) {
                    $discoveryError = 'Error: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'set_active':
                $id = $_POST['account_id'] ?? '';
                if (!empty($id)) {
                    $result = $accountManager->setActiveAccount($id);
                    if ($result['success']) {
                        header('Location: index.php');
                        exit;
                    }
                }
                break;
        }
    }
}

$accounts = $accountManager->getAccounts();

function getAccountStatusBadge($status) {
    $statusLabels = [
        1 => 'Active',
        2 => 'Disabled',
        3 => 'Unsettled',
        7 => 'Pending Review',
        8 => 'Pending Closure',
        9 => 'Closed',
        100 => 'Pending Risk Review',
        101 => 'In Grace Period'
    ];
    
    $statusColors = [
        1 => '#28a745',
        2 => '#dc3545',
        3 => '#ffc107',
        7 => '#17a2b8',
        8 => '#dc3545',
        9 => '#6c757d',
        100 => '#ffc107',
        101 => '#ffc107'
    ];
    
    $label = $statusLabels[$status] ?? 'Unknown';
    $color = $statusColors[$status] ?? '#6c757d';
    
    return "<span style='background: {$color}; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;'>{$label}</span>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ad Accounts - Facebook Ads Budget Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .accounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .account-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        .account-card.active {
            border-left: 5px solid #28a745;
        }
        .account-card h3 {
            margin-top: 0;
            color: #1c1e21;
            margin-bottom: 10px;
        }
        .account-card .account-id {
            color: #65676b;
            font-size: 14px;
            margin: 8px 0;
        }
        .account-card .account-info {
            color: #65676b;
            font-size: 13px;
            margin: 6px 0;
        }
        .account-card .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .account-card .badge.active {
            background: #d4edda;
            color: #155724;
        }
        .account-card .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-activate {
            background: #1877f2;
            color: white;
        }
        .btn-discover {
            background: #42b72a;
            color: white;
            padding: 14px 28px;
            font-size: 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-discover:hover {
            background: #36a420;
        }
        .discovery-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        .discovery-section p {
            color: #65676b;
            margin: 15px 0;
            font-size: 15px;
        }
        .message {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #1877f2;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #1877f2;
            font-size: 16px;
        }
        .info-box ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: #1c1e21;
        }
        .info-box li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Ad Accounts</h1>
            <p>Automatically discover and switch between your Facebook Ad Accounts</p>
            <a href="index.php" style="position: absolute; top: 30px; right: 30px; background: #1877f2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">← Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($discoveryError): ?>
            <div class="message error">
                <strong>Discovery Error:</strong> <?php echo htmlspecialchars($discoveryError); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>How Auto-Discovery Works</h3>
            <ul>
                <li>Click the "Discover Ad Accounts" button to automatically fetch all Ad Accounts linked to your access token</li>
                <li>The system will find all accounts you have permission to access</li>
                <li>Your current active account selection will be preserved during discovery</li>
                <li>Account status, currency, and timezone information will be automatically retrieved</li>
            </ul>
        </div>

        <div class="discovery-section">
            <h2 style="margin-top: 0;">Auto-Discover Your Ad Accounts</h2>
            <p>Click the button below to automatically discover all Ad Accounts associated with your Facebook access token</p>
            <form method="POST">
                <input type="hidden" name="action" value="discover">
                <button type="submit" class="btn-discover">
                    🔍 Discover Ad Accounts
                </button>
            </form>
            <p style="font-size: 13px; color: #8a8d91; margin-top: 20px;">
                This will fetch all Ad Accounts you have access to via your configured Access Token
            </p>
        </div>

        <h2>Your Ad Accounts</h2>
        
        <?php if (empty($accounts)): ?>
            <div class="alert alert-warning">
                No ad accounts discovered yet. Click "Discover Ad Accounts" above to automatically find your accounts.
            </div>
        <?php else: ?>
            <div class="accounts-grid">
                <?php foreach ($accounts as $account): ?>
                    <div class="account-card <?php echo $account['active'] ? 'active' : ''; ?>">
                        <?php if ($account['active']): ?>
                            <span class="badge active">Active</span>
                        <?php endif; ?>
                        
                        <h3><?php echo htmlspecialchars($account['name']); ?></h3>
                        
                        <div class="account-id">
                            <strong>Account ID:</strong> <?php echo htmlspecialchars($account['account_id']); ?>
                        </div>
                        
                        <div class="account-info">
                            <strong>Status:</strong> <?php echo getAccountStatusBadge($account['account_status']); ?>
                        </div>
                        
                        <?php if (!empty($account['currency'])): ?>
                            <div class="account-info">
                                <strong>Currency:</strong> <?php echo htmlspecialchars($account['currency']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($account['business_name'])): ?>
                            <div class="account-info">
                                <strong>Business:</strong> <?php echo htmlspecialchars($account['business_name']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($account['timezone_name'])): ?>
                            <div class="account-info">
                                <strong>Timezone:</strong> <?php echo htmlspecialchars($account['timezone_name']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="actions">
                            <?php if (!$account['active'] && $account['account_status'] == 1): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="set_active">
                                    <input type="hidden" name="account_id" value="<?php echo htmlspecialchars($account['id']); ?>">
                                    <button type="submit" class="btn-small btn-activate">Set Active</button>
                                </form>
                            <?php elseif ($account['account_status'] != 1): ?>
                                <span style="color: #dc3545; font-size: 12px;">Account cannot be activated (not active status)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
