<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// API URL
// $api_url = 'https://Payformee.com/api/create-order';

// // Form-encoded payload data
// $post_data = [
//     'customer_mobile' => '8145344963',
//     'user_token' => 'ba7ded0202cd40d42dab6bfff66bd68b',
//     'amount' => '100',
//     'order_id' => '1',
//     'redirect_url' => 'https://Payformee.com',
//     'remark1' => 'testremark',
//     'remark2' => 'testremark2',
//     'route' => '2' // route 2 is for VIP users, route 1 is for normal users
// ];

// // Initialize cURL session
// $ch = curl_init($api_url);

// // Set cURL options
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data)); // to format POST data
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     'Content-Type: application/x-www-form-urlencoded'
// ]);

// // Execute the cURL session and capture the response
// $response = curl_exec($ch);
// // echo 'hi';
// // Check for cURL errors
// if (curl_errno($ch)) {
//     echo 'cURL Error: ' . curl_error($ch);
// } else {
//     // echo 'hi'.$response.'he';
//     echo $response;
// }

// // Close the cURL session
// curl_close($ch);


// API URL
$api_url = 'https://Payformee.com/api/check-order-status';

// Form-encoded payload data
$post_data = [
    'user_token' => 'ba7ded0202cd40d42dab6bfff66bd68b',
    'order_id' => '554'
];

// Initialize cURL session
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data)); // to format POST data
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

// Execute the cURL session and capture the response
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
} else {
    echo $response;
}

// Close the cURL session
curl_close($ch);
?>