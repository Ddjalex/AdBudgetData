<?php

function getTimeRangeForFilter($filterType, $startDate = null, $endDate = null) {
    $timezone = new DateTimeZone('UTC');
    $now = new DateTime('now', $timezone);
    
    switch ($filterType) {
        case 'today':
            $since = new DateTime('today', $timezone);
            $until = clone $now;
            break;
            
        case 'yesterday':
            $since = new DateTime('yesterday', $timezone);
            $until = new DateTime('today', $timezone);
            $until->modify('-1 second');
            break;
            
        case 'this_week':
            $since = new DateTime('monday this week', $timezone);
            $until = clone $now;
            break;
            
        case 'custom':
            if ($startDate && $endDate) {
                $since = new DateTime($startDate, $timezone);
                $until = new DateTime($endDate, $timezone);
                $until->setTime(23, 59, 59);
            } else {
                $since = new DateTime('30 days ago', $timezone);
                $until = clone $now;
            }
            break;
            
        default:
            $since = new DateTime('30 days ago', $timezone);
            $until = clone $now;
    }
    
    return [
        'since' => $since->format('Y-m-d'),
        'until' => $until->format('Y-m-d'),
        'since_timestamp' => $since->getTimestamp(),
        'until_timestamp' => $until->getTimestamp()
    ];
}

function formatTimeFilterLabel($filterType, $startDate = null, $endDate = null) {
    $range = getTimeRangeForFilter($filterType, $startDate, $endDate);
    
    switch ($filterType) {
        case 'today':
            return 'Today (' . date('M d, Y', $range['since_timestamp']) . ')';
        case 'yesterday':
            return 'Yesterday (' . date('M d, Y', $range['since_timestamp']) . ')';
        case 'this_week':
            return 'This Week (' . date('M d', $range['since_timestamp']) . ' - ' . date('M d, Y', $range['until_timestamp']) . ')';
        case 'custom':
            return 'Custom Range (' . date('M d', $range['since_timestamp']) . ' - ' . date('M d, Y', $range['until_timestamp']) . ')';
        default:
            return 'Last 30 Days';
    }
}
