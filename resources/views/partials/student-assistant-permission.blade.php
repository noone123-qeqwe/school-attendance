<div class="form-check mt-3">
    <input type="hidden" name="can_submit_attendance" value="0">
    <input class="form-check-input" type="checkbox" id="{{ $permissionId }}" name="can_submit_attendance" value="1"
           aria-describedby="{{ $permissionId }}Help" @checked($allowSubmission ?? false)>
    <label class="form-check-label" for="{{ $permissionId }}">Submit attendance for teacher review</label>
    <small class="d-block" id="{{ $permissionId }}Help">The teacher reviews submissions before any attendance record changes.</small>
</div>
