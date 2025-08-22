<!-- <a href="#"
    data-href="{{ $reset_url }}"
    data-id="{{ $model->id }}"
    id="update-user"
    class="btn btn-icon btn-info btn-sm"
    title="Update User">
        <i class="fas fa-pencil-alt"></i>
</a> -->
<a href="#"
    data-href="{{ $reset_url }}"
    data-id="{{ $model->id }}"
    id="reset-password"
    class="btn btn-icon btn-warning btn-sm"
    title="Reset Password">
        <i class="fas fa-key"></i>
</a>
<a href="#"
    data-href="{{ $destroy_url }}"
    data-id="{{ $model->id }}"
    id="destroy-user"
    class="btn btn-icon btn-danger btn-sm"
    title="Delete User">
        <i class="fa fa-trash"></i>
</a>
