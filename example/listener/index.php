<?php

// Middleware to parse JSON content in the request body
$requestBody = json_decode(file_get_contents('php://input'), true);

// Create a simple router
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if ($_SERVER['REQUEST_URI'] === '/create') {
            handleRequest('POST', $requestBody);
        } else {
            notFound();
        }
        break;
    case 'PATCH':
        if ($_SERVER['REQUEST_URI'] === '/update') {
            handleRequest('PATCH', $requestBody);
        } else {
            notFound();
        }
        break;
    case 'DELETE':
        if ($_SERVER['REQUEST_URI'] === '/delete') {
            handleRequest('DELETE', $requestBody);
        } else {
            notFound();
        }
        break;
    default:
        notFound();
        break;
}

function handleRequest($method, $requestData)
{
    // Log the received data
    error_log("Received data for $method:\n");
    error_log(json_encode($requestData, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));

    // Respond with a simple message
    echo "Data received for $method";
}

function notFound()
{
    http_response_code(404);
    echo 'Not Found';
}
