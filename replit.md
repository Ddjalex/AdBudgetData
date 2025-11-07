# Facebook Ads Budget Tracker

## Overview
A PHP web application for tracking and analyzing Facebook advertising campaigns, ad sets, and ads. The application integrates with the Facebook Marketing API to fetch real-time performance data and budget information.

## Project Structure
```
├── index.php          # Main dashboard with tabbed interface
├── config.php         # API configuration and credentials
├── api.php           # Facebook Marketing API integration class
├── style.css         # Styling for the dashboard
└── replit.md         # Project documentation
```

## Features
- **Campaign Tracking**: View all campaigns with budget, spend, and status information
- **Ad Set Analysis**: Track daily and total spending for each ad set with performance metrics
- **Ad-Level Details**: Monitor individual ad performance with today's spend and creative information
- **Tabbed Interface**: Easy navigation between Campaigns, Ad Sets, and Ads
- **Real-time Data**: Fetches live data from Facebook Marketing API
- **Clean Dashboard**: Table-based layout for quick data scanning

## Technology Stack
- **Backend**: PHP 8.4
- **Frontend**: HTML5, CSS3
- **API**: Facebook Marketing API v21.0
- **HTTP Client**: cURL

## Setup Instructions

### 1. Facebook App Configuration
1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app or use an existing one
3. Add the Marketing API product to your app
4. Generate an access token with the following permissions:
   - `ads_read`
   - `ads_management`
5. Note your App ID, App Secret, and Access Token

### 2. Configure the Application
Edit `config.php` and replace the placeholder values:

```php
define('FB_APP_ID', 'YOUR_APP_ID_HERE');
define('FB_APP_SECRET', 'YOUR_APP_SECRET_HERE');
define('FB_ACCESS_TOKEN', 'YOUR_ACCESS_TOKEN_HERE');
define('FB_AD_ACCOUNT_ID', 'act_YOUR_AD_ACCOUNT_ID');
```

**Important**: Your Ad Account ID should include the `act_` prefix (e.g., `act_1234567890`).

### 3. Access the Dashboard
Once configured, the application will automatically fetch and display your Facebook Ads data in three categories:
- **Campaigns**: Overview of all campaigns with budgets and total spend
- **Ad Sets**: Daily and lifetime spend with performance metrics
- **Ads**: Individual ad performance with today's spend

## API Integration Details

### FacebookAdsAPI Class (api.php)
The application uses a custom PHP class to interact with the Facebook Marketing API:

- `getCampaigns()`: Fetches all campaigns with budget and spend data
- `getAdSets($campaignId)`: Retrieves ad sets for a specific campaign or all ad sets
- `getAds($adSetId)`: Gets ads for a specific ad set or all ads
- `getAdInsights($adId, $datePreset)`: Fetches performance insights for an ad
- `getAdSetInsights($adSetId, $datePreset)`: Retrieves insights for an ad set
- `getAllData()`: Comprehensive method that fetches all data in one call

### Data Retrieved
- **Campaign Level**: ID, name, status, objective, budgets, spend, budget remaining
- **Ad Set Level**: ID, name, status, budgets, spend, impressions, clicks
- **Ad Level**: ID, name, status, creative information, daily spend metrics

## User Preferences
- Pure PHP implementation without frameworks
- Clean, scannable table-based interface
- Focus on budget tracking and daily spend analysis
- Hierarchical data organization (Campaign → Ad Set → Ad)

## Recent Changes
- **2025-11-07**: UI Redesign to Facebook Ads Manager style
  - Redesigned entire interface with modern white card-based layout
  - Changed background from gradient to professional light grey (#f0f2f5)
  - Added summary cards showing Total Lifetime Spend, Today's Spend, Total Campaigns, and Active Campaigns
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
- The `.gitignore` file is configured to exclude sensitive files
- Access tokens should be kept secure and rotated regularly
- Consider using environment variables for production deployments
