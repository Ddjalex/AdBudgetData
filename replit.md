# Facebook Ads Budget Tracker

## Overview
A comprehensive PHP web application for tracking and analyzing Facebook advertising campaigns across **multiple Ad Accounts**. The application integrates with the Facebook Marketing API to automatically discover ad accounts and fetch real-time performance data, budget information, and provides detailed financial tracking with visual indicators.

## Project Structure
```
├── index.php              # Main dashboard with multi-account support
├── manage_accounts.php    # Ad Account auto-discovery interface
├── settings.php           # API credentials configuration
├── config.php             # API configuration and credentials
├── api.php               # Facebook Marketing API integration class
├── account_manager.php   # Account management logic
├── accounts.json         # Multi-account storage (auto-generated)
├── style.css             # Styling for the dashboard
└── replit.md             # Project documentation
```

## Key Features

### Automatic Multi-Account Discovery
- **Auto-Discovery**: Automatically discovers all Ad Accounts linked to your Facebook Access Token using the `/me/adaccounts` API endpoint
- **Track 7+ Ad Accounts**: Manage and monitor unlimited Facebook Ad Accounts from a single dashboard
- **One-Click Discovery**: Simply click "Discover Ad Accounts" to fetch all accessible accounts
- **Account Status Tracking**: Displays account status (Active, Disabled, Pending Review, etc.) with color-coded badges
- **Account Metadata**: Automatically retrieves account name, currency, timezone, and business information
- **Smart Syncing**: Preserves your active account selection when re-discovering accounts
- **Quick Account Switching**: Dropdown selector to switch between accounts instantly
- **Account-Specific Data**: All metrics and reports are scoped to the selected Ad Account

### Financial Tracking & KPIs
- **Total Budget Card**: View total allocated budget across all campaigns
- **Total Spend Card**: Track cumulative lifetime spend
- **Remaining Budget Card**: See available budget with color-coded indicators (green for positive, red for overspend)
- **Today's Spend Card**: Monitor daily spending across active campaigns
- **Budget Utilization Progress Bar**: Visual indicator showing budget usage percentage with color warnings:
  - Green: < 60% used
  - Blue: 60-79% used
  - Yellow: 80-89% used
  - Red: 90%+ used

### Campaign Tracking
- View all campaigns with budget, spend, and status information
- Daily and lifetime budget breakdowns
- Budget remaining calculations per campaign
- Campaign objectives and performance metrics

### Ad Set Analysis
- Track daily and total spending for each ad set
- Performance metrics: impressions, clicks
- Campaign hierarchy mapping
- Status monitoring

### Ad-Level Details
- Individual ad performance with today's spend
- Creative information and IDs
- Direct links to Facebook Ads Manager
- Granular performance tracking

### User Interface
- **Tabbed Navigation**: Easy switching between Campaigns, Ad Sets, and Ads views
- **Real-time Data**: Fetches live data from Facebook Marketing API
- **Clean Dashboard**: Professional Facebook Ads Manager-style layout
- **Responsive Design**: Works on desktop and mobile devices
- **Visual Indicators**: Color-coded status badges and budget warnings

## Technology Stack
- **Backend**: PHP 8.4
- **Frontend**: HTML5, CSS3
- **API**: Facebook Marketing API v21.0
- **HTTP Client**: cURL
- **Data Storage**: JSON file-based account management

## Setup Instructions

### 1. Configure API Credentials
1. Click the **Settings** button in the dashboard
2. Enter your Facebook API credentials:
   - App ID
   - App Secret
   - Access Token (with `ads_read` and `ads_management` permissions)
3. Save settings

