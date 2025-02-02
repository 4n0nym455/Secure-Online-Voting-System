<?php
$allowed_ips = ['127.0.1.1', '::1', '216.128.0.118'];
$client_ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $client_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}
if (!in_array($client_ip, $allowed_ips)) {
    http_response_code(403);
    echo "Access denied. Your IP ($client_ip) is not authorized.";
    header("Refresh:3; url = ../index.php");
    exit;
}

?>
