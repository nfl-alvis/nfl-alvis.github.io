<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

const ROLE_USER = 'user';
const ROLE_STORE_ADMIN = 'store_admin';
const ROLE_SUPER_ADMIN = 'super_admin';

function app_name(): string
{
    return 'PusakaRasa';
}

function base_path(string $path = ''): string
{
    $root = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (basename($root) === 'auth') {
        $root = rtrim(dirname($root), '/\\');
    }

    $root = $root === '/' ? '' : $root;

    return $root . '/' . ltrim($path, '/');
}

function redirect_to(string $path): never
{
    header('Location: ' . base_path($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user ?: null;
    }

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        $user = null;
        return null;
    }

    $stmt = db()->prepare(
        'SELECT u.*, s.name AS store_name, s.slug AS store_slug
         FROM users u
         LEFT JOIN stores s ON s.id = u.store_id
         WHERE u.id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch() ?: null;

    if (!$user) {
        unset($_SESSION['user_id']);
    }

    return $user ?: null;
}

function user_profile_image_url(?array $user): string
{
    $path = trim((string) ($user['profile_image'] ?? ''));

    if ($path === '') {
        $path = trim((string) ($user['picture'] ?? ''));
    }

    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return base_path($path);
}

function user_profile_initial(?array $user): string
{
    $name = trim((string) ($user['name'] ?? ''));

    if ($name === '') {
        return 'U';
    }

    return strtoupper(substr($name, 0, 1));
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string ...$roles): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    return in_array($user['role'], $roles, true);
}

function user_can_manage_product_reviews(?array $user, array $product): bool
{
    if (!$user) {
        return false;
    }

    if (($user['role'] ?? '') === ROLE_SUPER_ADMIN) {
        return true;
    }

    return (($user['role'] ?? '') === ROLE_STORE_ADMIN)
        && (int) ($user['store_id'] ?? 0) > 0
        && (int) ($user['store_id'] ?? 0) === (int) ($product['store_id'] ?? 0);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Silakan masuk terlebih dahulu.');
        redirect_to('login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!has_role(...$roles)) {
        set_flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect_to('index.php');
    }
}

function nav_target_for_user(?array $user): string
{
    if (!$user) {
        return 'login.php';
    }

    return match ($user['role']) {
        ROLE_SUPER_ADMIN => 'admin-dashboard.php',
        ROLE_STORE_ADMIN => 'store-dashboard.php',
        default => 'katalog.php',
    };
}

function number_short(int $value): string
{
    if ($value >= 1000000) {
        return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.') . 'M';
    }

    if ($value >= 1000) {
        return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.') . 'K';
    }

    return number_format($value);
}

function rupiah(?string $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return 'Rp ' . $value;
}

function operating_days(): array
{
    return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
}

function normalize_operating_time(string $value, string $default): string
{
    $value = trim(str_replace('.', ':', $value));

    if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches)) {
        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
            return sprintf('%02d:%02d', $hour, $minute);
        }
    }

    return $default;
}

function normalize_operating_slot(string $value): string
{
    $value = trim(str_replace('.', ':', $value));

    if ($value === '24 Jam' || $value === 'Tutup') {
        return $value;
    }

    if (preg_match('/^(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})$/', $value, $matches)) {
        $open = normalize_operating_time($matches[1], '');
        $close = normalize_operating_time($matches[2], '');

        if ($open !== '' && $close !== '') {
            return $open . ' - ' . $close;
        }
    }

    return '08:00 - 21:00';
}

function operating_slot_parts(string $slot): array
{
    $slot = normalize_operating_slot($slot);

    if ($slot === 'Tutup') {
        return ['status' => 'Tutup', 'open' => '08:00', 'close' => '21:00'];
    }

    if ($slot === '24 Jam') {
        return ['status' => '24 Jam', 'open' => '00:00', 'close' => '23:30'];
    }

    if (preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $slot, $matches)) {
        return ['status' => 'Buka', 'open' => $matches[1], 'close' => $matches[2]];
    }

    return ['status' => 'Buka', 'open' => '08:00', 'close' => '21:00'];
}

function default_operating_schedule(string $slot = '08:00 - 21:00'): array
{
    return array_fill_keys(operating_days(), $slot);
}

function parse_operating_schedule(?string $value): array
{
    $value = trim((string) $value);

    if ($value !== '') {
        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            $schedule = [];

            foreach (operating_days() as $day) {
                $schedule[$day] = normalize_operating_slot((string) ($decoded[$day] ?? '08:00 - 21:00'));
            }

            return $schedule;
        }

        if (preg_match('/(\d{1,2}[.:]\d{2})\s*-\s*(\d{1,2}[.:]\d{2})/', $value, $matches)) {
            $slot = normalize_operating_slot($matches[1] . ' - ' . $matches[2]);
            return default_operating_schedule($slot);
        }

        if (stripos($value, '24') !== false) {
            return default_operating_schedule('24 Jam');
        }
    }

    return default_operating_schedule();
}

