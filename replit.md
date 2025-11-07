# Facebook Ads Budget Tracker

## Overview
A comprehensive PHP web application for tracking and analyzing Facebook advertising campaigns across **multiple Ad Accounts**. The application integrates with the Facebook Marketing API to fetch real-time performance data and budget information for manually configured ad accounts, providing detailed financial tracking with visual indicators.

## Project Structure
```
├── index.php                  # Main dashboard with multi-account support
├── employee_productivity.php  # Employee productivity and creation metrics tracker
├── manage_accounts.php        # Ad Account manual entry and management interface
├── settings.php              # API credentials configuration
├── config.php                # API configuration and credentials
├── api.php                   # Facebook Marketing API integration class
├── account_manager.php       # Account management logic
├── employee_config.php       # Employee mapping configuration
├── time_filter.php           # Time range filtering helper functions
├── accounts.json             # Multi-account storage (auto-generated)
├── style.css                 # Styling for the dashboard
└── replit.md                 # Project documentation
```

## Key Features

### Manual Multi-Account Management
- **Manual Account Entry**: Add multiple Ad Accounts by entering just the Account ID
- **Track Unlimited Ad Accounts**: Manage and monitor unlimited Facebook Ad Accounts from a single dashboard
- **Simple Account Addition**: Enter your Ad Account ID to add new accounts (names are auto-generated)
- **Quick Account Switching**: Dropdown selector to switch between accounts instantly
- **Account-Specific Data**: All metrics and reports are scoped to the selected Ad Account
- **Easy Account Management**: Set active account, remove accounts, and manage all accounts from one interface

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
- **Campaign Start & End Dates**: See when campaigns started and when they're scheduled to end
- **Today's Unallocated Budget**: Monitor how much of today's daily budget is still available per campaign
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

### Date Range Filtering
- **Optional Date Filtering**: Filter lifetime metrics by custom date ranges
- **Smart Filtering**: Custom date ranges only apply to lifetime/total metrics, while today's metrics always show current day data
- **Flexible Analysis**: Leave dates empty to see all-time data, or specify a range for focused analysis

### Employee Productivity Tracker
- **NEW FEATURE**: Track employee creation metrics and allocated budgets
- **Employee Mapping**: Configure which employee manages which ad account
- **Time-Based Filtering**: Filter by Today, Yesterday, This Week, or Custom Date Range
- **Creation Metrics**: See how many Campaigns, Ad Sets, and Ads each employee created
- **Allocated Budget Tracking**: View total budget allocated (not spent) by each employee
- **Comprehensive Reporting**: Detailed table showing productivity metrics per employee
- **Multi-Account Support**: Aggregates data across multiple ad accounts per employee

### User Interface
- **Tabbed Navigation**: Easy switching between Campaigns, Ad Sets, and Ads views
- **Employee Productivity Dashboard**: Dedicated page for tracking employee creation metrics
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

### 2. Add Your Ad Accounts Manually
1. Click the **Accounts** button in the dashboard
2. Find your Ad Account ID in Facebook Business Settings
3. Enter the Account ID in the form (account name will be auto-generated)
4. Click "Add Account" to save the account

### 3. Switch Between Accounts
- Use the dropdown selector on the dashboard to switch between your manually added accounts
- Or click "Set Active" on any account card in the Accounts page

## API Integration Details

### FacebookAdsAPI Class (api.php)
The application uses a custom PHP class to interact with the Facebook Marketing API:

- `__construct($accountId)`: Initialize API with specific account ID
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
- `addAccount($accountId)`: Add a new account manually (name auto-generated)
- `setActiveAccount($id)`: Switch to a different account
- `deleteAccount($id)`: Remove an account

### Data Retrieved
- **Account Level**: ID, name (manually entered)
- **Campaign Level**: ID, name, status, objective, budgets, spend, budget remaining
- **Ad Set Level**: ID, name, status, budgets, spend, impressions, clicks
- **Ad Level**: ID, name, status, creative information, daily spend metrics
- **Insights**: Spend, impressions, clicks, CPC, CPM, CTR for today and lifetime

