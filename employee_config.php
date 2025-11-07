<?php

$employee_map = [
    'act_12345' => 'Employee A',
    'act_67890' => 'Employee B',
    'act_11111' => 'John Smith',
    'act_22222' => 'Sarah Johnson',
    'act_33333' => 'Mike Davis'
];

function getEmployeeByAccountId($accountId) {
    global $employee_map;
    
    $cleanId = str_replace('act_', '', $accountId);
    $withPrefix = 'act_' . $cleanId;
    
    if (isset($employee_map[$withPrefix])) {
        return $employee_map[$withPrefix];
    }
    
    if (isset($employee_map[$accountId])) {
        return $employee_map[$accountId];
    }
    
    return 'Unknown Employee';
}

function getAllEmployees() {
    global $employee_map;
    return array_unique(array_values($employee_map));
}

function getAccountIdsByEmployee($employeeName) {
    global $employee_map;
    $accountIds = [];
    
    foreach ($employee_map as $accountId => $employee) {
        if ($employee === $employeeName) {
            $accountIds[] = $accountId;
        }
    }
    
    return $accountIds;
}