function operating_schedule_from_post(mixed $value): string
{
    if (!is_array($value)) {
        $schedule = parse_operating_schedule((string) $value);
        return json_encode($schedule, JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    $schedule = [];

    foreach (operating_days() as $day) {
        $postedDay = $value[$day] ?? '';

        if (is_array($postedDay)) {
            $status = (string) ($postedDay['status'] ?? 'Buka');

            if ($status === 'Tutup' || $status === '24 Jam') {
                $schedule[$day] = $status;
                continue;
            }

            $open = normalize_operating_time((string) ($postedDay['open'] ?? ''), '08:00');
            $close = normalize_operating_time((string) ($postedDay['close'] ?? ''), '21:00');
            $schedule[$day] = $open . ' - ' . $close;
            continue;
        }

        $slot = normalize_operating_slot((string) $postedDay);
        $schedule[$day] = $slot;
    }

    return json_encode($schedule, JSON_UNESCAPED_UNICODE) ?: '{}';
}

function operating_schedule_is_open_today(?string $value): bool
{
    $schedule = parse_operating_schedule($value);
    $dayIndex = ((int) date('N')) - 1;
    $today = operating_days()[$dayIndex] ?? 'Senin';

    return ($schedule[$today] ?? '08:00 - 21:00') !== 'Tutup';
}

function operating_hours_display(?string $value): string
{
    $schedule = parse_operating_schedule($value);
    $uniqueSlots = array_values(array_unique($schedule));

    if (count($uniqueSlots) === 1) {
        $slot = $uniqueSlots[0];

        return match ($slot) {
            'Tutup' => 'Tutup setiap hari',
            '24 Jam' => 'Buka 24 jam setiap hari',
            default => 'Buka jam ' . $slot . ' setiap hari',
        };
    }

    $dayIndex = ((int) date('N')) - 1;
    $today = operating_days()[$dayIndex] ?? 'Senin';
    $slot = $schedule[$today] ?? '08:00 - 21:00';

    return match ($slot) {
        'Tutup' => 'Tutup hari ini',
        '24 Jam' => 'Buka 24 jam hari ini',
        default => 'Buka jam ' . $slot . ' hari ini',
    };
}

function render_operating_hours_selects(?string $currentValue, string $fieldName = 'operating_hours'): void
{
    $schedule = parse_operating_schedule($currentValue);
    ?>
    <div class="operating-hours-grid">
      <?php foreach (operating_days() as $day): ?>
        <?php $parts = operating_slot_parts($schedule[$day] ?? '08:00 - 21:00'); ?>
        <?php $isClosed = $parts['status'] === 'Tutup'; ?>
        <div class="operating-hours-row">
          <span class="operating-hours-day"><?= e($day) ?></span>
          <div class="operating-hours-controls">
            <select class="operating-hours-select operating-hours-status" name="<?= e($fieldName) ?>[<?= e($day) ?>][status]" aria-label="Status <?= e($day) ?>" required>
              <?php foreach (['Buka', 'Tutup', '24 Jam'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $parts['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
              <?php endforeach; ?>
            </select>
            <input class="operating-hours-input" type="time" name="<?= e($fieldName) ?>[<?= e($day) ?>][open]" value="<?= e($parts['open']) ?>" aria-label="Jam buka <?= e($day) ?>" <?= $isClosed ? 'disabled' : 'required' ?> />
            <input class="operating-hours-input" type="time" name="<?= e($fieldName) ?>[<?= e($day) ?>][close]" value="<?= e($parts['close']) ?>" aria-label="Jam tutup <?= e($day) ?>" <?= $isClosed ? 'disabled' : 'required' ?> />
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
}

function normalize_price_display(?string $value): string
{
    $digits = preg_replace('/\D+/', '', (string) $value) ?: '';

    if ($digits === '') {
        throw new RuntimeException('Harga produk wajib diisi angka.');
    }

    $digits = ltrim($digits, '0');
    if ($digits === '') {
        return '0';
    }

    return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits) ?: $digits;
}

function rating_stars_html(float $rating): string
{
    $rating = max(0.0, min(5.0, $rating));
    $wholeRating = floor($rating);
    $visualRating = $rating > $wholeRating ? $wholeRating + 0.5 : $wholeRating;
    $html = '<span class="rating-stars" aria-label="' . e(number_format($rating, 1) . ' dari 5 bintang') . '">';

    for ($star = 1; $star <= 5; $star++) {
        if ($visualRating >= $star) {
            $className = 'is-full';
        } elseif ($visualRating >= $star - 0.5) {
            $className = 'is-half';
        } else {
            $className = 'is-empty';
        }

        $html .= '<span class="rating-star ' . $className . '" aria-hidden="true">★</span>';
    }

    return $html . '</span>';
}

function session_key(): string
{
    if (!isset($_SESSION['visitor_key'])) {
        $_SESSION['visitor_key'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['visitor_key'];
}

function track_store_visit(int $storeId): void
{
    $stmt = db()->prepare(
        'INSERT IGNORE INTO store_visits (store_id, session_key, visit_date, visited_at)
         VALUES (:store_id, :session_key, CURDATE(), NOW())'
    );
    $stmt->execute([
        'store_id' => $storeId,
        'session_key' => session_key(),
    ]);
}

function track_product_view(int $productId, int $storeId): void
{
    $stmt = db()->prepare(
        'INSERT IGNORE INTO product_views (product_id, store_id, session_key, view_date, viewed_at)
         VALUES (:product_id, :store_id, :session_key, CURDATE(), NOW())'
    );
    $stmt->execute([
        'product_id' => $productId,
        'store_id' => $storeId,
        'session_key' => session_key(),
    ]);
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
    return trim($value, '-');
}

function search_word_tokens(string $value): array
{
    if (!preg_match_all('/[\p{L}\p{N}]+/u', $value, $matches)) {
        return [];
    }

    return array_values(array_filter($matches[0], static fn(string $token): bool => $token !== ''));
}

function find_featured_products(int $limit = 4): array
{
    ensure_store_operational_columns();

    $stmt = db()->prepare(
        'SELECT p.*,
                COALESCE(rv.rating, 0) AS rating,
                COALESCE(rv.review_count, 0) AS review_count,
                s.name AS store_name,
                s.slug AS store_slug
         FROM products p
         INNER JOIN stores s ON s.id = p.store_id
         LEFT JOIN (
             SELECT product_id, ROUND(AVG(stars), 1) AS rating, COUNT(*) AS review_count
             FROM reviews
             GROUP BY product_id
         ) rv ON rv.product_id = p.id
         WHERE p.is_active = 1 AND s.is_active = 1
         ORDER BY p.is_featured DESC, COALESCE(rv.rating, 0) DESC, p.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function find_products(array $filters = []): array
{
    ensure_store_operational_columns();

    $conditions = ['p.is_active = 1', 's.is_active = 1'];
    $params = [];

    if (!empty($filters['search'])) {
        $searchTokens = search_word_tokens((string) $filters['search']);

        if (!$searchTokens) {
            $conditions[] = '1 = 0';
        }

        foreach ($searchTokens as $index => $token) {
            $param = 'search_' . $index;
            $conditions[] = "LOWER(CONCAT(' ', p.name, ' ', p.region, ' ', s.name)) LIKE :{$param}";
            $params[$param] = '% ' . strtolower($token) . '%';
        }
    }

    if (!empty($filters['type']) && in_array($filters['type'], ['Makanan', 'Minuman'], true)) {
        $conditions[] = 'p.type = :type';
        $params['type'] = $filters['type'];
    }

    if (!empty($filters['region'])) {
        $conditions[] = 'p.region = :region';
        $params['region'] = $filters['region'];
    }

    $sql = sprintf(
        'SELECT p.*,
                COALESCE(rv.rating, 0) AS rating,
                COALESCE(rv.review_count, 0) AS review_count,
                s.name AS store_name,
                s.slug AS store_slug
         FROM products p
         INNER JOIN stores s ON s.id = p.store_id
         LEFT JOIN (
             SELECT product_id, ROUND(AVG(stars), 1) AS rating, COUNT(*) AS review_count
             FROM reviews
             GROUP BY product_id
         ) rv ON rv.product_id = p.id
         WHERE %s
         ORDER BY p.is_featured DESC, p.name ASC',
        implode(' AND ', $conditions)
    );

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function product_regions(): array
{
    ensure_store_operational_columns();

    $stmt = db()->query(
        'SELECT DISTINCT p.region
         FROM products p
         INNER JOIN stores s ON s.id = p.store_id
         WHERE p.is_active = 1 AND s.is_active = 1
         ORDER BY p.region ASC'
    );

    return array_values(array_filter(array_map(
        static fn($row) => trim((string) ($row['region'] ?? '')),
        $stmt->fetchAll()
    )));
}

function indonesia_provinces(): array
{
    static $provinces = null;

    if (is_array($provinces)) {
        return $provinces;
    }

    $names = [];

    try {
        $stmt = db()->query('SELECT name FROM provinces ORDER BY name ASC');
        $names = array_map(
            static fn($row) => trim((string) ($row['name'] ?? '')),
            $stmt->fetchAll()
        );
    } catch (Throwable $exception) {
        $names = [];
    }

    if (!$names) {
        $sqlFile = __DIR__ . '/../indonesia.sql';
        $sql = is_file($sqlFile) ? file_get_contents($sqlFile) : false;

        if (is_string($sql) && preg_match('/-- Dumping data for table `provinces`(?P<block>.*?)(?:ALTER TABLE `provinces` ENABLE KEYS)/s', $sql, $blockMatch)) {
            preg_match_all(
                "/\\('([^']+)'\\s*,\\s*'((?:[^'\\\\]|\\\\.)*)'\\)/",
                $blockMatch['block'],
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $names[] = trim(str_replace("\\'", "'", $match[2]));
            }
        }
    }

    $names = array_values(array_unique(array_filter($names)));
    sort($names, SORT_NATURAL | SORT_FLAG_CASE);

    $provinces = $names;

    return $provinces;
}

function render_province_options(?string $selected = null): void
{
    $selected = trim((string) $selected);
    $provinces = indonesia_provinces();
    $hasSelected = $selected !== '' && in_array($selected, $provinces, true);

    echo '<option value="">Pilih provinsi</option>';

    if ($selected !== '' && !$hasSelected) {
        echo '<option value="' . e($selected) . '" selected>' . e($selected) . ' (data saat ini)</option>';
    }

    foreach ($provinces as $province) {
        $isSelected = $selected === $province ? ' selected' : '';
        echo '<option value="' . e($province) . '"' . $isSelected . '>' . e($province) . '</option>';
    }
}

function find_product_by_slug(string $slug): ?array
{
    ensure_store_operational_columns();

    $stmt = db()->prepare(
        'SELECT p.*,
                COALESCE(rv.rating, 0) AS rating,
                COALESCE(rv.review_count, 0) AS review_count,
                s.name AS store_name,
                s.slug AS store_slug,
                s.whatsapp,
                s.instagram,
                s.address,
                s.description AS store_description
         FROM products p
         INNER JOIN stores s ON s.id = p.store_id
         LEFT JOIN (
             SELECT product_id, ROUND(AVG(stars), 1) AS rating, COUNT(*) AS review_count
             FROM reviews
             GROUP BY product_id
         ) rv ON rv.product_id = p.id
         WHERE p.slug = :slug AND p.is_active = 1 AND s.is_active = 1
         LIMIT 1'
    );
    $stmt->execute(['slug' => $slug]);

    return $stmt->fetch() ?: null;
}

function find_reviews_by_product(int $productId): array
{
    ensure_review_replies_table();

    $stmt = db()->prepare(
        'SELECT r.*,
                u.name AS reviewer_name,
                rr.id AS reply_id,
                rr.reply_text,
                rr.created_at AS reply_created_at,
                rr.updated_at AS reply_updated_at,
                rr.admin_user_id AS reply_admin_user_id,
                au.name AS reply_admin_name,
                au.role AS reply_admin_role
         FROM reviews r
         INNER JOIN users u ON u.id = r.user_id
         LEFT JOIN review_replies rr ON rr.review_id = r.id
         LEFT JOIN users au ON au.id = rr.admin_user_id
         WHERE r.product_id = :product_id
         ORDER BY r.created_at DESC'
    );
    $stmt->execute(['product_id' => $productId]);

    return $stmt->fetchAll();
}

function ensure_review_replies_table(): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS review_replies (
          id INT AUTO_INCREMENT PRIMARY KEY,
          review_id INT NOT NULL,
          admin_user_id INT NULL,
          reply_text TEXT NOT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uniq_review_replies_review (review_id),
          CONSTRAINT fk_review_replies_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
          CONSTRAINT fk_review_replies_admin FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL
        )'
    );

    $checked = true;
}

function find_stores(?string $search = null): array
{
    ensure_store_operational_columns();

    $sql = 'SELECT s.*,
                   COUNT(DISTINCT p.id) AS product_count
            FROM stores s
            LEFT JOIN products p ON p.store_id = s.id AND p.is_active = 1
            WHERE s.is_active = 1';
    $params = [];

    if ($search) {
        $sql .= ' AND (s.name LIKE :search OR s.region LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $sql .= ' GROUP BY s.id ORDER BY s.name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function find_store_by_slug(string $slug): ?array
{
    ensure_store_operational_columns();

    $stmt = db()->prepare('SELECT * FROM stores WHERE slug = :slug AND is_active = 1 LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $store = $stmt->fetch();

    if (!$store) {
        return null;
    }

    $productStmt = db()->prepare(
        'SELECT p.*,
                COALESCE(rv.rating, 0) AS rating,
                COALESCE(rv.review_count, 0) AS review_count
         FROM products p
         LEFT JOIN (
             SELECT product_id, ROUND(AVG(stars), 1) AS rating, COUNT(*) AS review_count
             FROM reviews
             GROUP BY product_id
         ) rv ON rv.product_id = p.id
         WHERE p.store_id = :store_id AND p.is_active = 1
         ORDER BY p.is_featured DESC, p.name ASC'
    );
    $productStmt->execute(['store_id' => $store['id']]);
    $store['products'] = $productStmt->fetchAll();

    return $store;
}

function find_user_by_id(int $id): ?array
{
    ensure_user_auth_columns();

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);

    return $stmt->fetch() ?: null;
}

function find_user_by_email(string $email, bool $activeOnly = false): ?array
{
    ensure_user_auth_columns();

    $email = strtolower(trim($email));
    $sql = 'SELECT * FROM users WHERE email = :email';

    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }

    $stmt = db()->prepare($sql . ' LIMIT 1');
    $stmt->execute(['email' => $email]);

    return $stmt->fetch() ?: null;
}

function authenticate_user(string $email, string $password): ?array
{
    $user = find_user_by_email($email, true);

    if (!$user) {
        return null;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        return null;
    }

    return $user;
}

function user_needs_email_verification(array $user): bool
{
    return (($user['auth_provider'] ?? 'local') === 'local')
        && (int) ($user['email_verified'] ?? 0) !== 1;
}

function create_user(
    string $name,
    string $email,
    string $password,
    string $role = ROLE_USER,
    ?int $storeId = null,
    bool $emailVerified = true
): int {
    ensure_user_auth_columns();

    $stmt = db()->prepare(
        'INSERT INTO users
            (name, email, password_hash, profile_image, google_id, picture, auth_provider, email_verified, email_verify_token, email_verify_expires, reset_token, reset_expires, role, store_id, is_active, created_at, updated_at)
         VALUES
            (:name, :email, :password_hash, NULL, NULL, NULL, :auth_provider, :email_verified, NULL, NULL, NULL, NULL, :role, :store_id, 1, NOW(), NOW())'
    );
    $stmt->execute([
        'name' => $name,
        'email' => strtolower(trim($email)),
        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        'auth_provider' => 'local',
        'email_verified' => $emailVerified ? 1 : 0,
        'role' => $role,
        'store_id' => $storeId,
    ]);

    return (int) db()->lastInsertId();
}

function make_auth_token(): string
{
    return bin2hex(random_bytes(32));
}

function auth_token_hash(string $token): string
{
    return hash('sha256', $token);
}

function create_email_verification_token(int $userId, int $ttlSeconds = 86400): string
{
    ensure_user_auth_columns();

    $token = make_auth_token();
    $expires = date('Y-m-d H:i:s', time() + $ttlSeconds);

    $stmt = db()->prepare(
        'UPDATE users
         SET email_verify_token = :token_hash,
             email_verify_expires = :expires,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'token_hash' => auth_token_hash($token),
        'expires' => $expires,
        'id' => $userId,
    ]);

    return $token;
}

function verify_user_email_token(string $email, string $token): bool
{
    ensure_user_auth_columns();

    $email = strtolower(trim($email));
    $token = trim($token);

    if ($email === '' || $token === '') {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT id
         FROM users
         WHERE email = :email
           AND email_verify_token = :token_hash
           AND email_verify_expires IS NOT NULL
           AND email_verify_expires >= NOW()
         LIMIT 1'
    );
    $stmt->execute([
        'email' => $email,
        'token_hash' => auth_token_hash($token),
    ]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    $updateStmt = db()->prepare(
        'UPDATE users
         SET email_verified = 1,
             email_verify_token = NULL,
             email_verify_expires = NULL,
             updated_at = NOW()
         WHERE id = :id'
    );
    $updateStmt->execute(['id' => $user['id']]);

    return true;
}

function create_password_reset_token(int $userId, int $ttlSeconds = 3600): string
{
    ensure_user_auth_columns();

    $token = make_auth_token();
    $expires = date('Y-m-d H:i:s', time() + $ttlSeconds);

    $stmt = db()->prepare(
        'UPDATE users
         SET reset_token = :token_hash,
             reset_expires = :expires,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'token_hash' => auth_token_hash($token),
        'expires' => $expires,
        'id' => $userId,
    ]);

    return $token;
}

function find_user_by_reset_token(string $email, string $token): ?array
{
    ensure_user_auth_columns();

    $email = strtolower(trim($email));
    $token = trim($token);

    if ($email === '' || $token === '') {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT *
         FROM users
         WHERE email = :email
           AND reset_token = :token_hash
           AND reset_expires IS NOT NULL
           AND reset_expires >= NOW()
           AND email_verified = 1
           AND is_active = 1
         LIMIT 1'
    );
    $stmt->execute([
        'email' => $email,
        'token_hash' => auth_token_hash($token),
    ]);

    return $stmt->fetch() ?: null;
}

function reset_user_password_with_token(string $email, string $token, string $password): bool
{
    $user = find_user_by_reset_token($email, $token);

    if (!$user) {
        return false;
    }

    $stmt = db()->prepare(
        'UPDATE users
         SET password_hash = :password_hash,
             reset_token = NULL,
             reset_expires = NULL,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        'id' => $user['id'],
    ]);

    return true;
}

function save_uploaded_profile_image(array $file, ?string $currentPath = null): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $currentPath ?: '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload foto profil gagal diproses.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format foto profil harus JPG, PNG, WEBP, atau GIF.');
    }

    $uploadDir = __DIR__ . '/../uploads/profiles';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Folder upload foto profil tidak dapat dibuat.');
    }

    $filename = uniqid('profile_', true) . '.' . $allowed[$mime];
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Gagal menyimpan foto profil.');
    }

    return 'uploads/profiles/' . $filename;
}

function ensure_user_profile_image_column(): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    $stmt = db()->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    if ($stmt->fetch()) {
        return;
    }

    db()->exec('ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER password_hash');
}

