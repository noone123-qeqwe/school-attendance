<div class="saas-modal-backdrop" id="{{ $editorId }}" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.7); align-items:center; justify-content:center;">
    <div class="saas-card" style="width:100%; max-width:560px;">
        <div class="saas-card-header d-flex justify-content-between align-items-center">
            <h2 class="saas-heading saas-heading-sm m-0">Edit {{ $recordType }}</h2>
            <button type="button" class="saas-btn saas-btn-secondary" data-admin-close-dialog aria-label="Close dialog"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <form method="POST" data-admin-record-form>
            @csrf
            @method('PUT')
            <div class="saas-card-body">
                @if(isset($editorDepartments))
                    <div class="saas-form-group">
                        <label class="saas-label" for="{{ $editorId }}Department">Department</label>
                        <select id="{{ $editorId }}Department" name="department_id" class="saas-input saas-select" required>
                            <option value="">Choose department</option>
                            @foreach($editorDepartments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="saas-form-group">
                    <label class="saas-label" for="{{ $editorId }}Code">{{ $recordType }} code</label>
                    <input id="{{ $editorId }}Code" name="code" type="text" class="saas-input" maxlength="{{ $recordType === 'department' ? 20 : 50 }}" required>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label" for="{{ $editorId }}Name">{{ ucfirst($recordType) }} name</label>
                    <input id="{{ $editorId }}Name" name="name" type="text" class="saas-input" maxlength="255" required>
                </div>
                <div class="saas-form-group mb-0">
                    <label class="saas-label" for="{{ $editorId }}Description">Description <span class="saas-text-muted">(optional)</span></label>
                    <textarea id="{{ $editorId }}Description" name="description" class="saas-input" maxlength="1000" rows="3"></textarea>
                </div>
            </div>
            <div class="saas-card-body d-flex gap-2 justify-content-end flex-wrap">
                <button type="button" class="saas-btn saas-btn-secondary" data-admin-close-dialog>Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</div>
