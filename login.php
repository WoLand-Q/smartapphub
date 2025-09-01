<?php
require_once __DIR__.'/helpers.php';

// уже вошёл — на главную
if (is_logged_in()) { header('Location: index.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u==='' || $p==='') {
        $err = __('Enter login and password');
    } else {
        if (login($u,$p)) {
            $next = $_GET['next'] ?? 'index.php';
            header('Location: ' . $next); exit;
        } else {
            $err = __('Invalid login or password');
        }
    }
}

include __DIR__.'/partials_header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item active"><?=__('Login')?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-hover">
            <div class="card-body">
                <h5 class="card-title mb-3"><?=__('Login')?></h5>
                <?php if($err): ?><div class="alert alert-danger py-2"><?=e($err)?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label"><?=__('Username')?></label>
                        <input class="form-control" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?=__('Password')?></label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100"><?=__('Sign in')?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__.'/partials_footer.php'; ?>
