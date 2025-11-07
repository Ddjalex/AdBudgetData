<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

require_once 'config.php';
require_once 'account_manager.php';

$accountManager = new AccountManager();
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_account':
                $accountId = trim($_POST['account_id'] ?? '');
                
                if (empty($accountId)) {
                    $message = 'Ad Account ID is required.';
                    $messageType = 'error';
                } else {
                    $result = $accountManager->addAccount($accountId);
                    if ($result['success']) {
                        $message = 'Ad Account added successfully!';
                        $messageType = 'success';
                    } else {
                        $message = $result['message'];
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'set_active':
                $id = $_POST['account_id'] ?? '';
                if (!empty($id)) {
                    $result = $accountManager->setActiveAccount($id);
                    if ($result['success']) {
                        header('Location: index.php');
                        exit;
                    } else {
                        $message = 'Failed to set active account.';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete_account':
                $id = $_POST['account_id'] ?? '';
                if (!empty($id)) {
                    $result = $accountManager->deleteAccount($id);
                    if ($result['success']) {
                        $message = 'Account removed successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to remove account.';
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

$accounts = $accountManager->getAccounts();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ad Accounts - Facebook Ads Budget Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            max-width: 600px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1c1e21;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1877f2;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #65676b;
            font-size: 12px;
        }
        .btn-primary {
            background: #1877f2;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary:hover {
            background: #166fe5;
        }
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
        .btn-activate:hover {
            background: #166fe5;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
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
            <p>Add and manage multiple Facebook Ad Accounts</p>
            <a href="index.php" style="position: absolute; top: 30px; right: 30px; background: #1877f2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">← Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>How to Find Your Ad Account ID</h3>
            <ul>
                <li>Go to <a href="https://business.facebook.com/settings/ad-accounts" target="_blank">Facebook Business Settings</a></li>
                <li>Select the Ad Account you want to track</li>
                <li>The Account ID will be displayed in the format "1234567890" (numbers only)</li>
                <li>Enter this ID in the form below (account name will be auto-generated)</li>
            </ul>
        </div>

        <div class="form-container">
            <h2 style="margin-top: 0;">Add New Ad Account</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_account">
                
                <div class="form-group">
                    <label for="account_id">Ad Account ID</label>
                    <input type="text" id="account_id" name="account_id" placeholder="e.g., 1234567890" required>
                    <small>Your Facebook Ad Account ID (numbers only, the "act_" prefix will be added automatically)</small>
                </div>

                <button type="submit" class="btn-primary">Add Account</button>
            </form>
        </div>

        <h2>Your Ad Accounts</h2>
        
        <?php if (empty($accounts)): ?>
            <div class="alert alert-warning">
                No ad accounts added yet. Use the form above to add your first account.
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
                        
                        <div class="actions">
                            <?php if (!$account['active']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="set_active">
                                    <input type="hidden" name="account_id" value="<?php echo htmlspecialchars($account['id']); ?>">
                                    <button type="submit" class="btn-small btn-activate">Set Active</button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this account?');">
                                <input type="hidden" name="action" value="delete_account">
                                <input type="hidden" name="account_id" value="<?php echo htmlspecialchars($account['id']); ?>">
                                <button type="submit" class="btn-small btn-delete">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
