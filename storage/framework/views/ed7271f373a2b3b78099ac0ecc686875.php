<div class="modal fade" id="modal-selectPlant" tabindex="-1" role="dialog" aria-labelledby="selectPlantLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="selectPlantLabel">Select Plant to Manage</h5>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="plantSelect">Choose Plant:</label>
            <select class="form-control" id="plantSelect">
              <option value="">-- Select Plant --</option>
              <?php if(!empty($plants)): ?>
                <?php $__currentLoopData = $plants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($plant->code_3); ?>" <?php echo e($selectedPlant == $plant->code_3 ? 'selected' : ''); ?>>
                    <?php echo e($plant->code_2); ?> (<?php echo e($plant->code_3); ?>)
                  </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              <?php endif; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" id="confirmPlantSelect" class="btn btn-success">Confirm</button>
        </div>
      </div>
    </div>
  </div><?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/modals/__selectPlant.blade.php ENDPATH**/ ?>