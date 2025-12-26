<div class="modal fade" id="modal-blending-editSubTank" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" style="z-index:1041">
    <div class="modal-dialog" role="document" style="max-width:480px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Assign Specific Sloc (Sub Tank)</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <form id="form-blendingEntryEditSubtank" method="post">
            @csrf
            <input type="hidden" name="flag" id="form-blendingEntryEditSubtank-flag" value="post_updateEntrySubTank" />
            <input type="hidden" name="mode" id="form-blendingEntryEditSubtank-mode" value="UPDATE" />
            <input type="hidden" name="idHead" id="form-blendingEntryEditSubtank-idHead" />
            <input type="hidden" name="idTank" id="form-blendingEntryEditSubtank-idTank" />
  
            <div class="form-group">
              <label>Main Sloc</label>
              <input name="mainSloc" id="form-blendingEntryEditSubtank-mainSloc" class="form-control" readonly>
            </div>
  
            <div class="form-group">
              <label>Select Specific Sloc</label>
              <select name="idTankTail[]" id="form-blendingEntryEditSubtank-tankNo" class="form-control" multiple="multiple" required>
              </select>
            </div>
  
            <div class="form-group text-right">
              <button class="btn btn-primary" id="save-blendingEntryEditSubtank">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>