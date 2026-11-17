<?php
require __DIR__ . '/config.php';

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function logged_in(): bool { return !empty($_SESSION['user_id']); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf'] ?? '';
        if (!hash_equals(csrf_token(), $sent)) {
            http_response_code(400); exit('Invalid request token. Refresh and try again.');
        }
    }
}
function redirect_to(string $url = 'index.php'): never { header('Location: ' . $url); exit; }

$modules = [
    'patients' => [
        'title' => 'Patients', 'table' => 'patients',
        'fields' => [
            'name' => ['Patient name', 'text', true],
            'age' => ['Age', 'number', false],
            'gender' => ['Gender', 'select:Male,Female,Other', false],
            'phone' => ['Phone', 'text', false],
            'address' => ['Address', 'textarea', false],
        ],
        'columns' => ['id'=>'ID','name'=>'Name','age'=>'Age','gender'=>'Gender','phone'=>'Phone']
    ],
    'departments' => [
        'title' => 'Departments', 'table' => 'departments',
        'fields' => [
            'name' => ['Department name', 'text', true],
            'description' => ['Description', 'textarea', false],
        ],
        'columns' => ['id'=>'ID','name'=>'Department','description'=>'Description']
    ],
    'doctors' => [
        'title' => 'Doctors', 'table' => 'doctors',
        'fields' => [
            'name' => ['Doctor name', 'text', true],
            'specialization' => ['Specialization', 'text', true],
            'phone' => ['Phone', 'text', false],
            'email' => ['Email', 'email', false],
            'department_id' => ['Department', 'department', false],
        ],
        'columns' => ['id'=>'ID','name'=>'Doctor','specialization'=>'Specialization','phone'=>'Phone','department_id'=>'Department']
    ],
    'appointments' => [
        'title' => 'Appointments', 'table' => 'appointments',
        'fields' => [
            'patient_id' => ['Patient', 'patient', true],
            'doctor_id' => ['Doctor', 'doctor', true],
            'appointment_date' => ['Date', 'date', true],
            'appointment_time' => ['Time', 'time', true],
            'status' => ['Status', 'select:Scheduled,Completed,Cancelled', true],
            'notes' => ['Notes', 'textarea', false],
        ],
        'columns' => ['id'=>'ID','patient_id'=>'Patient','doctor_id'=>'Doctor','appointment_date'=>'Date','appointment_time'=>'Time','status'=>'Status']
    ],
    'bills' => [
        'title' => 'Bills', 'table' => 'bills',
        'fields' => [
            'patient_id' => ['Patient', 'patient', true],
            'amount' => ['Amount', 'number', true],
            'description' => ['Description', 'text', true],
            'payment_status' => ['Payment status', 'select:Unpaid,Paid,Partially Paid', true],
            'bill_date' => ['Bill date', 'date', true],
        ],
        'columns' => ['id'=>'ID','patient_id'=>'Patient','amount'=>'Amount','description'=>'Description','payment_status'=>'Payment status','bill_date'=>'Date']
    ],
];

$action = $_POST['action'] ?? '';
if ($action === 'login') {
    verify_csrf();
    $stmt = db()->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        redirect_to();
    }
    $login_error = 'Incorrect username or password.';
}
if ($action === 'logout') {
    verify_csrf(); $_SESSION = []; session_destroy(); redirect_to();
}
if (!logged_in()) {
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Login - <?= e(APP_NAME) ?></title><link rel="stylesheet" href="style.css"></head>
    <body class="login-page"><div class="login-card"><div class="brand-mark">+</div><h1>Hospital Management</h1><p class="muted">Sign in to manage hospital records</p>
    <?php if (!empty($login_error)): ?><div class="notice error"><?= e($login_error) ?></div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="login">
    <label>Username<input name="username" required autocomplete="username"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn primary full">Sign in</button></form><p class="small muted">Initial login: admin / ChangeMe123!</p></div></body></html>
    <?php
    exit;
}

verify_csrf();
$module = $_GET['module'] ?? 'dashboard';
if (!in_array($module, array_merge(['dashboard'], array_keys($modules)), true)) $module = 'dashboard';
$notice = '';
$error = '';