function ensure_user_auth_columns(): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    ensure_user_profile_image_column();

    $columns = [];
    $stmt = db()->query('SHOW COLUMNS FROM users');

    foreach ($stmt->fetchAll() as $column) {
        $field = (string) ($column['Field'] ?? '');

        if ($field !== '') {
            $columns[$field] = true;
        }
    }

    if (!isset($columns['google_id'])) {
        db()->exec('ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL AFTER profile_image');
    }

    if (!isset($columns['picture'])) {
        db()->exec('ALTER TABLE users ADD COLUMN picture TEXT NULL AFTER google_id');
    }

    $pdo = db();

    if (!isset($columns['auth_provider'])) {
        $pdo->exec("ALTER TABLE users ADD COLUMN auth_provider ENUM('local','google') NOT NULL DEFAULT 'local' AFTER picture");
    }

    if (!isset($columns['email_verified'])) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER auth_provider');
    }

    if (!isset($columns['email_verify_token'])) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email_verify_token VARCHAR(255) NULL AFTER email_verified');
    }

    if (!isset($columns['email_verify_expires'])) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email_verify_expires DATETIME NULL AFTER email_verify_token');
    }

    if (!isset($columns['reset_token'])) {
        $pdo->exec('ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL AFTER email_verify_expires');
    }

    if (!isset($columns['reset_expires'])) {
        $pdo->exec('ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL AFTER reset_token');
    }

    $pdo->exec(
        "UPDATE users
         SET email_verified = 1
         WHERE email_verified = 0
           AND email_verify_token IS NULL
           AND auth_provider = 'local'"
    );

    $checked = true;
}

