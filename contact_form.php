<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    // Validate form data
    if (empty($name) || empty($email) || empty($message)) {
        echo 'Please fill in all fields';
    } else {
        $to = '<william.reames.19@cnu.edu>';
        $subject = 'New message from my website!';
        $message = wordwrap($message,70);
        $headers = "From: <$email>";

        $mail_result = mail($to, $subject, $message, $headers);

        if($mail_result){
            echo 'Message sent successfully.'; 
        }
        else{
            echo 'There was an error sending the message. Please try again later.';
        }
    }
} else {
    // If the request method is not POST, redirect to the contace page
    header('Location: /contact');
    exit;
}