## Usage Guide

### Adding Accounts Manually
1. Navigate to the Accounts page
2. Enter your Ad Account ID (numbers only)
3. Click "Add Account" to save
4. The account will be added with an auto-generated name (e.g., "Ad Account 1234567890")

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

## User Preferences
- Pure PHP implementation without heavy frameworks
- Clean, professional interface inspired by Facebook Ads Manager
- Focus on financial tracking and budget management
- Multi-account support for agencies and businesses
- Hierarchical data organization (Campaign → Ad Set → Ad)
- Manual account entry for full control and simplicity

## Recent Changes
- **2025-11-07**: Employee Productivity and Allocated Budget Tracker Feature
  - **CREATED** new employee productivity tracking page (employee_productivity.php)
  - **IMPLEMENTED** employee mapping system to track who created which entities
  - **ADDED** time-based filtering: Today, Yesterday, This Week, Custom Date Range
  - **CREATED** new API methods: getCampaignsCreatedInRange, getAdSetsCreatedInRange, getAdsCreatedInRange
  - **FOCUSED** on creation metrics and allocated budgets (not spend)
  - **IMPLEMENTED** comprehensive reporting table showing employee productivity
  - **ADDED** navigation link from main dashboard to Employee Productivity page
  - **CREATED** employee_config.php for employee-to-account mapping
  - **CREATED** time_filter.php for date range calculation helpers
  - **FIXED** security issue preventing placeholder account IDs from displaying in UI

- **2025-11-07**: Advanced Date Range Filtering & Enhanced Campaign Tracking
  - **IMPLEMENTED** optional date range filtering for insights data retrieval
  - **ADDED** Start Date and End Date input fields to the data loading form
  - **SMART FILTERING**: Custom date ranges only apply to lifetime/total metrics; today's metrics always show current day data
  - **ADDED** START DATE column to Campaigns table showing when each campaign started
  - **ADDED** END DATE column to Campaigns table showing when campaigns are scheduled to end (or "Continuous" if no end date)
  - **ADDED** TODAY'S UNALLOCATED BUDGET column to Campaigns table showing remaining daily budget for active campaigns
  - **UPDATED** api.php to fetch start_time and stop_time fields for campaigns
  - **UPDATED** all insights methods to conditionally apply custom date ranges only to "lifetime" presets
  - **CREATED** formatDate() helper function for consistent date display (e.g., "Nov 07, 2025")
  - **VERIFIED** tab structure correctly separates Campaigns, Ad Sets, and Ads data without mixing

- **2025-11-07**: Switched to Manual-Only Multi-Account Management (CRITICAL REVISION)
  - **REMOVED** all auto-discovery logic and UI from the application
  - **DELETED** `discoverAdAccounts()` method from FacebookAdsAPI class
  - **DELETED** `syncDiscoveredAccounts()` method from AccountManager class
  - **REMOVED** "Discover Ad Accounts" button and auto-discovery UI from manage_accounts.php
  - **IMPLEMENTED** clean manual account entry form with only Account ID field (names auto-generated)
  - **UPDATED** `addAccount()` method to include default values for all account fields
  - **SIMPLIFIED** account management to manual entry only
  - **IMPROVED** user control over account management with straightforward manual addition
  
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

### Cannot Add Account
- Verify your Ad Account ID is correct (numbers only, e.g., 1234567890)
- Check that you haven't already added this account
- Make sure the Account ID field is filled in

### API Errors When Loading Data
- Verify your access token has `ads_read` and `ads_management` permissions
- Ensure your access token hasn't expired (Facebook tokens expire regularly)
- Check that the Ad Account ID you entered is correct and you have access to it
- Verify your App ID and App Secret are correct in Settings

### Account Not Switching
- Clear your browser cache and try again
- Verify the account exists in the Manage Accounts page
- Try removing and re-adding the account if issues persist
