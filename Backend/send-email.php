<?php
// Set content type to JSON first
header('Content-Type: application/json');

// Disable error display (but keep logging)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed', 405);
}

// Sanitize and validate input data
$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$userEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Basic validation
if (empty($firstName) || empty($lastName) || empty($userEmail) || empty($message)) {
    sendJsonResponse(false, 'All fields are required', 400);
}

// Validate email format
if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Invalid email format', 400);
}

// Load PHPMailer
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPDebug = 0; // Always 0 for production

    // SMTP Configuration
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    
    // Your email credentials
    $mail->Username = 'youremail@gmail.com'; // Your SMTP username
    $mail->Password = 'password'; // Your SMTP password (use App Password for Gmail)

    // Recipients
    $mail->setFrom($userEmail, "$firstName $lastName");
    $mail->addAddress('info@uip.ph', 'Jason');
    $mail->addReplyTo($userEmail, "$firstName $lastName");

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'New Contact Form Submission from UIP Website';
    
    // Create HTML email body
    $mail->Body = "
    <html>
    <body>
        <h2>UIP Contact Form Submission</h2>
        <p><strong>From:</strong> " . htmlspecialchars($firstName . ' ' . $lastName) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($userEmail) . "</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br(htmlspecialchars($message)) . "</p>
        <hr>
        <p><small>This message was sent from the UIP website contact form.</small></p>
    </body>
    </html>";

    // Plain text version
    $mail->AltBody = "New Contact Form Submission\n\n" .
                     "From: $firstName $lastName\n" .
                     "Email: $userEmail\n\n" .
                     "Message:\n$message\n\n" .
                     "This message was sent from the UIP website contact form.";

    // Send the email
    $mail->send();
    
    // Success response
    sendJsonResponse(true, 'Message sent successfully!');
    
} catch (Exception $e) {
    // Log the actual error
    error_log("Email sending failed: " . $e->getMessage());
    
    // Send generic error message to user
    sendJsonResponse(false, 'Message could not be sent. Please try again later.', 500);
}
?>