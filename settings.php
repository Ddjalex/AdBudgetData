<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

require_once 'config.php';

$saved = isset($_GET['saved']) && $_GET['saved'] === 'true';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId = $_POST['app_id'] ?? '';
    $appSecret = $_POST['app_secret'] ?? '';
    $accessToken = $_POST['access_token'] ?? '';
    $adAccountId = $_POST['ad_account_id'] ?? '';
    
    if (!str_starts_with($adAccountId, 'act_') && !empty($adAccountId)) {
        $adAccountId = 'act_' . $adAccountId;
    }
    
    $configContent = "<?php\n\n";
    $configContent .= "define('FB_APP_ID', '" . addslashes($appId) . "');\n";
    $configContent .= "define('FB_APP_SECRET', '" . addslashes($appSecret) . "');\n";
    $configContent .= "define('FB_ACCESS_TOKEN', '" . addslashes($accessToken) . "');\n";
    $configContent .= "define('FB_AD_ACCOUNT_ID', '" . addslashes($adAccountId) . "');\n\n";
    $configContent .= "function getConfig() {\n";
    $configContent .= "    return [\n";
    $configContent .= "        'app_id' => FB_APP_ID,\n";
    $configContent .= "        'app_secret' => FB_APP_SECRET,\n";
    $configContent .= "        'access_token' => FB_ACCESS_TOKEN,\n";
    $configContent .= "        'ad_account_id' => FB_AD_ACCOUNT_ID,\n";
    $configContent .= "        'api_base_url' => 'https://graph.facebook.com/v21.0'\n";
    $configContent .= "    ];\n";
    $configContent .= "}\n";
    
    file_put_contents('config.php', $configContent);
    
    header('Location: settings.php?saved=true');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Facebook Ads Budget Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-form {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            margin-right: 10px;
        }
        .btn-primary:hover {
            background: #166fe5;
        }
        .btn-secondary {
            background: #e4e6eb;
            color: #1c1e21;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary:hover {
            background: #d8dadf;
        }
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #1877f2;
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #1877f2;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>API Settings</h1>
            <p>Configure your Facebook Marketing API credentials</p>
        </div>

        <div class="settings-form">
            <?php if ($saved): ?>
                <div class="success-message">
                    <strong>Success!</strong> Your settings have been saved. <a href="index.php">Go to Dashboard</a>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <h3>How to get your Facebook API credentials:</h3>
                <ul>
                    <li>Go to <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a></li>
                    <li>Create a new app or select an existing one</li>
                    <li>Add the Marketing API product to your app</li>
                    <li>Generate an access token with <code>ads_read</code> and <code>ads_management</code> permissions</li>
                    <li>Find your Ad Account ID in <a href="https://business.facebook.com/settings/ad-accounts" target="_blank">Business Settings</a></li>
                </ul>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="app_id">App ID</label>
                    <input type="text" id="app_id" name="app_id" value="<?php echo htmlspecialchars(FB_APP_ID); ?>" required>
                    <small>Your Facebook App ID</small>
                </div>

                <div class="form-group">
                    <label for="app_secret">App Secret</label>
                    <input type="text" id="app_secret" name="app_secret" value="<?php echo htmlspecialchars(FB_APP_SECRET); ?>" required>
                    <small>Your Facebook App Secret</small>
                </div>

                <div class="form-group">
                    <label for="access_token">Access Token</label>
                    <input type="text" id="access_token" name="access_token" value="<?php echo htmlspecialchars(FB_ACCESS_TOKEN); ?>" required>
                    <small>Your Facebook Access Token with ads_read and ads_management permissions</small>
                </div>

                <div class="form-group">
                    <label for="ad_account_id">Primary Ad Account ID (Optional Fallback)</label>
                    <input type="text" id="ad_account_id" name="ad_account_id" value="<?php echo defined('FB_AD_ACCOUNT_ID') ? htmlspecialchars(str_replace('act_', '', FB_AD_ACCOUNT_ID)) : ''; ?>" placeholder="1234567890">
                    <small>Your Ad Account ID (the act_ prefix will be added automatically). This is used as a fallback when no account is selected from auto-discovery. <strong>Recommended:</strong> Use the "Manage Accounts" page to discover and manage multiple accounts.</small>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-primary">Save Settings</button>
                    <a href="index.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
