<?php
include('./consts.php');
function mailer($subject, $body, $to = EMAIL_TO, $from = EMAIL_FROM) {
    $headers = "From: " . $from;

    if ( mail($to, $subject, $body, $headers)) {
        echo("Email successfully sent to $to...");
    } else {
        echo("Email sending failed...");
    }

}