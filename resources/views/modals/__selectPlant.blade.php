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
              @if(!empty($plants))
                @foreach($plants as $plant)
                  <option value="{{ $plant->code_3 }}" {{ $selectedPlant == $plant->code_3 ? 'selected' : '' }}>
                    {{ $plant->code_2 }} ({{ $plant->code_3 }})
                  </option>
                @endforeach
              @endif
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" id="confirmPlantSelect" class="btn btn-success">Confirm</button>
        </div>
      </div>
    </div>
  </div>