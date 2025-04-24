<?php
header('Content-Type: application/json');

// reCAPTCHA secret key
$recaptcha_secret = "YOUR_RECAPTCHA_SECRET_KEY";

// Verify reCAPTCHA
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
$verify_url = "https://www.google.com/recaptcha/api/siteverify";
$data = [
    'secret' => $recaptcha_secret,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$verify_response = file_get_contents($verify_url, false, $context);
$response_data = json_decode($verify_response);

if (!$response_data->success) {
    echo json_encode(['success' => false, 'message' => 'Please complete the reCAPTCHA verification']);
    exit;
}

// Get form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$subject = $_POST['subject'] ?? '';
$product = $_POST['product'] ?? '';
$message = $_POST['message'] ?? '';

// Validate inputs
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

// Prepare email content
$to = 'support@leftovr.org';
$headers = [
    'From' => $email,
    'Reply-To' => $email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'Content-Type' => 'text/html; charset=UTF-8'
];

$email_content = "
<html>
<head>
    <title>New Support Request</title>
</head>
<body>
    <h2>New Support Request</h2>
    <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
    <p><strong>Product:</strong> " . htmlspecialchars($product) . "</p>
    <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
    <p><strong>Message:</strong></p>
    <p>" . nl2br(htmlspecialchars($message)) . "</p>
</body>
</html>";

// Send email using PHP's mail function
$mail_sent = mail($to, $subject, $email_content, buildHeaders($headers));

if ($mail_sent) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your message. We will get back to you soon.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Sorry, there was an error sending your message. Please try again later.']);
}

// Helper function to build email headers
function buildHeaders($headers) {
    $output = '';
    foreach ($headers as $key => $value) {
        $output .= "$key: $value\r\n";
    }
    return $output;
}
?> 