<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('content'); ?>
<div style="background:var(--qb-purple);padding:2rem 0;border-bottom:3px solid rgba(0,0,0,0.3)">
  <div class="container">
    <div class="d-flex align-center justify-between">
      <div>
        <h1 style="font-size:1.8rem;text-transform:uppercase;letter-spacing:-0.5px">Your Quizzes</h1>
        <p style="color:rgba(255,255,255,0.6);margin-top:0.2rem;font-size:0.88rem">Create, manage, and host live quiz games</p>
      </div>
      <a href="<?php echo e(route('quizzes.create')); ?>" class="btn btn-white btn-lg">+ NEW QUIZ</a>
    </div>
  </div>
</div>
<div style="padding:2rem 0">
  <div class="container">
    <?php if($quizzes->isEmpty()): ?>
      <div class="card text-center" style="padding:3.5rem 2rem;border:2px dashed rgba(255,255,255,0.15)">
        <div style="font-size:3rem;margin-bottom:1rem">🎯</div>
        <h2 style="font-size:1.3rem;text-transform:uppercase;margin-bottom:0.5rem">No Quizzes Yet</h2>
        <p class="text-muted mb-3">Create your first quiz and start hosting live games</p>
        <a href="<?php echo e(route('quizzes.create')); ?>" class="btn btn-success btn-xl">CREATE A QUIZ</a>
      </div>
    <?php else: ?>
      <div class="grid-3">
        <?php $__currentLoopData = $quizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="quiz-card">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;margin-bottom:0.6rem">
              <div class="quiz-card-title"><?php echo e($quiz->title); ?></div>
              <?php if($quiz->is_public): ?>
                <span style="background:rgba(38,137,12,0.25);border:1px solid rgba(38,137,12,0.5);color:#5ddd3a;font-size:0.7rem;font-weight:800;padding:.15rem .5rem;border-radius:var(--radius);text-transform:uppercase;white-space:nowrap">Public</span>
              <?php endif; ?>
            </div>
            <?php if($quiz->description): ?>
              <p class="text-muted" style="font-size:0.82rem;margin-bottom:0.5rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?php echo e($quiz->description); ?></p>
            <?php endif; ?>
            <div class="quiz-card-meta">
              <span><?php echo e($quiz->questions_count); ?> question<?php echo e($quiz->questions_count != 1 ? 's' : ''); ?></span>
              <span><?php echo e($quiz->created_at->diffForHumans()); ?></span>
            </div>
            <div class="quiz-card-actions">
              <a href="<?php echo e(route('game.start', $quiz)); ?>" class="btn btn-success btn-sm">&#9654; HOST</a>
              <a href="<?php echo e(route('quizzes.edit', $quiz)); ?>" class="btn btn-outline btn-sm">EDIT</a>
              <form method="POST" action="<?php echo e(route('quizzes.destroy', $quiz)); ?>" onsubmit="return confirm('Delete this quiz?')">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button class="btn btn-danger btn-sm">X</button>
              </form>
            </div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    <?php endif; ?>
    <?php if($recentGames->isNotEmpty()): ?>
      <div class="mt-4">
        <h2 style="font-size:0.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--qb-muted);margin-bottom:0.75rem">Recent Games</h2>
        <div class="card" style="padding:0;overflow:hidden">
          <?php $__currentLoopData = $recentGames; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $statusColor = $game->status === 'finished' ? 'var(--qb-muted)' : '#5ddd3a'; ?>
            <div style="padding:0.85rem 1.25rem;border-bottom:1px solid var(--qb-border);display:flex;align-items:center;justify-content:space-between;<?php echo e($loop->last ? 'border-bottom:none' : ''); ?>">
              <div>
                <span style="font-weight:700;font-size:0.9rem;text-transform:uppercase"><?php echo e($game->quiz->title ?? 'Deleted Quiz'); ?></span>
                <span class="text-muted" style="font-size:0.8rem;margin-left:0.75rem">PIN <?php echo e($game->pin); ?></span>
              </div>
              <div style="display:flex;align-items:center;gap:1rem">
                <span class="stat-chip">
                  <span class="val" style="color:<?php echo e($statusColor); ?>"><?php echo e(strtoupper($game->status)); ?></span>
                </span>
                <span class="text-muted" style="font-size:0.78rem"><?php echo e($game->created_at->diffForHumans()); ?></span>
              </div>
            </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/quizblast/resources/views/dashboard/index.blade.php ENDPATH**/ ?>