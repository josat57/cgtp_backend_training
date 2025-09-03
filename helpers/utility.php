<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/ActivityLog.php';

function get_json_input() {
	// Prefer body parsed by router (prevents php://input re-read empty issue)
	if (isset($GLOBALS['__ROUTER_JSON__']) && is_array($GLOBALS['__ROUTER_JSON__'])) {
		return $GLOBALS['__ROUTER_JSON__'];
	}
	$body = file_get_contents('php://input');
	$data = json_decode($body, true);
	if (!is_array($data)) return [];
	return $data;
}

// send OTP email using PHPMailer. Requires correct config in config/config.php
function send_otp_email($toEmail, $toName, $otp) {
	$cfg = require __DIR__ . '/../config/config.php';
	$mailCfg = $cfg['mail'];

	$mail = new PHPMailer(true);
	try {
		// SMTP configuration (Zoho Mail)
		$mail->isSMTP();
		$mail->Host = $mailCfg['host'];
		$mail->SMTPAuth = true;
		$mail->Username = $mailCfg['username'];
		$mail->Password = $mailCfg['password'];
		// Respect configured encryption (ssl for 465, tls for 587)
		if (($mailCfg['encryption'] ?? 'ssl') === 'ssl') {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // implicit TLS/SSL
		} else {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS
		}
		$mail->Port = $mailCfg['port'];
		//$mail->SMTPDebug = 2; // enable for debugging

		// From/To
		$mail->setFrom($mailCfg['from_email'], $mailCfg['from_name']);
		$mail->addAddress($toEmail, $toName);

		// Content
		$mail->isHTML(true);
		$mail->Subject = 'Your verification code';
		$mail->Body = "<p>Your verification code is: <strong>{$otp}</strong></p><p>This code is valid for 15 minutes.</p>";
		$mail->AltBody = "Your verification code is: {$otp}";

		$mail->send();
		return true;
	} catch (Exception $e) {
		// In production, log the error ($mail->ErrorInfo)
		return false;
	}
}

function record_activity($conn, ?int $actorUserId, string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []) {
    $logger = new ActivityLog($conn);
    // Fire-and-forget; ignore errors to avoid affecting main flow
    try { $logger->record($actorUserId, $action, $entityType, $entityId, $meta); } catch (\Throwable $e) {}
}
