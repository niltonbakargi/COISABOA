<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = file_get_contents('php://input');
parse_str($input, $postData);

echo json_encode([
    'status' => 'TEST_API_WORKING',
    'debug_info' => [
        'raw_input' => $input,
        'post_data' => $_POST,
        'get_data' => $_GET,
        'parsed_input' => $postData,
        'server_method' => $_SERVER['REQUEST_METHOD']
    ],
    'message' => 'API Test está funcionando!'
]);
?>