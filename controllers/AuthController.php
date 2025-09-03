<?php
// AuthController handles registration, OTP verification, login and logout
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/utility.php';
require_once __DIR__ . '/../helpers/jwt.php';

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Otp.php';

class AuthController {
	private $conn;
	private $userModel;
	private $otpModel;
	private $cfg;

	public function __construct($conn) {
		$this->conn = $conn;
		$this->userModel = new User($conn);
		$this->otpModel = new OtpModel($conn);
		$this->cfg = require __DIR__ . '/../config/config.php';
	}

	// POST /api/auth/register
	public function register() {
		$data = get_json_input();
		$fullname = trim($data['fullname'] ?? '');
		$email = strtolower(trim($data['email'] ?? ''));
		$password = $data['password'] ?? '';
		$role = $data['role'] ?? 'reviewer'; // allow client to specify role; default reviewer

		if (!$fullname || !$email || !$password) {
			send_json(['success' => false, 'error' => 'fullname, email and password are required'], 400);
		}

		if (!in_array($role, ['author','reviewer'])) {
			$role = 'reviewer';
		}

		// Check email exists
		if ($this->userModel->findByEmail($email)) {
			send_json(['success' => false, 'error' => 'Email already registered'], 409);
		}

		// Hash password
		$password_hash = password_hash($password, PASSWORD_BCRYPT);

		// Create user (is_verified remains 0)
		$user_id = $this->userModel->create($fullname, $email, $password_hash, $role);
		if (!$user_id) {
			send_json(['success' => false, 'error' => 'Could not create user (DB error)'], 500);
		}

		// Log registration
		record_activity($this->conn, null, 'user.registered', 'user', (int)$user_id, ['email' => $email, 'role' => $role]);

		// Create OTP
		$otp_code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
		$code_hash = password_hash($otp_code, PASSWORD_BCRYPT);
		$expires_at = date('Y-m-d H:i:s', time() + ($this->cfg['otp_ttl_seconds'] ?? 900));
		$created = $this->otpModel->create($user_id, $code_hash, 'email_verification', $expires_at);

		// Send OTP to email
		$mail_sent = send_otp_email($email, $fullname, $otp_code);

		// Note: if mail fails we still created the user & OTP. Optionally roll back or notify
		if (!$mail_sent) {
			record_activity($this->conn, (int)$user_id, 'otp.send_failed', 'user', (int)$user_id);
			send_json(['success' => false, 'error' => 'User created but failed to send verification email. Check mail config.'], 500);
		}

		record_activity($this->conn, (int)$user_id, 'otp.sent', 'user', (int)$user_id);
		send_json(['success' => true, 'message' => 'User created. Verification OTP sent to email.'], 201);
	}

	// POST /api/auth/verify-otp
	public function verifyOtp() {
		$data = get_json_input();
		$email = strtolower(trim($data['email'] ?? ''));
		$otp = trim($data['otp'] ?? '');

		if (!$email || !$otp) {
			send_json(['success' => false, 'error' => 'email and otp are required'], 400);
		}

		$user = $this->userModel->findByEmail($email);
		if (!$user) send_json(['success' => false, 'error' => 'User not found'], 404);
		if ((int)$user['is_verified'] === 1) {
			send_json(['success' => true, 'message' => 'User already verified.'], 200);
		}

		$activeOtps = $this->otpModel->getActiveForUser($user['id'], 'email_verification');
		foreach ($activeOtps as $row) {
			if (password_verify($otp, $row['code_hash'])) {
				// OK: mark OTP used and set user verified
				$this->otpModel->markUsed($row['id']);
				$this->userModel->setVerified($user['id']);
				send_json(['success' => true, 'message' => 'Email verified. You can now log in.']);
			}
		}

		send_json(['success' => false, 'error' => 'Invalid or expired OTP'], 400);
	}

	// POST /api/auth/login
	public function login() {
		$data = get_json_input();
		$email = strtolower(trim($data['email'] ?? ''));
		$password = $data['password'] ?? '';

		if (!$email || !$password) {
			send_json(['success' => false, 'error' => 'email and password are required'], 400);
		}

		$user = $this->userModel->findByEmail($email);
		if (!$user) send_json(['success' => false, 'error' => 'Invalid credentials'], 401);

		if (!password_verify($password, $user['password_hash'])) {
			send_json(['success' => false, 'error' => 'Invalid credentials'], 401);
		}

		if ((int)$user['is_verified'] !== 1) {
			send_json(['success' => false, 'error' => 'Email not verified. Please verify your email (OTP).'], 403);
		}

		$token = generate_jwt($user['id'], $user['role']);
		record_activity($this->conn, (int)$user['id'], 'auth.logged_in', 'user', (int)$user['id']);

		$userData = [
			'id' => (int)$user['id'],
			'fullname' => $user['fullname'],
			'email' => $user['email'],
			'role' => $user['role'],
			'avatar_path' => $user['avatar_path']
		];

		send_json(['success' => true, 'token' => $token, 'user' => $userData]);
	}

	// POST /api/auth/logout
	public function logout() {
		// Get token from header, decode to get exp, then store hashed token in blacklist
		$token = get_bearer_token_from_header();
		if (!$token) {
			send_json(['success' => false, 'error' => 'Missing token'], 401);
		}

		$decoded = decode_jwt_or_null($token);
		if (!$decoded) send_json(['success' => false, 'error' => 'Invalid token'], 401);

		$exp = property_exists($decoded, 'exp') ? $decoded->exp : time();
		$expires_at = date('Y-m-d H:i:s', $exp);
		$token_hash = hash('sha256', $token);

		$stmt = $this->conn->prepare("INSERT INTO token_blacklist (token_hash, expires_at) VALUES (?, ?)");
		$stmt->bind_param("ss", $token_hash, $expires_at);
		if ($stmt->execute()) {
			record_activity($this->conn, (int)$decoded->sub, 'auth.logged_out', 'user', (int)$decoded->sub);
			send_json(['success' => true, 'message' => 'Logged out']);
		} else {
			send_json(['success' => false, 'error' => 'Could not logout (DB error)'], 500);
		}
	}
}
