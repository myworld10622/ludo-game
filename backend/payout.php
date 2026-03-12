<?php
// // API URL
// $api_url = 'https://Payformee.com/api/bank/create-order';

// // User input
// $user_token = 'ba7ded0202cd40d42dab6bfff66bd68b'; // Replace with the user's API token
// $amount = 100; // Replace with the payout amount
// $upiid= "arunmauryaaa@ybl"; // Replace with upi id
// $secret_key = 'xQWxHUOaHsCKMmR991hL3Krd6flHXpny'; // Your secret key for checksum generation 

// // Create an array with POST data
// $post_data = [
//     'user_token' => $user_token,
//     'amount' => $amount,
//     'upi_id' => $upiid
// ];

// // Function to generate xverify
// function generatexverify($data, $secret_key) {
//   // Sort the data by keys to ensure consistent order
//   ksort($data);
//   $dataString = implode('|', array_map(function ($key, $value) {
//       return $key . '=' . $value;
//   }, array_keys($data), $data));
//   return hash_hmac('sha256', $dataString, $secret_key);
// }

// // Generate xverify
// $xverify = generatexverify($post_data, $secret_key);

// // Initialize cURL session
// $ch = curl_init($api_url);

// // Prepare the headers including the X-Verify custom header
// $headers = [
//     'Content-Type: application/x-www-form-urlencoded', // Set the content type
//     'X-VERIFY: ' . $xverify, // Send the xverify in the headers
// ];

// // Set cURL options
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data)); // Use http_build_query to format POST data
// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Ensure headers are correctly set

// // Execute the cURL session and capture the response
// $response = curl_exec($ch);

// // Check for cURL errors
// if (curl_errno($ch)) {
//     echo 'cURL Error: ' . curl_error($ch);
// } else {
//     echo $response;
// }

// // Close the cURL session
// curl_close($ch);



// API URL
$api_url = 'https://Payformee.com/api/bank/create-order';

// User input
$user_token = 'ba7ded0202cd40d42dab6bfff66bd68b'; // Replace with the user's API Key
$amount = 100; // Replace with the payout amount
$accnumber= 2312546498; // Replace with acc number
$ifsc="KKBK0000671"; //ifsc code
$secret_key = 'xQWxHUOaHsCKMmR991hL3Krd6flHXpny'; // Your secret key for checksum generation 

// Create an array with POST data
$post_data = [
    'user_token' => $user_token,
    'amount' => $amount,
    'acc_no' => $accnumber,
    'ifsc' =>$ifsc
];

// Function to generate xverify
function generatexverify($data, $secret_key) {
  // Sort the data by keys to ensure consistent order
  ksort($data);
  $dataString = implode('|', array_map(function ($key, $value) {
      return $key . '=' . $value;
  }, array_keys($data), $data));
  return hash_hmac('sha256', $dataString, $secret_key);
}

// Generate xverify
$xverify = generatexverify($post_data, $secret_key);

// Initialize cURL session
$ch = curl_init($api_url);

// Prepare the headers including the X-Verify custom header
$headers = [
    'Content-Type: application/x-www-form-urlencoded', // Set the content type
    'X-VERIFY: ' . $xverify, // Send the xverify in the headers
];

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data)); // Use http_build_query to format POST data
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Ensure headers are correctly set

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