function ensure_user_google_columns(): void
{
    ensure_user_auth_columns();
}

function ensure_store_operational_columns(): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $pdo = db();
    $pdo->exec(
        "ALTER TABLE stores
         ADD COLUMN IF NOT EXISTS operating_hours VARCHAR(500) NOT NULL DEFAULT 'Setiap hari, 08.00 - 21.00 WIB' AFTER description"
    );
    $pdo->exec(
        "ALTER TABLE stores
         MODIFY COLUMN operating_hours VARCHAR(500) NOT NULL DEFAULT 'Setiap hari, 08.00 - 21.00 WIB'"
    );
    $pdo->exec(
        'ALTER TABLE stores
         ADD COLUMN IF NOT EXISTS is_open TINYINT(1) NOT NULL DEFAULT 1 AFTER cover_image'
    );

    $pdo->exec(
        'ALTER TABLE stores
         ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_open'
    );

    $checked = true;
}

function update_current_user_profile(
    int $userId,
    string $name,
    string $email,
    ?string $password = null,
    ?string $profileImage = null
): void {
    ensure_user_profile_image_column();
    $params = [
        'id' => $userId,
        'name' => $name,
        'email' => $email,
    ];

    $sql = 'UPDATE users
            SET name = :name,
                email = :email,
                updated_at = NOW()';

    if ($profileImage !== null) {
        $sql .= ', profile_image = :profile_image';
        $params['profile_image'] = $profileImage;
    }

    if ($password !== null && $password !== '') {
        $sql .= ', password_hash = :password_hash';
        $params['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
    }

    $sql .= ' WHERE id = :id';

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
    } catch (Throwable $exception) {
        if (str_contains($exception->getMessage(), 'Duplicate entry')) {
            throw new RuntimeException('Email sudah dipakai akun lain.');
        }

        throw $exception;
    }
}

