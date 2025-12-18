<?php

// Simulate the import
$enable_rp = 1; // Assume enabled

$imported_data = [
    ['1', '', 'John', '', 'Doe', '', '', '', '0', '', 'days', '0', '', '919876543210', '', '', '', '', '', '', '', '', '', '', '', '', '', '100'],
    ['2', '', 'Jane', '', 'Smith', '', '', '', '0', '', 'days', '0', '', '919876543211', '', '', '', '', '', '', '', '', '', '', '', '', '', '50'],
    ['3', '', 'Alex', '', 'Brown', '', '', '', '0', '', 'days', '0', '', '919876543212', '', '', '', '', '', '', '', '', '', '', '', '', '', '75'],
    ['1', '', 'Invalid', '', 'Test', '', '', '', '0', '', 'days', '0', '', '919876543213', '', '', '', '', '', '', '', '', '', '', '', '', '-10'],
    ['1', '', 'Invalid2', '', 'Test', '', '', '', '0', '', 'days', '0', '', '919876543214', '', '', '', '', '', '', '', '', '', '', '', '', 'abc']
];

$formated_data = [];
$is_valid = true;
$error_msg = '';

foreach ($imported_data as $key => $value) {
    $row_no = $key + 1;
    $contact_array = [];

    //Check if 28 no. of columns exists
    if (count($value) != 28) {
        $is_valid = false;
        $error_msg = 'Number of columns mismatch';
        break;
    }

    //Check contact type
    $contact_type = '';
    $contact_types = [
        1 => 'customer',
        2 => 'supplier',
        3 => 'both',
    ];
    if (! empty($value[0])) {
        $contact_type = strtolower(trim($value[0]));
        if (in_array($contact_type, [1, 2, 3])) {
            $contact_array['type'] = $contact_types[$contact_type];
            $contact_type = $contact_types[$contact_type];
        } else {
            $is_valid = false;
            $error_msg = "Invalid contact type $contact_type in row no. $row_no";
            break;
        }
    } else {
        $is_valid = false;
        $error_msg = "Contact type is required in row no. $row_no";
        break;
    }

    $contact_array['prefix'] = $value[1];
    if (! empty($value[2])) {
        $contact_array['first_name'] = $value[2];
    } else {
        $is_valid = false;
        $error_msg = "First name is required in row no. $row_no";
        break;
    }
    $contact_array['middle_name'] = $value[3];
    $contact_array['last_name'] = $value[4];
    $contact_array['name'] = implode(' ', [$contact_array['prefix'], $contact_array['first_name'], $contact_array['middle_name'], $contact_array['last_name']]);

    if (! empty(trim($value[5]))) {
        $contact_array['supplier_business_name'] = $value[5];
    }

    if (in_array($contact_type, ['supplier', 'both'])) {
        if (trim($value[9]) != '') {
            $contact_array['pay_term_number'] = trim($value[9]);
        } else {
            $is_valid = false;
            $error_msg = "Pay term is required in row no. $row_no";
            break;
        }

        $pay_term_type = strtolower(trim($value[10]));
        if (in_array($pay_term_type, ['days', 'months'])) {
            $contact_array['pay_term_type'] = $pay_term_type;
        } else {
            $is_valid = false;
            $error_msg = "Pay term period is required in row no. $row_no";
            break;
        }
    }

    if (! empty(trim($value[6]))) {
        // Assume no duplicate for test
        $contact_array['contact_id'] = $value[6];
    }

    if (! empty(trim($value[7]))) {
        $contact_array['tax_number'] = $value[7];
    }

    if (! empty(trim($value[8])) && $value[8] != 0) {
        $contact_array['opening_balance'] = trim($value[8]);
    }

    if (trim($value[11]) != '' && in_array($contact_type, ['customer', 'both'])) {
        $contact_array['credit_limit'] = trim($value[11]);
    }

    if (! empty(trim($value[12]))) {
        if (filter_var(trim($value[12]), FILTER_VALIDATE_EMAIL)) {
            $contact_array['email'] = $value[12];
        } else {
            $is_valid = false;
            $error_msg = "Invalid email id in row no. $row_no";
            break;
        }
    }

    if (! empty(trim($value[13]))) {
        $contact_array['mobile'] = $value[13];
    } else {
        $is_valid = false;
        $error_msg = "Mobile number is required in row no. $row_no";
        break;
    }

    $contact_array['alternate_number'] = $value[14];
    $contact_array['landline'] = $value[15];
    $contact_array['city'] = $value[16];
    $contact_array['state'] = $value[17];
    $contact_array['country'] = $value[18];
    $contact_array['address_line_1'] = $value[19];
    $contact_array['address_line_2'] = $value[20];
    $contact_array['zip_code'] = $value[21];
    $contact_array['dob'] = $value[22];
    $contact_array['custom_field1'] = $value[23];
    $contact_array['custom_field2'] = $value[24];
    $contact_array['custom_field3'] = $value[25];
    $contact_array['custom_field4'] = $value[26];

    //Reward points validation and setting
    echo "Row $row_no: contact_type=$contact_type, enable_rp=$enable_rp, reward_points_raw=" . (isset($value[27]) ? $value[27] : 'not set') . "\n";
    if ($enable_rp == 1 && in_array($contact_type, ['customer', 'both'])) {
        $reward_points = trim($value[27]);
        echo "Row $row_no: reward_points_trimmed=$reward_points, is_numeric=" . is_numeric($reward_points) . ", >=0=" . ($reward_points >= 0) . "\n";
        if (!is_numeric($reward_points) || $reward_points < 0) {
            $is_valid = false;
            $error_msg = "Invalid reward points in row no. $row_no";
            break;
        }
        $contact_array['total_rp'] = $reward_points;
        echo "Row $row_no: total_rp set to $reward_points\n";
    } else {
        $contact_array['total_rp'] = 0;
        echo "Row $row_no: total_rp set to 0 (not customer or rp disabled)\n";
    }

    $formated_data[] = $contact_array;
}

if (! $is_valid) {
    echo "Error: $error_msg\n";
} else {
    echo "All rows valid. Formated data:\n";
    print_r($formated_data);
}