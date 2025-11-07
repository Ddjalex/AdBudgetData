<?php

require_once 'config.php';
require_once 'account_manager.php';

$accountManager = new AccountManager();
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['account_name'] ?? '';
                $accountId = $_POST['account_id'] ?? '';
                
                if (!empty($name) && !empty($accountId)) {
                    $result = $accountManager->addAccount($name, $accountId);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;
                
            case 'delete':
                $id = $_POST['account_id'] ?? '';
                if (!empty($id)) {
                    $result = $accountManager->deleteAccount($id);
                    $message = $result['success'] ? 'Account deleted successfully' : 'Failed to delete account';
                    $messageType = $result['success'] ? 'success' : 'error';
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
        }
        .account-card .account-id {
            color: #65676b;
            font-size: 14px;
            margin: 10px 0;
        }
        .account-card .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
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
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .add-account-form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr;
            gap: 15px;
            align-items: end;
        }
        .form-row input {
            padding: 10px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Ad Accounts</h1>
            <p>Add, remove, and switch between your Facebook Ad Accounts</p>
            <a href="index.php" style="position: absolute; top: 30px; right: 30px; background: #1877f2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">← Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="add-account-form">
            <h2 style="margin-top: 0;">Add New Ad Account</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Account Name</label>
                        <input type="text" name="account_name" placeholder="e.g., Main Business Account" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Ad Account ID</label>
                        <input type="text" name="account_id" placeholder="1234567890 or act_1234567890" required>
                    </div>
                    <button type="submit" class="btn-primary">Add Account</button>
                </div>
            </form>
        </div>

        <h2>Your Ad Accounts</h2>
        
        <?php if (empty($accounts)): ?>
            <div class="alert alert-warning">
                No ad accounts configured. Add your first account above.
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
                            
                            <?php if (count($accounts) > 1): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this account?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="account_id" value="<?php echo htmlspecialchars($account['id']); ?>">
                                    <button type="submit" class="btn-small btn-delete">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