function save_uploaded_product_image(array $file, ?string $currentPath = null): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $currentPath ?: 'assets/image/image.png';
    }

    return save_uploaded_product_image_file($file);
}

function save_uploaded_product_image_file(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gambar gagal diproses.');
    }

    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Ukuran gambar produk maksimal 5MB per file.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
    }

    $uploadDir = __DIR__ . '/../uploads/products';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Folder upload tidak dapat dibuat.');
    }

    $filename = uniqid('product_', true) . '.' . $allowed[$mime];
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Gagal menyimpan file gambar.');
    }

    return 'uploads/products/' . $filename;
}

function product_upload_entries(array $files): array
{
    $names = $files['name'] ?? null;

    if ($names === null || $names === '') {
        return [];
    }

    if (!is_array($names)) {
        return [array_merge($files, ['name' => (string) $names])];
    }

    $entries = [];
    $count = count($names);

    for ($index = 0; $index < $count; $index++) {
        $entries[] = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return array_values(array_filter(
        $entries,
        static fn(array $file): bool => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ));
}

function save_uploaded_product_images(array $files, ?string $currentPath = null, bool $required = false): array
{
    $entries = product_upload_entries($files);

    if (!$entries) {
        if ($required && trim((string) $currentPath) === '') {
            throw new RuntimeException('Minimal satu gambar produk wajib diupload.');
        }

        return $currentPath ? [$currentPath] : [];
    }

    $paths = [];

    foreach ($entries as $file) {
        $paths[] = save_uploaded_product_image_file($file);
    }

    return $paths;
}

function ensure_product_images_table(): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS product_images (
          id INT AUTO_INCREMENT PRIMARY KEY,
          product_id INT NOT NULL,
          image_path VARCHAR(255) NOT NULL,
          sort_order INT NOT NULL DEFAULT 0,
          created_at DATETIME NOT NULL,
          CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )'
    );

    $checked = true;
}

function replace_product_images(int $productId, array $imagePaths): void
{
    ensure_product_images_table();

    $imagePaths = array_values(array_filter(array_map(
        static fn($path): string => trim((string) $path),
        $imagePaths
    )));

    if (!$imagePaths) {
        return;
    }

    db()->prepare('DELETE FROM product_images WHERE product_id = :product_id')->execute([
        'product_id' => $productId,
    ]);

    $stmt = db()->prepare(
        'INSERT INTO product_images (product_id, image_path, sort_order, created_at)
         VALUES (:product_id, :image_path, :sort_order, NOW())'
    );

    foreach ($imagePaths as $index => $path) {
        $stmt->execute([
            'product_id' => $productId,
            'image_path' => $path,
            'sort_order' => $index,
        ]);
    }
}

function product_existing_image_paths(array $product): array
{
    ensure_product_images_table();

    $productId = (int) ($product['id'] ?? 0);
    $fallback = trim((string) ($product['image_path'] ?? ''));
    $paths = [];

    if ($productId < 1) {
        return $fallback !== '' ? [$fallback] : [];
    }

    $stmt = db()->prepare(
        'SELECT image_path
         FROM product_images
         WHERE product_id = :product_id
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute(['product_id' => $productId]);

    $paths = array_values(array_filter(array_map(
        static fn(array $row): string => trim((string) ($row['image_path'] ?? '')),
        $stmt->fetchAll()
    )));

    if ($fallback !== '' && !in_array($fallback, $paths, true)) {
        array_unshift($paths, $fallback);
    }

    return array_values(array_unique($paths));
}

function product_image_paths(array $product): array
{
    $paths = product_existing_image_paths($product);

    return $paths ?: ['assets/image/image.png'];
}

function edited_product_image_paths(array $product, array $uploadedFiles, mixed $removedImages): array
{
    $existingPaths = product_existing_image_paths($product);
    $removedPaths = is_array($removedImages) ? $removedImages : [];
    $removedPaths = array_values(array_intersect(
        array_map(static fn($path): string => trim((string) $path), $removedPaths),
        $existingPaths
    ));

    $keptPaths = array_values(array_filter(
        $existingPaths,
        static fn(string $path): bool => !in_array($path, $removedPaths, true)
    ));

    $uploadedPaths = save_uploaded_product_images($uploadedFiles);
    $finalPaths = array_values(array_unique(array_filter(array_merge($keptPaths, $uploadedPaths))));

    if (!$finalPaths) {
        throw new RuntimeException('Minimal satu foto produk harus tersisa.');
    }

    return $finalPaths;
}

function render_product_image_delete_controls(array $product): void
{
    $paths = product_existing_image_paths($product);

    if (!$paths) {
        return;
    }
    ?>
    <div class="product-image-manager" data-product-image-manager>
      <span class="field-label">Foto Saat Ini</span>
      <div class="product-image-manager-grid">
        <?php foreach ($paths as $index => $path): ?>
          <label class="product-image-manager-item">
            <img src="<?= e(base_path($path)) ?>" alt="Foto produk <?= e((string) ($index + 1)) ?>" />
            <span class="product-image-manager-check">
              <input type="checkbox" name="remove_images[]" value="<?= e($path) ?>" data-product-image-remove />
              Hapus foto
            </span>
          </label>
        <?php endforeach; ?>
      </div>
      <span class="field-hint">Centang foto yang ingin dihapus. Minimal satu foto produk harus tersisa.</span>
    </div>
    <?php
}

function save_uploaded_store_image(array $file, ?string $currentPath = null): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $currentPath ?: 'assets/image/image.png';
    }

    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_OK);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $message = match ($uploadError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran foto toko melebihi batas upload server.',
            UPLOAD_ERR_PARTIAL => 'Foto toko hanya terupload sebagian. Silakan pilih file kembali.',
            default => 'Upload gambar toko gagal diproses.',
        };

        throw new RuntimeException($message);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
    }

    $uploadDir = __DIR__ . '/../uploads/stores';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Folder upload gambar toko tidak dapat dibuat.');
    }

    $filename = uniqid('store_', true) . '.' . $allowed[$mime];
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Gagal menyimpan file gambar toko.');
    }

    return 'uploads/stores/' . $filename;
}

