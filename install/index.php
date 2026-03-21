<?php
/**
 * QuizBlast Web Installer
 *
 * A self-contained installer that configures the application,
 * tests the database connection, runs migrations, and creates
 * the first admin account — all through the browser.
 */

define('QB_ROOT', realpath(__DIR__ . '/..'));
define('QB_LOCK', __DIR__ . '/.installed');
define('QB_ENV',  QB_ROOT . '/.env');

// ─── Lock check ──────────────────────────────────────────────────────────────
$isInstalled = file_exists(QB_LOCK);

// ─── AJAX / POST handlers ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    // Test database connection
    if ($action === 'test_db') {
        echo json_encode(testDb(
            trim($_POST['db_host']     ?? ''),
            trim($_POST['db_port']     ?? '3306'),
            trim($_POST['db_database'] ?? ''),
            trim($_POST['db_username'] ?? ''),
            $_POST['db_password'] ?? ''
        ));
        exit;
    }

    // Run the full installation
    if ($action === 'install') {
        if ($isInstalled) {
            echo json_encode(['ok' => false, 'log' => ['Already installed.']]);
            exit;
        }
        echo json_encode(runInstall($_POST));
        exit;
    }

    echo json_encode(['ok' => false, 'log' => ['Unknown action.']]);
    exit;
}

// ─── Helper: test DB connection ───────────────────────────────────────────────
function testDb(string $host, string $port, string $db, string $user, string $pass): array {
    if (!$host || !$db || !$user) {
        return ['ok' => false, 'message' => 'Host, database name, and username are required.'];
    }
    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        // Check if the named database exists (or we can create it)
        $stmt = $pdo->query("SHOW DATABASES LIKE " . $pdo->quote($db));
        if ($stmt->fetch()) {
            return ['ok' => true, 'message' => "Connected. Database \"{$db}\" exists."];
        }
        // Try creating it
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return ['ok' => true, 'message' => "Connected. Database \"{$db}\" created."];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

// ─── Helper: run artisan command ──────────────────────────────────────────────
function artisan(string $command): array {
    $php     = PHP_BINARY;
    $artisan = QB_ROOT . '/artisan';
    $cmd     = escapeshellcmd("{$php} {$artisan} {$command} 2>&1");
    exec($cmd, $out, $code);
    return ['output' => implode("\n", $out), 'code' => $code];
}

// ─── Helper: write .env ───────────────────────────────────────────────────────
function writeEnv(array $cfg): void {
    $reverbKey    = bin2hex(random_bytes(16));
    $reverbSecret = bin2hex(random_bytes(32));
    $url = rtrim($cfg['app_url'], '/');

    $env = <<<ENV
APP_NAME={$cfg['app_name']}
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL={$url}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$cfg['db_host']}
DB_PORT={$cfg['db_port']}
DB_DATABASE={$cfg['db_database']}
DB_USERNAME={$cfg['db_username']}
DB_PASSWORD={$cfg['db_password']}

BROADCAST_DRIVER=reverb
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=database
SESSION_LIFETIME=120

MAIL_MAILER=log

REVERB_APP_ID=quizblast
REVERB_APP_KEY={$reverbKey}
REVERB_APP_SECRET={$reverbSecret}
REVERB_ALLOWED_ORIGINS=*

REVERB_HOST={$cfg['reverb_host']}
REVERB_PORT={$cfg['reverb_port']}
REVERB_SCHEME={$cfg['reverb_scheme']}
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=7001
ENV;

    file_put_contents(QB_ENV, $env);
}

// ─── Helper: create admin user via PDO ────────────────────────────────────────
function createAdmin(PDO $pdo, string $name, string $email, string $password): void {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $now  = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $hash, $now, $now]);
}

