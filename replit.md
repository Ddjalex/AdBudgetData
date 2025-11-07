# Facebook Ads Budget Tracker

## Overview
A comprehensive PHP web application for tracking and analyzing Facebook advertising campaigns across **multiple Ad Accounts**. The application integrates with the Facebook Marketing API to fetch real-time performance data and budget information for manually configured ad accounts, providing detailed financial tracking with visual indicators.

## User Preferences
- Pure PHP implementation without heavy frameworks
- Clean, professional interface inspired by Facebook Ads Manager
- Focus on financial tracking and budget management
- Multi-account support for agencies and businesses
- Hierarchical data organization (Campaign → Ad Set → Ad)
- Manual account entry for full control and simplicity

## System Architecture

### Core Features
- **Manual Multi-Account Management**: Add, manage, and switch between unlimited Facebook Ad Accounts using only the Account ID.
- **Financial Tracking & KPIs**: Real-time display of Total Budget, Total Spend, Remaining Budget (with color-coded warnings), and Today's Spend. Includes a visual Budget Utilization Progress Bar.
- **Campaign Tracking**: View all campaigns with budget, spend, status, start/end dates, and today's unallocated budget.
- **Ad Set & Ad-Level Details**: Granular tracking of daily and total spending, performance metrics (impressions, clicks), and creative information.
- **Date Range Filtering**: Optional date filtering for lifetime metrics, while today's metrics remain current.
- **Employee Productivity Tracker**: Track employee creation metrics (Campaigns, Ad Sets, Ads created) and allocated budgets per Ad Account. **Filters to only show ACTIVE entities** (excludes PAUSED, DELETED statuses). Uses accurate duration-based budget calculation. Total Entities count focuses on Ad Sets + Ads created (campaigns tracked separately). Displays data individually per account, not aggregated.

### User Interface
- **Tabbed Navigation**: Easy switching between Campaigns, Ad Sets, Ads, and Employee Productivity views.
- **Clean Dashboard**: Professional, Facebook Ads Manager-style layout with white card-based design, light grey background, and visual indicators.
- **Responsive Design**: Optimized for desktop and mobile.

### Technical Implementation
- **Backend**: PHP 8.4
- **Frontend**: HTML5, CSS3
- **API Integration**: Custom PHP classes (`FacebookAdsAPI` for API interaction, `AccountManager` for account handling) to interface with Facebook Marketing API v21.0.
- **Data Retrieval**: Comprehensive methods to fetch account-level, campaign-level, ad set-level, and ad-level data, including insights (spend, impressions, clicks, CPC, CPM, CTR). Enhanced API calls retrieve budget-critical fields (start_time, end_time, effective_status, budget_remaining) for accurate allocation tracking. All entity types fetch `effective_status` field for filtering.
- **Budget Calculation**: Accurate duration-based allocated budget calculation using `calculateTotalAllocatedBudget()` function. Prioritizes lifetime_budget when available; otherwise calculates daily_budget × duration (days between start_time and end_time). For ongoing campaigns without end_time, calculates from start_time to current time. Includes robust type handling with floatval(), ensures minimum 1-day duration, properly converts from cents to USD (÷100), and rounds to 2 decimal places. Prevents double-counting by only summing Ad Set budgets (Ads inherit their parent Ad Set's budget).
- **Active Status Filtering**: Employee Productivity report filters all entities (Campaigns, Ad Sets, Ads) to only include those with `effective_status === 'ACTIVE'`. This ensures counts and budget calculations reflect only currently active advertising efforts, excluding paused or deleted entities.
- **Security**: `.gitignore` configured to exclude `config.php` and `accounts.json` for credential security.

## External Dependencies
- **API**: Facebook Marketing API v21.0
- **HTTP Client**: cURL
- **Data Storage**: JSON file-based for account management (`accounts.json`)