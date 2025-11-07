<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'account_manager.php';

$accountManager = new AccountManager();
$accounts = $accountManager->getAccounts();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
</head>
<body>
    <h1>Server is working!</h1>
    <p>Found <?php echo count($accounts); ?> ad accounts:</p>
    <ul>
    <?php foreach ($accounts as $account): ?>
        <li>
            <?php echo htmlspecialchars($account['name']); ?> 
            (<?php echo htmlspecialchars($account['account_id']); ?>)
            <?php echo $account['active'] ? '- ACTIVE' : ''; ?>
        </li>
    <?php endforeach; ?>
    </ul>
    <p><a href="/">Go to Dashboard</a></p>
</body>
</html>
