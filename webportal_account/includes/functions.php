<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ---------- output / navigation ---------- */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): void { header('Location: ' . BASE_URL . $path); exit; }
function peso($n): string { return '₱ ' . number_format((float)$n, 2); }
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
/** Name of the user who added/updated a row; NULL means that user has since closed their account. */
function who(?string $name): string { return $name ?? 'Closed account'; }

/* ---------- auth ---------- */
function require_login(): void {
    if (empty($_SESSION['user_id'])) redirect('/login.php');   // not logged in -> login page
}
function current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            http_response_code(400);
            die('Invalid or expired form token. Go back and try again.');
        }
    }
}

/* ---------- encryption for game-account passwords (seller must read them back) ---------- */
function encrypt_text(string $plain): string {
    $key = hash('sha256', APP_KEY, true);
    $iv  = random_bytes(16);
    $enc = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $enc);
}
function decrypt_text(string $blob): string {
    $raw = base64_decode($blob, true);
    if ($raw === false || strlen($raw) < 17) return '';
    $key = hash('sha256', APP_KEY, true);
    $out = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, substr($raw, 0, 16));
    return $out === false ? '' : $out;
}

/* ---------- game layouts ---------- */
const RANK_TIERS = ['Mythic', 'Legend', 'Epic'];

function game_meta_all(): array {
    return [
        'Genshin Impact' => [
            'group' => 'hoyo', 'level_label' => 'Adventure Rank', 'level_max' => 60,
            'items_label' => '5★ Characters Available', 'has_rank' => false,
        ],
        'Zenless Zone Zero' => [
            'group' => 'hoyo', 'level_label' => 'Interknot Level', 'level_max' => 60,
            'items_label' => '5★ Characters Available', 'has_rank' => false,
        ],
        'Mobile Legends' => [
            'group' => 'ml', 'level_label' => 'Account Level', 'level_max' => 300,
            'items_label' => 'Valuable Skins Available', 'has_rank' => true,
        ],
    ];
}
function game_meta(string $name): ?array { return game_meta_all()[$name] ?? null; }

/* ---------- small HTML helper: one-button POST form ---------- */
function post_button(string $action, int $id, string $label, string $class = 'btn btn-sm', string $confirm = ''): string {
    $c = $confirm ? ' data-confirm="' . e($confirm) . '"' : '';
    return '<form method="post" class="inline"' . $c . '>' . csrf_field()
         . '<input type="hidden" name="action" value="' . e($action) . '">'
         . '<input type="hidden" name="account_id" value="' . $id . '">'
         . '<button class="' . e($class) . '">' . e($label) . '</button></form>';
}

/* ---------- activity log: who inserted / updated / deleted what ---------- */
function log_activity(string $action, string $table, ?int $recordId = null, string $details = '', ?array $actor = null): void {
    $uid  = $actor['user_id']  ?? ($_SESSION['user_id']  ?? null);
    $name = $actor['username'] ?? ($_SESSION['username'] ?? 'unknown');
    $st = db()->prepare('INSERT INTO activity_log (user_id, username, action, table_name, record_id, details) VALUES (?,?,?,?,?,?)');
    $st->execute([$uid, $name, $action, $table, $recordId, mb_substr($details, 0, 255)]);
}

/* ---------- user entity helpers ---------- */
/** Validate the personal-information fields. Returns [cleanData, errors]. */
function validate_person(array $in): array {
    $d = [
        'first_name'     => trim($in['first_name'] ?? ''),
        'last_name'      => trim($in['last_name'] ?? ''),
        'age'            => trim((string)($in['age'] ?? '')),
        'contact_number' => trim($in['contact_number'] ?? ''),
        'email'          => trim($in['email'] ?? ''),
        'address'        => trim($in['address'] ?? ''),
    ];
    $err = [];
    $nameRe = '/^[\p{L} .\'\-]{1,50}$/u';
    if (!preg_match($nameRe, $d['first_name'])) $err[] = 'First name is required (letters only, max 50 characters).';
    if (!preg_match($nameRe, $d['last_name']))  $err[] = 'Last name is required (letters only, max 50 characters).';
    if (!ctype_digit($d['age']) || (int)$d['age'] < 1 || (int)$d['age'] > 120) $err[] = 'Age must be a number from 1 to 120.';
    if ($d['contact_number'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $d['contact_number'])) $err[] = 'Contact number looks invalid.';
    if ($d['email'] !== '' && (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['email']) > 100)) $err[] = 'Email looks invalid.';
    if (mb_strlen($d['address']) > 255) $err[] = 'Address is too long (max 255 characters).';
    return [$d, $err];
}

function username_taken(string $username): bool {
    $st = db()->prepare('SELECT 1 FROM users WHERE username = ?');
    $st->execute([$username]);
    return (bool)$st->fetchColumn();
}

/** Validate username + password pair used when creating a user. Returns error strings. */
function validate_credentials(string $username, string $password, string $confirm): array {
    $err = [];
    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) $err[] = 'Username must be 3-50 letters, numbers, or underscores.';
    elseif (username_taken($username))                    $err[] = 'That username is taken.';
    if (strlen($password) < 8)   $err[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)  $err[] = 'Passwords do not match.';
    return $err;
}

/**
 * INSERT a user. $addedBy = the logged-in user who creates them, or NULL for self-registration
 * (then added_by points to the new user themself). Returns the new user_id.
 */
function create_user(array $d, string $username, string $password, ?int $addedBy): int {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'INSERT INTO users (first_name, last_name, age, address, contact_number, email, username, password_hash)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $d['first_name'], $d['last_name'], (int)$d['age'],
            $d['address'] !== '' ? $d['address'] : null,
            $d['contact_number'] !== '' ? $d['contact_number'] : null,
            $d['email'] !== '' ? $d['email'] : null,
            $username, password_hash($password, PASSWORD_DEFAULT),
        ]);
        $id = (int)$pdo->lastInsertId();
        $by = $addedBy ?? $id;
        $pdo->prepare('UPDATE users SET added_by = ?, updated_by = ? WHERE user_id = ?')->execute([$by, $by, $id]);
        $actor = $addedBy === null ? ['user_id' => $id, 'username' => $username] : null;
        log_activity('INSERT', 'users', $id, "Created user '$username'", $actor);
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