// ─── Helper: insert sample quiz via PDO ───────────────────────────────────────
function createSampleQuiz(PDO $pdo, int $userId): void {
    $now = date('Y-m-d H:i:s');
    $pdo->prepare('INSERT INTO quizzes (user_id, title, description, is_public, created_at, updated_at) VALUES (?, ?, ?, 1, ?, ?)')
        ->execute([$userId, 'General Knowledge Blast', 'A mix of fun trivia questions', $now, $now]);
    $quizId = (int) $pdo->lastInsertId();

    $questions = [
        ['What is the capital of France?', 20, [['Paris', true], ['London', false], ['Berlin', false], ['Madrid', false]]],
        ['How many planets are in our solar system?', 15, [['7', false], ['8', true], ['9', false], ['10', false]]],
        ['Which element has the chemical symbol "Au"?', 20, [['Silver', false], ['Gold', true], ['Copper', false], ['Iron', false]]],
        ['What year did World War II end?', 20, [['1943', false], ['1944', false], ['1945', true], ['1946', false]]],
        ['Which language is known as the "language of the web"?', 15, [['Python', false], ['JavaScript', true], ['Java', false], ['PHP', false]]],
    ];

    foreach ($questions as $qi => $q) {
        $pdo->prepare('INSERT INTO questions (quiz_id, question_text, time_limit, points, `order`, created_at, updated_at) VALUES (?, ?, ?, 1000, ?, ?, ?)')
            ->execute([$quizId, $q[0], $q[1], $qi, $now, $now]);
        $qId = (int) $pdo->lastInsertId();
        foreach ($q[2] as $ai => $a) {
            $pdo->prepare('INSERT INTO answers (question_id, answer_text, is_correct, `order`, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$qId, $a[0], $a[1] ? 1 : 0, $ai, $now, $now]);
        }
    }
}

// ─── Main install runner ──────────────────────────────────────────────────────
function runInstall(array $p): array {
    $log = [];

    // Validate required fields
    $required = ['db_host', 'db_port', 'db_database', 'db_username', 'app_name', 'app_url', 'admin_name', 'admin_email', 'admin_password'];
    foreach ($required as $field) {
        if (empty(trim($p[$field] ?? ''))) {
            return ['ok' => false, 'log' => ["Missing required field: {$field}"]];
        }
    }

    if (!filter_var($p['admin_email'], FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'log' => ['Invalid admin email address.']];
    }
    if (strlen($p['admin_password']) < 8) {
        return ['ok' => false, 'log' => ['Admin password must be at least 8 characters.']];
    }

    // 1. Test DB
    $log[] = '⏳ Testing database connection…';
    $dbTest = testDb($p['db_host'], $p['db_port'], $p['db_database'], $p['db_username'], $p['db_password'] ?? '');
    if (!$dbTest['ok']) {
        return ['ok' => false, 'log' => array_merge($log, ['❌ DB error: ' . $dbTest['message']])];
    }
    $log[] = '✅ Database connected.';

    // 2. Write .env
    $log[] = '⏳ Writing configuration…';
    $reverb = guessReverbConfig($p['app_url']);
    writeEnv([
        'app_name'     => $p['app_name'],
        'app_url'      => $p['app_url'],
        'db_host'      => $p['db_host'],
        'db_port'      => $p['db_port'],
        'db_database'  => $p['db_database'],
        'db_username'  => $p['db_username'],
        'db_password'  => $p['db_password'] ?? '',
        'reverb_host'  => $reverb['host'],
        'reverb_port'  => $reverb['port'],
        'reverb_scheme'=> $reverb['scheme'],
    ]);
    $log[] = '✅ Configuration written.';

    // 3. Generate app key
    $log[] = '⏳ Generating application key…';
    $result = artisan('key:generate --force --ansi');
    if ($result['code'] !== 0) {
        return ['ok' => false, 'log' => array_merge($log, ['❌ key:generate failed: ' . $result['output']])];
    }
    $log[] = '✅ Application key generated.';

    // 4. Run migrations
    $log[] = '⏳ Running database migrations…';
    $result = artisan('migrate --force');
    if ($result['code'] !== 0) {
        return ['ok' => false, 'log' => array_merge($log, ['❌ Migration failed: ' . $result['output']])];
    }
    $log[] = '✅ Migrations complete.';

    // 5. Create admin user via PDO
    $log[] = '⏳ Creating admin account…';
    try {
        $dsn = "mysql:host={$p['db_host']};port={$p['db_port']};dbname={$p['db_database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $p['db_username'], $p['db_password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        createAdmin($pdo, $p['admin_name'], $p['admin_email'], $p['admin_password']);
        $adminId = (int) $pdo->lastInsertId();
        $log[] = '✅ Admin account created.';

        // 6. Sample quiz (optional)
        if (!empty($p['sample_quiz'])) {
            $log[] = '⏳ Creating sample quiz…';
            createSampleQuiz($pdo, $adminId);
            $log[] = '✅ Sample quiz created.';
        }
    } catch (PDOException $e) {
        return ['ok' => false, 'log' => array_merge($log, ['❌ ' . $e->getMessage()])];
    }

    // 7. Cache config
    artisan('config:cache');
    artisan('view:clear');

    // 8. Write lock file
    file_put_contents(QB_LOCK, date('c'));
    $log[] = '✅ Installation complete!';

    return ['ok' => true, 'log' => $log];
}

// ─── Helper: guess Reverb config from app URL ─────────────────────────────────
function guessReverbConfig(string $appUrl): array {
    $parsed = parse_url($appUrl);
    $scheme = ($parsed['scheme'] ?? 'http') === 'https' ? 'https' : 'http';
    $host   = $parsed['host'] ?? '127.0.0.1';
    $port   = $scheme === 'https' ? '443' : '8080';
    return ['host' => $host, 'port' => $port, 'scheme' => $scheme];
}

// ─── Requirements check ───────────────────────────────────────────────────────
function checkRequirements(): array {
    $checks = [];

    $phpOk = PHP_VERSION_ID >= 80200;
    $checks[] = ['label' => 'PHP 8.2+', 'ok' => $phpOk, 'value' => PHP_VERSION];

    $exts = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'curl'];
    foreach ($exts as $ext) {
        $checks[] = ['label' => "ext-{$ext}", 'ok' => extension_loaded($ext), 'value' => extension_loaded($ext) ? 'loaded' : 'missing'];
    }

    $dirs = [
        QB_ROOT . '/storage'         => 'storage/',
        QB_ROOT . '/bootstrap/cache' => 'bootstrap/cache/',
    ];
    foreach ($dirs as $path => $label) {
        $ok = is_writable($path);
        $checks[] = ['label' => "{$label} writable", 'ok' => $ok, 'value' => $ok ? 'writable' : 'not writable'];
    }

    $vendorOk = is_dir(QB_ROOT . '/vendor');
    $checks[] = ['label' => 'Composer dependencies', 'ok' => $vendorOk, 'value' => $vendorOk ? 'installed' : 'run composer install first'];

    return $checks;
}

