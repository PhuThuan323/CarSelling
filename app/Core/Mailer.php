<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public static function sendPasswordResetCode(
        string $email,
        string $name,
        string $code
    ): bool {
        $mail = new PHPMailer(true);

        try {

            /*
            |--------------------------------------------------------------------------
            | SMTP CONFIG
            |--------------------------------------------------------------------------
            */

            $mail->isSMTP();

            $mail->Host =
                $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';

            $mail->SMTPAuth = true;

            $mail->Username =
                $_ENV['MAIL_USERNAME'] ?? '';

            $mail->Password =
                $_ENV['MAIL_PASSWORD'] ?? '';

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port =
                (int) ($_ENV['MAIL_PORT'] ?? 587);

            /*
            |--------------------------------------------------------------------------
            | ENCODING
            |--------------------------------------------------------------------------
            */

            $mail->CharSet = 'UTF-8';

            /*
            |--------------------------------------------------------------------------
            | SENDER
            |--------------------------------------------------------------------------
            */

            $fromAddress =
                $_ENV['MAIL_FROM_ADDRESS']
                ?? $_ENV['MAIL_USERNAME']
                ?? '';

            $fromName =
                $_ENV['MAIL_FROM_NAME']
                ?? 'CarSelling';

            $mail->setFrom(
                $fromAddress,
                $fromName
            );

            /*
            |--------------------------------------------------------------------------
            | RECEIVER
            |--------------------------------------------------------------------------
            */

            $mail->addAddress(
                $email,
                $name
            );

            /*
            |--------------------------------------------------------------------------
            | EMAIL CONTENT
            |--------------------------------------------------------------------------
            */

            $mail->isHTML(true);

            $mail->Subject =
                'Password Reset Code - CarSelling';

            $safeName = htmlspecialchars(
                $name,
                ENT_QUOTES,
                'UTF-8'
            );

            $mail->Body = "
                <div style='
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 30px;
                '>

                    <h2>
                        Reset your password
                    </h2>

                    <p>
                        Hello {$safeName},
                    </p>

                    <p>
                        We received a request to reset
                        your CarSelling password.
                    </p>

                    <p>
                        Your verification code is:
                    </p>

                    <div style='
                        margin: 25px 0;
                        padding: 20px;
                        background: #f3f4f6;
                        text-align: center;
                        border-radius: 8px;
                        font-size: 32px;
                        font-weight: bold;
                        letter-spacing: 8px;
                    '>
                        {$code}
                    </div>

                    <p>
                        This code expires in
                        <strong>10 minutes</strong>.
                    </p>

                    <p>
                        If you did not request this,
                        please ignore this email.
                    </p>

                </div>
            ";

            $mail->AltBody =
                'Your password reset code is: '
                . $code
                . '. This code expires in 10 minutes.';

            /*
            |--------------------------------------------------------------------------
            | SEND
            |--------------------------------------------------------------------------
            */

            $mail->send();

            return true;

        } catch (Exception $e) {

            error_log(
                'PHPMailer Error: '
                . $mail->ErrorInfo
            );

            return false;
        }
    }
}