function lookup_name(string $type, $id): string {
    if (!$id) return '—';
    $map = ['patient'=>['patients','name'], 'doctor'=>['doctors','name'], 'department'=>['departments','name']];
    if (!isset($map[$type])) return '—';
    [$table,$field] = $map[$type];
    $stmt = db()->prepare("SELECT `$field` FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() ?: '—';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['save','delete','change_password'], true)) {
    try {
        if ($action === 'change_password') {
            $new = $_POST['new_password'] ?? '';
            if (strlen($new) < 10) throw new RuntimeException('Use a password with at least 10 characters.');
            $stmt = db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            $notice = 'Password updated.';
        } elseif ($action === 'delete') {
            $key = $_POST['module'] ?? '';
            if (!isset($modules[$key])) throw new RuntimeException('Invalid section.');
            $stmt = db()->prepare("DELETE FROM `{$modules[$key]['table']}` WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            $notice = 'Record deleted.';
            $module = $key;
        } else {
            $key = $_POST['module'] ?? '';
            if (!isset($modules[$key])) throw new RuntimeException('Invalid section.');
            $def = $modules[$key]; $values = [];
            foreach ($def['fields'] as $field => $meta) {
                $value = trim($_POST[$field] ?? '');
                if ($meta[2] && $value === '') throw new RuntimeException($meta[0] . ' is required.');
                if ($value === '') $value = null;
                if ($field === 'age' && $value !== null && ((int)$value < 0 || (int)$value > 120)) throw new RuntimeException('Enter a valid age.');
                if ($field === 'amount' && $value !== null && (float)$value < 0) throw new RuntimeException('Amount cannot be negative.');
                $values[$field] = $value;
            }
            if ($key === 'doctors' && !empty($values['email']) && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $set = implode(', ', array_map(fn($f) => "`$f` = ?", array_keys($values)));
                $stmt = db()->prepare("UPDATE `{$def['table']}` SET $set WHERE id = ?");
                $stmt->execute([...array_values($values), $id]);
                $notice = 'Record updated.';
            } else {
                $fields = array_keys($values);
                $quoted = implode(', ', array_map(fn($f) => "`$f`", $fields));
                $marks = implode(', ', array_fill(0, count($fields), '?'));
                $stmt = db()->prepare("INSERT INTO `{$def['table']}` ($quoted) VALUES ($marks)");
                $stmt->execute(array_values($values));
                $notice = 'Record added.';
            }
            $module = $key;
        }
    } catch (Throwable $ex) {
        $error = $ex instanceof PDOException ? 'Could not save this record. Check that related records exist and the values are valid.' : $ex->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'], $modules[$module])) {
    $stmt = db()->prepare("SELECT * FROM `{$modules[$module]['table']}` WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch();
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($module === 'dashboard' ? 'Dashboard' : $modules[$module]['title']) ?> - <?= e(APP_NAME) ?></title><link rel="stylesheet" href="style.css"></head>
<body><div class="layout">
<aside class="sidebar"><a class="logo" href="index.php"><span>+</span> MediCare</a><div class="side-label">MAIN MENU</div>
<a class="<?= $module==='dashboard'?'active':'' ?>" href="index.php">▦ Dashboard</a>
<?php foreach ($modules as $key=>$def): ?><a class="<?= $module===$key?'active':'' ?>" href="?module=<?= e($key) ?>">▤ <?= e($def['title']) ?></a><?php endforeach; ?>
<div class="side-bottom"><span>Signed in as</span><strong><?= e($_SESSION['username']) ?></strong><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button name="action" value="logout" class="logout">Sign out</button></form></div></aside>
<main class="main"><header class="topbar"><div><span class="eyebrow">HOSPITAL ADMINISTRATION</span><h1><?= e($module==='dashboard'?'Dashboard':$modules[$module]['title']) ?></h1></div><a class="avatar" href="?module=dashboard">A</a></header>
<?php if ($notice): ?><div class="notice success"><?= e($notice) ?></div><?php endif; ?><?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<?php if ($module === 'dashboard'): ?>
<section class="welcome"><div><span class="eyebrow">OVERVIEW</span><h2>Hospital at a glance</h2><p>Manage patients, doctors, appointments and billing records from one place.</p></div><div class="welcome-icon">✚</div></section>
<div class="stats">
<?php foreach (['patients'=>'Patients','doctors'=>'Doctors','appointments'=>'Appointments','departments'=>'Departments','bills'=>'Bills'] as $t=>$label): $count=(int)db()->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); ?>
<div class="stat-card"><span><?= e($label) ?></span><strong><?= $count ?></strong><small>Total records</small></div><?php endforeach; ?>
</div>
<section class="panel"><div class="panel-head"><div><h2>Quick access</h2><p>Open a section to add or manage records.</p></div></div><div class="quick-grid"><?php foreach ($modules as $key=>$def): ?><a href="?module=<?= e($key) ?>" class="quick-link"><span>＋</span><div><strong><?= e($def['title']) ?></strong><small>Manage <?= strtolower(e($def['title'])) ?></small></div>→</a><?php endforeach; ?></div></section>
<section class="panel"><div class="panel-head"><div><h2>Change administrator password</h2><p>Use a unique password and keep it private.</p></div></div><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="change_password"><input type="password" name="new_password" minlength="10" placeholder="New password (10+ characters)" required><button class="btn primary">Update password</button></form></section>
<?php else:
$def=$modules[$module]; $stmt=db()->query("SELECT * FROM `{$def['table']}` ORDER BY id DESC LIMIT 300"); $rows=$stmt->fetchAll();
?>
<div class="content-grid"><section class="panel form-panel"><div class="panel-head"><div><h2><?= $edit?'Edit':'Add' ?> <?= e(rtrim($def['title'],'s')) ?></h2><p>Enter the details below.</p></div></div>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="module" value="<?= e($module) ?>"><input type="hidden" name="id" value="<?= e($edit['id']??0) ?>">
<?php foreach ($def['fields'] as $field=>$meta): $value=$edit[$field]??''; ?>
<label><?= e($meta[0]) ?><?= $meta[2]?' *':'' ?>
<?php if (str_starts_with($meta[1], 'select:')): ?><select name="<?= e($field) ?>" <?= $meta[2]?'required':'' ?>><option value="">Choose...</option><?php foreach (explode(',',substr($meta[1],7)) as $opt): ?><option <?= $value===$opt?'selected':'' ?> value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?></select>
<?php elseif (in_array($meta[1], ['patient','doctor','department'], true)): $tableMap=['patient'=>'patients','doctor'=>'doctors','department'=>'departments']; $optRows=db()->query("SELECT id,name FROM `{$tableMap[$meta[1]]}` ORDER BY name")->fetchAll(); ?><select name="<?= e($field) ?>" <?= $meta[2]?'required':'' ?>><option value="">Choose...</option><?php foreach ($optRows as $opt): ?><option value="<?= e($opt['id']) ?>" <?= (string)$value===(string)$opt['id']?'selected':'' ?>><?= e($opt['name']) ?></option><?php endforeach; ?></select>
<?php elseif ($meta[1]==='textarea'): ?><textarea name="<?= e($field) ?>" rows="3"><?= e($value) ?></textarea>
<?php else: ?><input type="<?= e($meta[1]) ?>" name="<?= e($field) ?>" value="<?= e($value) ?>" <?= $meta[2]?'required':'' ?> <?= $field==='age'?'min="0" max="120"':'' ?> <?= $field==='amount'?'min="0" step="0.01"':'' ?>><?php endif; ?>
</label><?php endforeach; ?>
<div class="form-actions"><button class="btn primary"><?= $edit?'Save changes':'Add record' ?></button><?php if ($edit): ?><a class="btn secondary" href="?module=<?= e($module) ?>">Cancel</a><?php endif; ?></div></form></section>
<section class="panel table-panel"><div class="panel-head"><div><h2>All <?= e($def['title']) ?></h2><p>Latest 300 records.</p></div><span class="count-pill"><?= count($rows) ?> records</span></div>
<div class="table-wrap"><table><thead><tr><?php foreach ($def['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th>Actions</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="<?= count($def['columns'])+1 ?>" class="empty">No records yet. Add the first record using the form.</td></tr><?php endif; ?>
<?php foreach ($rows as $row): ?><tr><?php foreach ($def['columns'] as $col=>$label): ?><td><?php if ($col==='patient_id'): ?><?= e(lookup_name('patient',$row[$col])) ?><?php elseif ($col==='doctor_id'): ?><?= e(lookup_name('doctor',$row[$col])) ?><?php elseif ($col==='department_id'): ?><?= e(lookup_name('department',$row[$col])) ?><?php else: ?><?= e($row[$col]??'—') ?><?php endif; ?></td><?php endforeach; ?><td class="actions"><a href="?module=<?= e($module) ?>&edit=<?= e($row['id']) ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this record? Related records may also be deleted.');"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="module" value="<?= e($module) ?>"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><button class="text-danger">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></section></div>
<?php endif; ?>
<footer>Hospital Management System <span>•</span> Academic project</footer></main></div></body></html>
