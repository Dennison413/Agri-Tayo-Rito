<?php
// app/helpers/EmailHelper.php
$vendorPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    die('Composer autoload not found. Run: composer require phpmailer/phpmailer');
}
require_once $vendorPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper
{
    private $mailer;
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/email.php';
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer()
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = $this->config['smtp_auth'];
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = $this->config['smtp_secure'];
            $this->mailer->Port = $this->config['smtp_port'];

            $this->mailer->setFrom(
                $this->config['from_email'],
                $this->config['from_name']
            );

            $this->mailer->addReplyTo(
                $this->config['reply_to'],
                $this->config['from_name']
            );

            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("Mailer configuration error: " . $e->getMessage());
        }
    }

    public function sendOTP($toEmail, $toName, $otp)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);

            $this->mailer->Subject = 'Password Reset OTP - Agri Tayo Rito';

            $htmlBody = $this->getOTPEmailTemplate($toName, $otp);
            $this->mailer->Body = $htmlBody;

            $this->mailer->AltBody = "Your OTP for password reset is: $otp\n\n" .
                "This code will expire in 10 minutes.\n\n" .
                "If you did not request this, please ignore this email.";

            $result = $this->mailer->send();

            if ($result) {
                error_log("OTP email sent successfully to: $toEmail");
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully'
                ];
            }
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mailer->ErrorInfo);
            return [
                'success' => false,
                'message' => 'Failed to send OTP email',
                'error' => $this->mailer->ErrorInfo
            ];
        }

        return [
            'success' => false,
            'message' => 'Unknown error occurred'
        ];
    }

    private function getOTPEmailTemplate($name, $otp)
    {
        return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2d5016; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .otp-box { background: white; border: 2px dashed #2d5016; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
            .otp-code { font-size: 32px; font-weight: bold; color: #2d5016; letter-spacing: 8px; }
            .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
            .warning { color: #d32f2f; font-weight: bold; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🌾 Agri Tayo Rito</h1>
                <p>Password Reset Request</p>
            </div>
            <div class="content">
                <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p>We received a request to reset your password. Use the OTP code below to proceed:</p>
                
                <div class="otp-box">
                    <p style="margin: 0; color: #666;">Your OTP Code:</p>
                    <div class="otp-code">' . $otp . '</div>
                    <p style="margin: 10px 0 0 0; font-size: 14px; color: #999;">Valid for 30 minutes</p>
                </div>
                
                <p>Enter this code on the verification page to reset your password.</p>
                
                <div class="warning">
                    ⚠️ If you did not request this password reset, please ignore this email and ensure your account is secure.
                </div>
            </div>
            <div class="footer">
                <p>© 2025 Agri Tayo Rito - Fresh from Farm to Table</p>
                <p>This is an automated email, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    }

    public function sendPasswordResetConfirmation($toEmail, $toName)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);

            $this->mailer->Subject = 'Password Successfully Reset - Agri Tayo Rito';

            $htmlBody = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2d5016; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                    .success-icon { font-size: 48px; text-align: center; margin: 20px 0; }
                    .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🌾 Agri Tayo Rito</h1>
                    </div>
                    <div class="content">
                        <div class="success-icon">✅</div>
                        <h2 style="text-align: center; color: #2d5016;">Password Reset Successful</h2>
                        <p>Hello <strong>' . htmlspecialchars($toName) . '</strong>,</p>
                        <p>Your password has been successfully reset. You can now log in with your new password.</p>
                        <p>If you did not make this change, please contact support immediately.</p>
                    </div>
                    <div class="footer">
                        <p>© 2025 Agri Tayo Rito - Fresh from Farm to Table</p>
                    </div>
                </div>
            </body>
            </html>
            ';

            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = "Your password has been successfully reset. You can now log in with your new password.";

            $this->mailer->send();
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Confirmation email failed: " . $this->mailer->ErrorInfo);
            return ['success' => false];
        }
    }
}
