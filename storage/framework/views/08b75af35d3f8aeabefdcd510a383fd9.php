<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
  <title><?php echo $__env->yieldContent('title', 'QuizBlast'); ?> — QuizBlast</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="shortcut icon" href="/favicon.svg" />
  <link rel="stylesheet" href="/css/app.css" />
  <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <a href="<?php echo e(route('play.join')); ?>" class="navbar-brand">⚡ Quiz<span>Blast</span></a>
    <div class="navbar-nav">
      <a href="<?php echo e(route('library')); ?>" class="nav-link">Library</a>
      <?php if(auth()->guard()->check()): ?>
        <a href="<?php echo e(route('dashboard')); ?>" class="nav-link">Dashboard</a>
        <a href="<?php echo e(route('quizzes.create')); ?>" class="nav-link">New Quiz</a>
        <form method="POST" action="<?php echo e(route('logout')); ?>" style="display:inline">
          <?php echo csrf_field(); ?>
          <button type="submit" class="btn btn-outline btn-sm">Log out</button>
        </form>
      <?php else: ?>
        <a href="<?php echo e(route('play.join')); ?>" class="nav-link">Join Game</a>
        <?php if(session('player_account_id')): ?>
          <a href="<?php echo e(route('player.stats')); ?>" class="nav-link">My Stats</a>
          <form method="POST" action="<?php echo e(route('player.logout')); ?>" style="display:inline">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-outline btn-sm">Log out</button>
          </form>
        <?php else: ?>
          <a href="<?php echo e(route('player.login')); ?>" class="nav-link">My Stats</a>
          <a href="<?php echo e(route('login')); ?>" class="nav-link">Host Login</a>
          <a href="<?php echo e(route('register')); ?>" class="btn btn-white btn-sm">Host Sign Up</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main>
  <?php if(session('success')): ?>
    <div class="container mt-2"><div class="alert alert-success"><?php echo e(session('success')); ?></div></div>
  <?php endif; ?>
  <?php if(session('error')): ?>
    <div class="container mt-2"><div class="alert alert-error"><?php echo e(session('error')); ?></div></div>
  <?php endif; ?>

  <?php echo $__env->yieldContent('content'); ?>
</main>

<?php echo $__env->yieldPushContent('scripts'); ?>
<script>
document.querySelectorAll('.alert').forEach(function(el) {
  setTimeout(function() {
    el.style.transition = 'opacity 0.5s ease';
    el.style.opacity = '0';
    setTimeout(function() { el.remove(); }, 500);
  }, 3000);
});
</script>
</body>
</html>
<?php /**PATH /var/www/quizblast/resources/views/layouts/app.blade.php ENDPATH**/ ?>