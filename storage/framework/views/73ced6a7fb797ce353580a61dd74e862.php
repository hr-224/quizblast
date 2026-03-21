<?php $__env->startSection('title', 'Join a Game'); ?>

<?php $__env->startSection('content'); ?>
<div style="min-height:calc(100vh - 63px);display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,var(--qb-purple) 0%,var(--qb-darker) 55%);padding:2rem 1rem">
  <div style="width:100%;max-width:420px">

    <div style="text-align:center;margin-bottom:2rem">
      <div style="font-size:3rem;margin-bottom:.5rem">⚡</div>
      <h1 style="font-size:2.8rem;font-family:'Montserrat',sans-serif;font-weight:900;text-transform:uppercase;letter-spacing:-1px">QuizBlast</h1>
      <p style="color:rgba(255,255,255,.6);margin-top:.4rem;font-size:.95rem">Enter a game PIN to join</p>
    </div>

    <?php if(request('kicked')): ?>
      <div class="alert alert-error mb-2" style="text-align:center">You were kicked from the game.</div>
    <?php endif; ?>

    <div class="card slide-up" style="background:var(--qb-card);border:2px solid rgba(255,255,255,.12)">
      <form method="POST" action="<?php echo e(route('play.join.post')); ?>">
        <?php echo csrf_field(); ?>
        <div class="form-group">
          <label class="form-label">Game PIN</label>
          <input type="text" name="pin" class="form-control" value="<?php echo e(old('pin', request('pin'))); ?>"
                 placeholder="Enter PIN" maxlength="6" inputmode="numeric"
                 style="font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;letter-spacing:.25em;text-align:center;padding:1rem"
                 autofocus required />
          <?php $__errorArgs = ['pin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="field-error" style="text-align:center;display:block"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
          <label class="form-label">Nickname</label>
          <input type="text" name="nickname" class="form-control" value="<?php echo e(old('nickname')); ?>"
                 placeholder="Enter nickname" maxlength="20"
                 style="font-family:'Montserrat',sans-serif;font-size:1.1rem;font-weight:700;text-align:center"
                 required />
          <?php $__errorArgs = ['nickname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="field-error" style="text-align:center;display:block"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <button type="submit" class="btn btn-success btn-full btn-xl" style="margin-top:.5rem;font-size:1.1rem;letter-spacing:1px">
          JOIN GAME →
        </button>
      </form>
    </div>

    <div style="margin-top:1.25rem;display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
      <p style="color:rgba(255,255,255,.5);font-size:.82rem;text-align:center">
        Want to host? <a href="<?php echo e(route('register')); ?>" style="color:var(--qb-yellow);font-weight:700">Create a host account</a>
      </p>
      <?php if(!session('player_account_id')): ?>
        <p style="color:rgba(255,255,255,.5);font-size:.82rem;text-align:center">
          Track your stats? <a href="<?php echo e(route('player.register')); ?>" style="color:var(--qb-cyan);font-weight:700">Create a player account</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/quizblast/resources/views/play/join.blade.php ENDPATH**/ ?>