To get your Facebook API credentials:
- Go to [Facebook Developers](https://developers.facebook.com/)
- Create a new app or use an existing one
- Add the Marketing API product to your app
- Generate an access token with required permissions

### 2. Auto-Discover Your Ad Accounts
1. Click the **Accounts** button in the dashboard
2. Click the green "Discover Ad Accounts" button
3. The system will automatically fetch all Ad Accounts linked to your access token
4. All accounts will be displayed with their status, currency, timezone, and business information

### 3. Switch Between Accounts
- Use the dropdown selector on the dashboard to switch between discovered accounts
- Or click "Set Active" on any account card in the Accounts page
- Only accounts with "Active" status can be set as the active account

## API Integration Details

### FacebookAdsAPI Class (api.php)
The application uses a custom PHP class to interact with the Facebook Marketing API:

- `__construct($accountId)`: Initialize API with specific account ID
- `discoverAdAccounts()`: Auto-discovers all Ad Accounts linked to the access token via `/me/adaccounts`
- `getCampaigns()`: Fetches all campaigns with budget and spend data
- `getAdSets($campaignId)`: Retrieves ad sets for a specific campaign
- `getAds($adSetId)`: Gets ads for a specific ad set
- `getAdInsights($adId, $datePreset)`: Fetches performance insights for an ad
- `getAdSetInsights($adSetId, $datePreset)`: Retrieves insights for an ad set
- `getCampaignInsights($campaignId, $datePreset)`: Gets campaign-level insights
- `getAllData()`: Comprehensive method that fetches all data in one call

### AccountManager Class (account_manager.php)
Handles multi-account storage and management:

- `getAccounts()`: Retrieve all configured accounts
- `getActiveAccount()`: Get the currently active account
- `addAccount($name, $accountId)`: Add a new account (legacy, now replaced by auto-discovery)
- `setActiveAccount($id)`: Switch to a different account
- `deleteAccount($id)`: Remove an account
- `syncDiscoveredAccounts($discoveredAccounts)`: Sync auto-discovered accounts from API

### Data Retrieved
- **Account Level**: ID, name, status, currency, timezone, business name (via auto-discovery)
- **Campaign Level**: ID, name, status, objective, budgets, spend, budget remaining
- **Ad Set Level**: ID, name, status, budgets, spend, impressions, clicks
- **Ad Level**: ID, name, status, creative information, daily spend metrics
- **Insights**: Spend, impressions, clicks, CPC, CPM, CTR for today and lifetime

## Usage Guide

### Auto-Discovering Accounts
1. Navigate to the Accounts page
2. Click "Discover Ad Accounts"
3. The system automatically fetches all accounts from Facebook
4. View account status, currency, and business information
5. Your previously active account selection is preserved

### Switching Between Accounts
1. Use the dropdown selector at the top of the dashboard
2. Or click "Set Active" on any account in the Accounts page
3. The page will reload with data for the selected account

### Monitoring Budget Health
- **Green Remaining Budget**: You're within budget limits
- **Red Remaining Budget**: You've exceeded your allocated budget
- **Progress Bar Color**:
  - Green: Healthy usage (< 60%)
  - Blue: Moderate usage (60-79%)
  - Yellow: High usage (80-89%)
  - Red: Critical usage (90%+)

### Viewing Detailed Reports
1. Use the tabs (Campaigns, Ad Sets, Ads) to navigate between different levels
2. Each table shows relevant metrics and financial data
3. Click on Ad IDs to view them directly in Facebook Ads Manager

### Account Status Meanings
- **Active (Green)**: Account is fully operational and can be used
- **Disabled (Red)**: Account has been disabled and cannot be used
- **Pending Review (Blue)**: Account is under review by Facebook
- **Unsettled (Yellow)**: Account has outstanding payment issues
- **Closed (Grey)**: Account has been permanently closed

## User Preferences
- Pure PHP implementation without heavy frameworks
- Clean, professional interface inspired by Facebook Ads Manager
- Focus on financial tracking and budget management
- Multi-account support for agencies and businesses
- Hierarchical data organization (Campaign → Ad Set → Ad)
- Automatic account discovery instead of manual entry

## Recent Changes
- **2025-11-07**: Automatic Ad Account Discovery Implementation (MAJOR UPDATE)
  - **REMOVED** manual Ad Account entry form - no longer needed
  - **ADDED** automatic account discovery via `/me/adaccounts` Facebook API endpoint
  - **ADDED** `discoverAdAccounts()` method to FacebookAdsAPI class
  - **ADDED** `syncDiscoveredAccounts()` method to AccountManager class
  - **UPDATED** manage_accounts.php with auto-discovery UI and "Discover Ad Accounts" button
  - **ADDED** account status badges (Active, Disabled, Pending Review, etc.) with color coding
  - **ADDED** automatic retrieval of account metadata (currency, timezone, business name)
  - **IMPROVED** account management with smart syncing that preserves active account selection
  - **ENHANCED** UI with informational sections explaining auto-discovery process
  - **ADDED** account status filtering - only Active accounts can be set as active
  
- **2025-11-07**: Multi-Account Support & Enhanced Financial Tracking
  - Implemented multi-account management system supporting 7+ accounts
  - Added account management interface with add/remove/switch functionality
  - Created KPI metric cards for Total Budget, Total Spend, Remaining Budget, Today's Spend
  - Implemented visual budget utilization progress bar with color indicators
  - Added account selector dropdown for quick switching
  - Created settings page with credential input forms
  - Improved budget calculations and remaining budget tracking
  - Added color-coded warnings for budget thresholds
  - Updated API to support account-specific data fetching
  
- **2025-11-07**: UI Redesign to Facebook Ads Manager style
  - Redesigned entire interface with modern white card-based layout
  - Changed background from gradient to professional light grey (#f0f2f5)
  - Added summary cards showing campaign statistics
  - Implemented Facebook-style typography and spacing
  - Updated table styling with cleaner borders and hover effects
  - Removed all emojis for professional appearance
  - Added colored border-left accents to cards
  - Improved responsive design for mobile devices
  
- **2025-11-07**: Initial project setup with PHP 8.4
  - Created core file structure
  - Implemented Facebook Marketing API integration
  - Built tabbed dashboard interface
  - Added real-time data fetching for campaigns, ad sets, and ads
  - Implemented today's spend tracking feature

## Security Notes
- Never commit `config.php` with real credentials to version control
- The `.gitignore` file is configured to exclude `config.php` and `accounts.json`
- Access tokens should be kept secure and rotated regularly
- Account data is stored locally in JSON format
- Consider using Replit Secrets for production deployments

## Troubleshooting

### API Errors During Discovery
- Verify your access token has `ads_read` and `ads_management` permissions
- Ensure your access token hasn't expired (Facebook tokens expire regularly)
- Check that you have access to at least one Ad Account in Facebook Business Settings
- Verify your App ID and App Secret are correct in Settings

### No Accounts Discovered
- Confirm your access token is associated with a user or Business Manager that has Ad Accounts
- Verify you have the correct permissions on the Ad Accounts
- Check that your Facebook app has the Marketing API product enabled

### Account Not Switching
- Only accounts with "Active" status can be set as active
- Clear your browser cache and try again
- Verify the account exists in the Manage Accounts page

### Discovery Button Not Working
- Check your API credentials in the Settings page
- Verify your access token has not expired
- Look for error messages displayed on the page after clicking "Discover Ad Accounts"