function submit_product_review(int $productId, int $userId, int $stars, string $reviewText): void
{
    $stmt = db()->prepare(
        'INSERT INTO reviews (product_id, user_id, stars, review_text, created_at, updated_at)
         VALUES (:product_id, :user_id, :stars, :review_text, NOW(), NOW())'
    );
    $stmt->execute([
        'product_id' => $productId,
        'user_id' => $userId,
        'stars' => $stars,
        'review_text' => $reviewText,
    ]);

    recalculate_product_rating($productId);
}

function save_review_reply(int $reviewId, int $productId, int $adminUserId, string $replyText): bool
{
    ensure_review_replies_table();

    $reviewStmt = db()->prepare(
        'SELECT id
         FROM reviews
         WHERE id = :id AND product_id = :product_id
         LIMIT 1'
    );
    $reviewStmt->execute([
        'id' => $reviewId,
        'product_id' => $productId,
    ]);

    if (!$reviewStmt->fetch()) {
        return false;
    }

    $stmt = db()->prepare(
        'INSERT INTO review_replies (review_id, admin_user_id, reply_text, created_at, updated_at)
         VALUES (:review_id, :admin_user_id, :reply_text, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
             admin_user_id = VALUES(admin_user_id),
             reply_text = VALUES(reply_text),
             updated_at = NOW()'
    );
    $stmt->execute([
        'review_id' => $reviewId,
        'admin_user_id' => $adminUserId,
        'reply_text' => $replyText,
    ]);

    return true;
}

function delete_review_reply(int $reviewId, int $productId): bool
{
    ensure_review_replies_table();

    $stmt = db()->prepare(
        'DELETE FROM review_replies
         WHERE review_id IN (
             SELECT id
             FROM reviews
             WHERE id = :review_id AND product_id = :product_id
         )'
    );
    $stmt->execute([
        'review_id' => $reviewId,
        'product_id' => $productId,
    ]);

    return $stmt->rowCount() > 0;
}

function delete_product_review(int $reviewId, int $productId, int $userId): bool
{
    ensure_review_replies_table();

    $stmt = db()->prepare(
        'DELETE FROM reviews
         WHERE id = :id AND product_id = :product_id AND user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([
        'id' => $reviewId,
        'product_id' => $productId,
        'user_id' => $userId,
    ]);

    if ($stmt->rowCount() < 1) {
        return false;
    }

    recalculate_product_rating($productId);

    return true;
}

function delete_product_review_by_manager(int $reviewId, int $productId): bool
{
    ensure_review_replies_table();

    $stmt = db()->prepare(
        'DELETE FROM reviews
         WHERE id = :id AND product_id = :product_id
         LIMIT 1'
    );
    $stmt->execute([
        'id' => $reviewId,
        'product_id' => $productId,
    ]);

    if ($stmt->rowCount() < 1) {
        return false;
    }

    recalculate_product_rating($productId);

    return true;
}

function recalculate_product_rating(int $productId): void
{
    $stmt = db()->prepare(
        'SELECT
            COALESCE(AVG(stars), 0) AS rating,
            COUNT(id) AS review_count
         FROM reviews
         WHERE product_id = :product_id'
    );
    $stmt->execute(['product_id' => $productId]);
    $row = $stmt->fetch();

    if (!$row) {
        return;
    }

    $rating = round((float) $row['rating'], 1);
    $reviewCount = (int) $row['review_count'];

    $updateStmt = db()->prepare(
        'UPDATE products
         SET rating = :rating,
             review_count = :review_count,
             updated_at = NOW()
         WHERE id = :id'
    );
    $updateStmt->execute([
        'rating' => $rating,
        'review_count' => $reviewCount,
        'id' => $productId,
    ]);
}

function favorite_product_id(array $product): string
{
    return (string) ($product['slug'] ?? slugify((string) ($product['name'] ?? 'item')));
}

function find_store_products(int $storeId): array
{
    $stmt = db()->prepare(
        'SELECT p.*, COUNT(v.id) AS total_views
         FROM products p
         LEFT JOIN product_views v ON v.product_id = p.id
         WHERE p.store_id = :store_id
         GROUP BY p.id
         ORDER BY p.created_at DESC'
    );
    $stmt->execute(['store_id' => $storeId]);

    return $stmt->fetchAll();
}

function store_dashboard_stats(int $storeId): array
{
    $stats = [];
    $queries = [
        'total_products' => 'SELECT COUNT(*) FROM products WHERE is_active = 1 AND store_id = :store_id',
        'store_visitors' => 'SELECT COUNT(*) FROM store_visits WHERE store_id = :store_id',
        'monthly_visitors' => 'SELECT COUNT(*) FROM store_visits WHERE store_id = :store_id AND visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
        'product_views' => 'SELECT COUNT(*) FROM product_views WHERE store_id = :store_id',
    ];

    foreach ($queries as $key => $sql) {
        $stmt = db()->prepare($sql);
        $stmt->execute(['store_id' => $storeId]);
        $stats[$key] = (int) $stmt->fetchColumn();
    }

    $topStmt = db()->prepare(
        'SELECT p.name, p.rating, COUNT(v.id) AS total_views
         FROM products p
         LEFT JOIN product_views v ON v.product_id = p.id
         WHERE p.store_id = :store_id
         GROUP BY p.id
         ORDER BY total_views DESC, p.name ASC'
    );
    $topStmt->execute(['store_id' => $storeId]);
    $stats['top_products'] = $topStmt->fetchAll();

    return $stats;
}

function super_admin_stats(): array
{
    return [
        'users' => (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'stores' => (int) db()->query('SELECT COUNT(*) FROM stores')->fetchColumn(),
        'products' => (int) db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
        'views' => (int) db()->query('SELECT COUNT(*) FROM product_views')->fetchColumn(),
    ];
}

function all_users(): array
{
    return db()->query(
        'SELECT u.*, s.name AS store_name
         FROM users u
         LEFT JOIN stores s ON s.id = u.store_id
         ORDER BY u.created_at DESC'
    )->fetchAll();
}

function all_stores_with_admins(): array
{
    return db()->query(
        'SELECT s.*,
                COUNT(DISTINCT p.id) AS product_count,
                GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ", ") AS admins
         FROM stores s
         LEFT JOIN products p ON p.store_id = s.id AND p.is_active = 1
         LEFT JOIN users u ON u.store_id = s.id AND u.role = "store_admin"
         GROUP BY s.id
         ORDER BY s.created_at DESC'
    )->fetchAll();
}

function all_products_for_admin(): array
{
    return db()->query(
        'SELECT p.*, s.name AS store_name
         FROM products p
         INNER JOIN stores s ON s.id = p.store_id
         ORDER BY p.created_at DESC'
    )->fetchAll();
}

function paginate_array(array $items, int $page, int $perPage): array
{
    $total = count($items);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'items' => array_slice($items, $offset, $perPage),
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

function render_product_card(array $product, array $options = []): void
{
    $favoriteId = favorite_product_id($product);
    $catalogCard = (bool) ($options['catalog_card'] ?? false);
    $cardClass = 'food-card' . ($catalogCard ? ' food-card--catalog is-clickable' : '');
    $detailPath = base_path('product.php?slug=' . $product['slug']);
?>
    <div class="<?= e($cardClass) ?>" data-favorite-id="<?= e($favoriteId) ?>">
        <?php if ($catalogCard): ?>
            <a class="food-card-detail-link" href="<?= e($detailPath) ?>" aria-label="Lihat detail <?= e($product['name']) ?>">
        <?php endif; ?>
        <div class="card-image">
            <img src="<?= e(base_path($product['image_path'])) ?>" alt="<?= e($product['name']) ?>" />
            <div class="image-tags">
                <span><?= e($product['region']) ?></span>
            </div>
            <?php if (!$catalogCard): ?>
                <button class="fav-btn" type="button" aria-label="Simpan ke favorit"></button>
            <?php endif; ?>
        </div>
        <div class="card-content">
            <div class="card-meta-line">
                <span class="food-store"><?= e($product['store_name']) ?></span>
            </div>
            <h3 class="food-title"><?= e($product['name']) ?></h3>
            <p class="food-desc"><?= e($product['short_description']) ?></p>
            <div class="food-rating">
                <span class="stars"><?= rating_stars_html((float) $product['rating']) ?></span>
                <span class="review"><?= e(number_format((float) $product['rating'], 1)) ?> • <?= e(number_short((int) $product['review_count'])) ?> ulasan</span>
            </div>
            <p class="food-price"><?= e(rupiah($product['price_display'])) ?></p>
        </div>
        <?php if ($catalogCard): ?>
            </a>
            <button class="fav-btn" type="button" aria-label="Simpan ke favorit"></button>
        <?php else: ?>
            <div class="card-footer">
                <span class="food-tag"><?= e($product['tag_label']) ?></span>
                <button class="detail-btn" type="button" onclick="window.location.href='<?= e($detailPath) ?>'">Detail</button>
            </div>
        <?php endif; ?>
    </div>
<?php
}

function render_layout(string $title, callable $content, array $options = []): void
{
    $user = current_user();
    $flash = get_flash();
    $pageClass = $options['body_class'] ?? '';
    $includeAppCss = $options['app_css'] ?? true;
    $includeDashboardCss = $options['dashboard_css'] ?? false;
    $includeTentangCss = $options['tentang_css'] ?? false;
    $includeLoginCss = $options['login_css'] ?? false;
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $hideFooter = ($options['hide_footer'] ?? false) || in_array($currentPage, ['login.php', 'register.php'], true);
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?= e($title) ?> | <?= e(app_name()) ?></title>
        <link rel="icon" type="image/x-icon" href="<?= e(base_path('assets/image/PusakaRasa.webp')) ?>" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
        <link rel="stylesheet" href="<?= e(base_path('assets/css/variables.css')) ?>" />
        <link rel="stylesheet" href="<?= e(base_path('assets/css/base.css')) ?>" />
        <link rel="stylesheet" href="<?= e(base_path('assets/css/component.css')) ?>" />
        <?php if ($includeAppCss): ?>
            <link rel="stylesheet" href="<?= e(base_path('assets/css/style.css')) ?>" />
            <link rel="stylesheet" href="<?= e(base_path('assets/css/app/core-ui.css')) ?>" />
            <link rel="stylesheet" href="<?= e(base_path('assets/css/app/discovery-catalog.css')) ?>" />
            <link rel="stylesheet" href="<?= e(base_path('assets/css/app/detail-favorites.css')) ?>" />
            <link rel="stylesheet" href="<?= e(base_path('assets/css/app/product-detail.css')) ?>" />
            <link rel="stylesheet" href="<?= e(base_path('assets/css/app/auth.css')) ?>" />
            <link rel="stylesheet" href="<?= e(base_path('assets/css/app/profile-table.css')) ?>" />
            <link rel="stylesheet" href="<?= e(base_path('assets/css/app/responsive.css')) ?>" />
        <?php endif; ?>
        <?php if ($includeDashboardCss): ?>
            <link rel="stylesheet" href="<?= e(base_path('assets/css/dashboard.css')) ?>" />
        <?php endif; ?>
        <?php if ($includeTentangCss): ?>
            <link rel="stylesheet" href="<?= e(base_path('assets/css/tentang.css')) ?>" />
        <?php endif; ?>
        <?php if ($includeLoginCss): ?>
            <link rel="stylesheet" href="<?= e(base_path('assets/css/login.css')) ?>" />
        <?php endif; ?>
    </head>

    <body class="<?= e($pageClass) ?>">
        <?php if ($flash): ?>
            <div class="flash-stack">
                <div class="flash-banner flash-<?= e($flash['type']) ?>" role="status" aria-live="polite"><?= e($flash['message']) ?></div>
            </div>
        <?php endif; ?>
        <?php if (!($options['hide_header'] ?? false)): ?>
            <header>
                <h1><?= e(app_name()) ?></h1>
                <nav>
                    <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">☰</button>
                    <ul id="navMenu">
                        <li><a href="<?= e(base_path('index.php')) ?>" <?= $currentPage === 'index.php' ? 'class="active"' : '' ?>>Beranda</a></li>
                        <li><a href="<?= e(base_path('katalog.php')) ?>" <?= $currentPage === 'katalog.php' ? 'class="active"' : '' ?>>Katalog</a></li>
                        <li><a href="<?= e(base_path('store.php')) ?>" <?= $currentPage === 'store.php' ? 'class="active"' : '' ?>>Toko</a></li>
                        <li><a href="<?= e(base_path('favorites.php')) ?>" <?= $currentPage === 'favorites.php' ? 'class="active"' : '' ?>>Favorit</a></li>
                        <li><a href="<?= e(base_path('tentang.php')) ?>" <?= $currentPage === 'tentang.php' ? 'class="active"' : '' ?>>Tentang</a></li>
                        <?php if (!$user): ?>
                            <li class="mobile-auth"><a href="<?= e(base_path('login.php')) ?>" class="login">Masuk</a></li>
                            <li class="mobile-auth"><a href="<?= e(base_path('register.php')) ?>" class="register">Daftar</a></li>
                        <?php else: ?>
                            <li class="mobile-auth"><a href="<?= e(base_path('edit-profile.php')) ?>" class="mobile-profile-link"><i class="fa-regular fa-user"></i>Dashboard Profil</a></li>
                            <li class="mobile-auth"><a href="<?= e(base_path('favorites.php')) ?>" class="mobile-profile-link"><i class="fa-regular fa-heart"></i>Favorit</a></li>
                            <?php if ($user['role'] !== ROLE_USER): ?>
                                <li class="mobile-auth"><a href="<?= e(base_path(nav_target_for_user($user))) ?>" class="mobile-profile-link"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
                            <?php endif; ?>
                            <li class="mobile-auth"><a href="<?= e(base_path('logout.php')) ?>" class="mobile-logout-link"><i class="fa-solid fa-arrow-right-from-bracket"></i>Keluar</a></li>
                        <?php endif; ?>
                    </ul>
                    <span class="nav-indicator"></span>
                </nav>
                <div class="auth-buttons">
                    <?php if (!$user): ?>
                        <a href="<?= e(base_path('login.php')) ?>"><button class="btn btn-secondary">Masuk</button></a>
                        <a href="<?= e(base_path('register.php')) ?>"><button class="btn btn-primary">Daftar</button></a>
                    <?php else: ?>
                        <div class="profile-menu" id="profileMenu">
                            <button type="button" class="header-pill profile-trigger" id="profileMenuButton" aria-label="Buka menu profil" aria-haspopup="true" aria-expanded="false">
                                <?php if (user_profile_image_url($user) !== ''): ?>
                                    <img class="profile-avatar profile-avatar-image" src="<?= e(user_profile_image_url($user)) ?>" alt="<?= e($user['name']) ?>" />
                                <?php else: ?>
                                    <span class="profile-avatar"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                                <?php endif; ?>
                            </button>
                            <div class="profile-dropdown" id="profileDropdown">
                                <div class="profile-header">
                                    <?php if (user_profile_image_url($user) !== ''): ?>
                                        <img class="profile-avatar large profile-avatar-image" src="<?= e(user_profile_image_url($user)) ?>" alt="<?= e($user['name']) ?>" />
                                    <?php else: ?>
                                        <div class="profile-avatar large"><i class="fa-regular fa-user" aria-hidden="true"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= e($user['name']) ?></strong>
                                        <span><?= e($user['email']) ?></span>
                                    </div>
                                </div>
                                <a href="<?= e(base_path('edit-profile.php')) ?>"><i class="fa-regular fa-user"></i>Dashboard Profil</a>
                                <a href="<?= e(base_path('favorites.php')) ?>"><i class="fa-regular fa-heart"></i>Favorit</a>
                                <?php if ($user['role'] !== ROLE_USER): ?>
                                    <a href="<?= e(base_path(nav_target_for_user($user))) ?>"><i class="fa-solid fa-table-columns"></i>Dashboard</a>
                                <?php endif; ?>
                                <a href="<?= e(base_path('logout.php')) ?>" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i>Keluar</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </header>
        <?php endif; ?>

        <main>
            <?php $content($user); ?>
        </main>

        <?php if (!$hideFooter): ?>
            <footer class="footer">
                <div class="footer-content">
                    <div class="footer-section about">
                        <h3><?= e(app_name()) ?></h3>
                        <p>Katalog kuliner Indonesia untuk edukasi generasi muda, promosi UMKM, dan pelestarian budaya</p>
                    </div>

                    <div class="footer-section navigation">
                        <h4>Navigasi</h4>
                        <ul>
                            <li><a href="<?= e(base_path('index.php')) ?>">Beranda</a></li>
                            <li><a href="<?= e(base_path('katalog.php')) ?>">Katalog</a></li>
                            <li><a href="<?= e(base_path('favorites.php')) ?>">Favorit</a></li>
                        </ul>
                    </div>

                    <div class="footer-section support">
                        <h4>Dukungan</h4>
                        <ul>
                            <li><a href="<?= e(base_path('cs.php')) ?>">Customer Service</a></li>
                            <li><a href="<?= e(base_path('cs.php')) ?>">Kontak</a></li>
                            <li><a href="<?= e(base_path('cs.php')) ?>">Sosial Media</a></li>
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <p>© <?= date('Y') ?> <?= e(app_name()) ?> — All rights reserved.</p>
                    <a href="#">Kebijakan Privasi</a>
                    <a href="#">S&K</a>
                </div>
            </footer>
        <?php endif; ?>

        <script>
            (() => {
                const syncOperatingHourInputs = (scope = document) => {
                    scope.querySelectorAll('.operating-hours-status').forEach((select) => {
                        const row = select.closest('.operating-hours-row');
                        if (!row) return;

                        const isClosed = select.value === 'Tutup';
                        row.querySelectorAll('.operating-hours-input').forEach((input) => {
                            input.disabled = isClosed;
                            input.required = !isClosed;
                        });
                    });
                };

                document.addEventListener('change', (event) => {
                    if (!(event.target instanceof Element) || !event.target.matches('.operating-hours-status')) return;
                    syncOperatingHourInputs(event.target.closest('.operating-hours-row') ?? document);
                });
                document.addEventListener('reset', (event) => {
                    if (event.target instanceof Element) {
                        window.setTimeout(() => syncOperatingHourInputs(event.target), 0);
                    }
                }, true);
                syncOperatingHourInputs();
            })();
        </script>
        <script src="<?= e(base_path('assets/js/main.js')) ?>"></script>
        <?php if (($options['include_detail_js'] ?? false)): ?>
            <script src="<?= e(base_path('assets/js/detail.js')) ?>"></script>
        <?php endif; ?>
    </body>

    </html>
<?php
}