$checks     = checkRequirements();
$allOk      = array_reduce($checks, fn($c, $r) => $c && $r['ok'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>QuizBlast Installer</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg:      #0e0b1e;
    --darker:  #080612;
    --surface: #1a1535;
    --border:  rgba(255,255,255,.1);
    --purple:  #6c3ee8;
    --cyan:    #00d2ff;
    --yellow:  #ffd000;
    --green:   #26890c;
    --red:     #e21b3c;
    --muted:   rgba(255,255,255,.45);
    --radius:  10px;
  }
  body { background: var(--bg); color: #fff; font-family: 'Segoe UI', system-ui, sans-serif; font-size: 15px; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 2rem 1rem; }
  .brand { font-size: 2rem; font-weight: 900; letter-spacing: -1px; margin-bottom: 2rem; }
  .brand span { color: var(--purple); }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 2rem; width: 100%; max-width: 560px; }
  h2 { font-size: 1.25rem; margin-bottom: 1.5rem; color: #fff; }
  /* Steps */
  .steps { display: flex; gap: 0; margin-bottom: 2rem; width: 100%; max-width: 560px; }
  .step-dot { flex: 1; text-align: center; position: relative; }
  .step-dot::before { content: ''; position: absolute; top: 14px; left: 50%; right: -50%; height: 2px; background: var(--border); z-index: 0; }
  .step-dot:last-child::before { display: none; }
  .step-dot .dot { width: 28px; height: 28px; border-radius: 50%; background: var(--darker); border: 2px solid var(--border); display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; position: relative; z-index: 1; }
  .step-dot.active .dot { background: var(--purple); border-color: var(--purple); }
  .step-dot.done .dot { background: var(--green); border-color: var(--green); }
  .step-dot .label { font-size: .65rem; color: var(--muted); margin-top: 4px; display: block; }
  .step-dot.active .label { color: #fff; }
  /* Forms */
  .field { margin-bottom: 1.2rem; }
  label { display: block; font-size: .8rem; color: var(--muted); margin-bottom: .4rem; text-transform: uppercase; letter-spacing: .4px; }
  input[type=text], input[type=url], input[type=email], input[type=password], input[type=number] {
    width: 100%; background: var(--darker); border: 1px solid var(--border); border-radius: 8px;
    color: #fff; padding: .65rem .9rem; font-size: .95rem; outline: none; transition: border-color .15s;
  }
  input:focus { border-color: var(--purple); }
  input.error { border-color: var(--red); }
  .row-2 { display: grid; grid-template-columns: 1fr 120px; gap: .75rem; }
  .row-3 { display: grid; grid-template-columns: 1fr 120px 120px; gap: .75rem; }
  /* Buttons */
  .btn { display: inline-block; padding: .7rem 1.6rem; border-radius: 8px; font-size: .9rem; font-weight: 700; cursor: pointer; border: none; transition: opacity .15s, transform .1s; }
  .btn:active { transform: scale(.97); }
  .btn-primary { background: var(--purple); color: #fff; }
  .btn-secondary { background: var(--border); color: #fff; }
  .btn:disabled { opacity: .4; cursor: default; }
  .btn-sm { padding: .45rem 1rem; font-size: .8rem; }
  .btn-row { display: flex; gap: .75rem; justify-content: flex-end; margin-top: 1.5rem; }
  /* Checks */
  .check-row { display: flex; align-items: center; justify-content: space-between; padding: .55rem 0; border-bottom: 1px solid var(--border); font-size: .88rem; }
  .check-row:last-child { border: none; }
  .check-ok  { color: #4ade80; font-weight: 700; }
  .check-bad { color: var(--red); font-weight: 700; }
  .check-val { color: var(--muted); font-size: .78rem; }
  /* DB test */
  #db-test-result { font-size: .82rem; margin-top: .6rem; padding: .5rem .8rem; border-radius: 6px; display: none; }
  #db-test-result.ok  { background: rgba(38,137,12,.15); border: 1px solid rgba(38,137,12,.35); color: #4ade80; }
  #db-test-result.err { background: rgba(226,27,60,.12); border: 1px solid rgba(226,27,60,.35); color: #f87171; }
  /* Log */
  #install-log { background: var(--darker); border-radius: 8px; padding: 1rem; font-family: monospace; font-size: .82rem; line-height: 1.7; max-height: 240px; overflow-y: auto; color: var(--muted); margin-bottom: 1rem; display: none; }
  #install-log .ok  { color: #4ade80; }
  #install-log .err { color: #f87171; }
  #install-log .pending { color: var(--yellow); }
  /* Screens */
  .screen { display: none; }
  .screen.active { display: block; }
  /* Already installed */
  .installed-notice { text-align: center; padding: 1rem 0; }
  .installed-notice .icon { font-size: 3rem; margin-bottom: 1rem; }
  /* Checkbox */
  .check-field { display: flex; align-items: center; gap: .6rem; cursor: pointer; font-size: .9rem; margin-top: .5rem; }
  .check-field input { width: auto; }
  /* Success */
  .success-card { text-align: center; }
  .success-card .icon { font-size: 3.5rem; margin-bottom: 1rem; }
  .success-card h2 { font-size: 1.5rem; margin-bottom: .5rem; }
  .success-card p  { color: var(--muted); margin-bottom: 1.5rem; }
  .creds { background: var(--darker); border-radius: 8px; padding: 1rem; text-align: left; margin: 1rem 0; font-size: .88rem; }
  .creds dt { color: var(--muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .4px; }
  .creds dd { font-family: monospace; margin: 0 0 .5rem; }
  a.btn { text-decoration: none; }
</style>
</head>
<body>

<div class="brand">⚡ Quiz<span>Blast</span></div>

<?php if ($isInstalled): ?>
<!-- ── Already Installed ──────────────────────────────────────────────────── -->
<div class="card">
  <div class="installed-notice">
    <div class="icon">✅</div>
    <h2>Already Installed</h2>
    <p style="color:var(--muted);margin:.5rem 0 1.5rem">QuizBlast is already set up.</p>
    <a href="/" class="btn btn-primary">Open QuizBlast →</a>
  </div>
</div>

<?php else: ?>
<!-- ── Step indicators ────────────────────────────────────────────────────── -->
<div class="steps" id="steps-bar">
  <div class="step-dot active" id="sdot-1"><div class="dot">1</div><span class="label">Requirements</span></div>
  <div class="step-dot"        id="sdot-2"><div class="dot">2</div><span class="label">Database</span></div>
  <div class="step-dot"        id="sdot-3"><div class="dot">3</div><span class="label">Settings</span></div>
  <div class="step-dot"        id="sdot-4"><div class="dot">4</div><span class="label">Install</span></div>
</div>

<div class="card">

  <!-- ── Screen 1: Requirements ─────────────────────────────────────────── -->
  <div class="screen active" id="screen-1">
    <h2>Requirements Check</h2>
    <?php foreach ($checks as $c): ?>
    <div class="check-row">
      <span><?= htmlspecialchars($c['label']) ?></span>
      <span>
        <span class="check-val"><?= htmlspecialchars($c['value']) ?></span>
        &nbsp;<span class="<?= $c['ok'] ? 'check-ok' : 'check-bad' ?>"><?= $c['ok'] ? '✓' : '✗' ?></span>
      </span>
    </div>
    <?php endforeach ?>
    <div class="btn-row">
      <?php if (!$allOk): ?>
        <span style="color:var(--red);font-size:.82rem;align-self:center">Fix the issues above, then refresh.</span>
      <?php endif ?>
      <button class="btn btn-primary" id="btn-req-next" <?= $allOk ? '' : 'disabled' ?>>Next →</button>
    </div>
  </div>

  <!-- ── Screen 2: Database ─────────────────────────────────────────────── -->
  <div class="screen" id="screen-2">
    <h2>Database Connection</h2>
    <p style="color:var(--muted);font-size:.85rem;margin-bottom:1.2rem">MySQL or MariaDB required. The database will be created if it doesn't exist.</p>
    <div class="row-3">
      <div class="field">
        <label>Host</label>
        <input type="text" id="db_host" value="127.0.0.1" placeholder="127.0.0.1" />
      </div>
      <div class="field">
        <label>Port</label>
        <input type="number" id="db_port" value="3306" placeholder="3306" />
      </div>
      <div class="field">
        <label>Database</label>
        <input type="text" id="db_database" value="quizblast" placeholder="quizblast" />
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label>Username</label>
        <input type="text" id="db_username" value="root" placeholder="root" autocomplete="username" />
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" id="db_password" placeholder="(blank ok)" autocomplete="current-password" />
      </div>
    </div>
    <button class="btn btn-secondary btn-sm" id="btn-test-db">Test Connection</button>
    <div id="db-test-result"></div>
    <div class="btn-row">
      <button class="btn btn-secondary" id="btn-db-back">← Back</button>
      <button class="btn btn-primary"   id="btn-db-next">Next →</button>
    </div>
  </div>

  <!-- ── Screen 3: App Settings ─────────────────────────────────────────── -->
  <div class="screen" id="screen-3">
    <h2>Site Settings</h2>
    <div class="row-2">
      <div class="field">
        <label>Site Name</label>
        <input type="text" id="app_name" value="QuizBlast" placeholder="QuizBlast" />
      </div>
      <div class="field">
        <label>Site URL</label>
        <input type="url" id="app_url" value="<?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?>" placeholder="https://example.com" />
      </div>
    </div>
    <p style="color:var(--muted);font-size:.82rem;margin-bottom:1rem">Admin account — used to log in and host games.</p>
    <div class="field">
      <label>Your Name</label>
      <input type="text" id="admin_name" placeholder="e.g. Alex" autocomplete="name" />
    </div>
    <div class="row-2">
      <div class="field">
        <label>Email</label>
        <input type="email" id="admin_email" placeholder="you@example.com" autocomplete="email" />
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" id="admin_password" placeholder="8+ characters" autocomplete="new-password" />
      </div>
    </div>
    <label class="check-field">
      <input type="checkbox" id="sample_quiz" checked />
      Include sample quiz content (5 questions)
    </label>
    <div class="btn-row">
      <button class="btn btn-secondary" id="btn-settings-back">← Back</button>
      <button class="btn btn-primary"   id="btn-settings-next">Install →</button>
    </div>
  </div>

  <!-- ── Screen 4: Installing ───────────────────────────────────────────── -->
  <div class="screen" id="screen-4">
    <h2>Installing QuizBlast</h2>
    <div id="install-log"></div>
    <div id="install-spinner" style="color:var(--muted);font-size:.88rem">Preparing installation…</div>
    <div class="btn-row" id="install-done-row" style="display:none">
      <button class="btn btn-primary" id="btn-to-site">Open QuizBlast →</button>
    </div>
  </div>

</div><!-- .card -->
<?php endif ?>

<script>
(function () {
  'use strict';

  // ── Step navigation ────────────────────────────────────────────────────────
  let currentScreen = 1;

  function showScreen(n) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    document.getElementById('screen-' + n).classList.add('active');

    document.querySelectorAll('.step-dot').forEach((d, i) => {
      d.classList.remove('active', 'done');
      if (i + 1 < n)      d.classList.add('done');
      else if (i + 1 === n) d.classList.add('active');
    });

    currentScreen = n;
  }

  // ── Screen 1 → 2 ──────────────────────────────────────────────────────────
  document.getElementById('btn-req-next').addEventListener('click', () => showScreen(2));

  // ── Screen 2: DB test ─────────────────────────────────────────────────────
  document.getElementById('btn-test-db').addEventListener('click', async function () {
    const btn = this;
    const res = document.getElementById('db-test-result');
    btn.disabled = true; btn.textContent = 'Testing…';
    res.style.display = 'none';

    try {
      const r = await post({ action: 'test_db', ...dbValues() });
      res.className = r.ok ? 'ok' : 'err';
      res.textContent = r.message;
      res.style.display = 'block';
    } catch (e) {
      res.className = 'err';
      res.textContent = 'Request failed: ' + e.message;
      res.style.display = 'block';
    } finally {
      btn.disabled = false; btn.textContent = 'Test Connection';
    }
  });

  document.getElementById('btn-db-back').addEventListener('click', () => showScreen(1));
  document.getElementById('btn-db-next').addEventListener('click', () => {
    const v = dbValues();
    if (!v.db_host || !v.db_database || !v.db_username) {
      alert('Host, database name, and username are required.'); return;
    }
    showScreen(3);
  });

  // ── Screen 3 → 4 ──────────────────────────────────────────────────────────
  document.getElementById('btn-settings-back').addEventListener('click', () => showScreen(2));
  document.getElementById('btn-settings-next').addEventListener('click', () => {
    const name  = document.getElementById('admin_name').value.trim();
    const email = document.getElementById('admin_email').value.trim();
    const pass  = document.getElementById('admin_password').value;
    const url   = document.getElementById('app_url').value.trim();
    const sname = document.getElementById('app_name').value.trim();
    if (!sname || !url)   { alert('Site name and URL are required.'); return; }
    if (!name || !email)  { alert('Admin name and email are required.'); return; }
    if (!email.includes('@')) { alert('Enter a valid email address.'); return; }
    if (pass.length < 8)  { alert('Password must be at least 8 characters.'); return; }
    showScreen(4);
    runInstall();
  });

  // ── Install ────────────────────────────────────────────────────────────────
  async function runInstall() {
    const log    = document.getElementById('install-log');
    const spinner = document.getElementById('install-spinner');
    log.style.display = 'block';
    spinner.textContent = 'Running installation — this may take 10–30 seconds…';

    function addLine(text, cls) {
      const el = document.createElement('div');
      el.textContent = text;
      if (cls) el.className = cls;
      log.appendChild(el);
      log.scrollTop = log.scrollHeight;
    }

    addLine('Starting QuizBlast installation…', 'pending');

    try {
      const r = await post({
        action:         'install',
        ...dbValues(),
        app_name:       document.getElementById('app_name').value.trim(),
        app_url:        document.getElementById('app_url').value.trim(),
        admin_name:     document.getElementById('admin_name').value.trim(),
        admin_email:    document.getElementById('admin_email').value.trim(),
        admin_password: document.getElementById('admin_password').value,
        sample_quiz:    document.getElementById('sample_quiz').checked ? '1' : '',
      });

      (r.log || []).forEach(line => {
        const cls = line.startsWith('✅') ? 'ok' : line.startsWith('❌') ? 'err' : 'pending';
        addLine(line, cls);
      });

      if (r.ok) {
        spinner.innerHTML = '<span style="color:#4ade80;font-weight:700">✅ Installation complete!</span>';
        document.getElementById('install-done-row').style.display = 'flex';
        document.getElementById('btn-to-site').addEventListener('click', () => {
          window.location.href = '/';
        });
      } else {
        spinner.innerHTML = '<span style="color:#f87171">Installation failed. See log above.</span>';
      }
    } catch (e) {
      addLine('❌ Request failed: ' + e.message, 'err');
      spinner.innerHTML = '<span style="color:#f87171">Installation failed.</span>';
    }
  }

  // ── Helpers ────────────────────────────────────────────────────────────────
  function dbValues() {
    return {
      db_host:     document.getElementById('db_host').value.trim(),
      db_port:     document.getElementById('db_port').value.trim(),
      db_database: document.getElementById('db_database').value.trim(),
      db_username: document.getElementById('db_username').value.trim(),
      db_password: document.getElementById('db_password').value,
    };
  }

  async function post(data) {
    const body = new URLSearchParams(data);
    const res  = await fetch(window.location.href, { method: 'POST', body });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
  }
})();
</script>
</body>
</html>
