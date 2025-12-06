<?php
// app/controllers/PasswordResetController.php
require_once __DIR__ . '/../helpers/EmailHelper.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../config/database.php';

class PasswordResetController
{
    private $conn;
    private $emailHelper;
    private $userModel;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
        $this->emailHelper = new EmailHelper();
        $this->userModel = new User();
    }

    private function generateOTP()
    {
        return sprintf("%04d", mt_rand(0, 9999));
    }

    public function sendOTP($email)
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }

            $user = $this->userModel->getUserByEmail($email);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'If this email exists, an OTP has been sent.'
                ];
            }

            if (!$user['is_active']) {
                return [
                    'success' => false,
                    'message' => 'This account is deactivated. Please contact support.'
                ];
            }

            $otp = $this->generateOTP();
            $expiresMinutes = 30;

            $query = "INSERT INTO password_reset_otps 
                  (email, otp, expires_at, created_at) 
                  VALUES (:email, :otp, DATE_ADD(NOW(), INTERVAL :minutes MINUTE), NOW())
                  ON DUPLICATE KEY UPDATE 
                  otp = :otp2, 
                  expires_at = DATE_ADD(NOW(), INTERVAL :minutes2 MINUTE), 
                  created_at = NOW(),
                  is_used = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':otp', $otp);
            $stmt->bindParam(':minutes', $expiresMinutes, PDO::PARAM_INT);
            $stmt->bindParam(':otp2', $otp);
            $stmt->bindParam(':minutes2', $expiresMinutes, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                error_log("Failed to insert OTP: " . print_r($stmt->errorInfo(), true));
                return [
                    'success' => false,
                    'message' => 'Failed to generate OTP'
                ];
            }

            $emailResult = $this->emailHelper->sendOTP(
                $email,
                $user['full_name'],
                $otp
            );

            if ($emailResult['success']) {
                error_log("OTP sent to: $email - OTP: $otp");
                return [
                    'success' => true,
                    'message' => 'OTP sent to your email. Valid for 30 minutes.',
                    'email' => $email
                ];
            } else {
                error_log("Email sending failed: " . print_r($emailResult, true));
                return [
                    'success' => false,
                    'message' => 'Failed to send email. Please try again.'
                ];
            }
        } catch (Exception $e) {
            error_log("Send OTP error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }

    public function verifyOTP($email, $otp)
    {
        try {
            error_log("=== VERIFY OTP DEBUG ===");
            error_log("Email: " . $email);
            error_log("OTP: " . $otp);

            $query = "SELECT * FROM password_reset_otps 
                  WHERE email = :email 
                  AND otp = :otp 
                  AND expires_at > NOW() 
                  AND is_used = 0 
                  ORDER BY created_at DESC 
                  LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':otp', $otp);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Database query result: " . ($result ? "FOUND" : "NOT FOUND"));

            if ($result) {
                error_log("OTP Details - Created: " . $result['created_at'] . ", Expires: " . $result['expires_at']);
            } else {
                $checkQuery = "SELECT *, 
                          CASE 
                              WHEN expires_at < NOW() THEN 'expired'
                              WHEN is_used = 1 THEN 'used'
                              WHEN otp != :otp THEN 'wrong_code'
                              ELSE 'unknown'
                          END as reason
                          FROM password_reset_otps 
                          WHERE email = :email 
                          ORDER BY created_at DESC 
                          LIMIT 1";

                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->bindParam(':email', $email);
                $checkStmt->bindParam(':otp', $otp);
                $checkStmt->execute();

                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($checkResult) {
                    error_log("OTP found but invalid - Reason: " . $checkResult['reason']);
                    error_log("Stored OTP: " . $checkResult['otp'] . " vs Provided: " . $otp);
                    error_log("Expires at: " . $checkResult['expires_at'] . " vs Now: " . date('Y-m-d H:i:s'));
                    error_log("Is used: " . $checkResult['is_used']);
                }
            }

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired OTP. Please try again or request a new code.'
                ];
            }

            $updateQuery = "UPDATE password_reset_otps 
                       SET verified_at = NOW() 
                       WHERE email = :email AND otp = :otp";

            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':email', $email);
            $updateStmt->bindParam(':otp', $otp);
            $updateStmt->execute();

            error_log("OTP verified successfully for: " . $email);

            return [
                'success' => true,
                'message' => 'OTP verified successfully',
                'email' => $email
            ];
        } catch (Exception $e) {
            error_log("Verify OTP error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Verification failed. Please try again.'
            ];
        }
    }

    public function resetPassword($email, $newPassword)
    {
        try {
            if (strlen($newPassword) < 8) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least 8 characters long'
                ];
            }

            $query = "SELECT * FROM password_reset_otps 
                      WHERE email = :email 
                      AND verified_at IS NOT NULL 
                      AND is_used = 0 
                      AND expires_at > NOW() 
                      ORDER BY created_at DESC 
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$otpRecord) {
                return [
                    'success' => false,
                    'message' => 'Please verify your OTP first'
                ];
            }

            $user = $this->userModel->getUserByEmail($email);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            if ($this->userModel->updatePassword($user['userID'], $passwordHash)) {

                $markUsedQuery = "UPDATE password_reset_otps 
                                 SET is_used = 1 
                                 WHERE email = :email";

                $markUsedStmt = $this->conn->prepare($markUsedQuery);
                $markUsedStmt->bindParam(':email', $email);
                $markUsedStmt->execute();

                $this->emailHelper->sendPasswordResetConfirmation(
                    $email,
                    $user['full_name']
                );

                error_log("Password reset successful for: $email");

                return [
                    'success' => true,
                    'message' => 'Password reset successfully. You can now log in.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to reset password'
                ];
            }
        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }

    public function resendOTP($email)
    {
        return $this->sendOTP($email);
    }

    public function cleanExpiredOTPs()
    {
        try {
            $query = "DELETE FROM password_reset_otps 
                      WHERE expires_at < NOW() 
                      OR (is_used = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            error_log("Clean expired OTPs error: " . $e->getMessage());
            return false;
        }
    }
}
