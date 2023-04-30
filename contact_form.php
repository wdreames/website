<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function sendMail($name, $replyto, $message) {
    $receiver_email = "wdreames@gmail.com";
    $server_email = "william.reames.website@gmail.com";
    $server_name = "william-reames.com";
    $subject = "New message on my website from $name";	

    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->Mailer = "smtp";

    $mail->SMTPDebug  = 0;  
    $mail->SMTPAuth   = TRUE;
    $mail->SMTPSecure = "tls";
    $mail->Port       = 587;
    $mail->Host       = "smtp.gmail.com";
    $mail->Username   = "$_SERVER[GMAIL_USER]";
    $mail->Password   = "$_SERVER[GMAIL_PASS]";
    
    $mail->IsHTML(true);
    $mail->AddAddress("$receiver_email");
    $mail->SetFrom("$server_email", "$server_name");
    $mail->AddReplyTo("$replyto", "$name");
    $mail->Subject = "$subject";

    $mail->MsgHTML($message);
    return $mail->Send();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($message)) {
        echo 'Please fill in all fields.';
    } else {
        $mail_result = sendMail($name, $email, $message);
        if($mail_result){
            echo 'Message sent successfully.'; 
        }
        else{
            echo 'There was an error sending the message. Please try again later.';
        }
    }
} else {
    // If the request method is not POST, redirect to the contact page
    header('Location: /contact');
    exit;
}
