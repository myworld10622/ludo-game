<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/Gateway/storeManual', [
                    'autocomplete' => false,
                    'id' => 'add_gateway',
                    'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_rummy_tournament_types')]);
                ?>

                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">Gateway Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="name" id="name" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="role" class="col-sm-2 col-form-label">Role *</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="role[]" id="role" multiple required>
                            <option value="">Select Role</option>
                            <!-- <option value="0">Super Admin</option> -->
                            <option value="1">Admin</option>
                            <option value="2">Agent</option>
                            <option value="3">Distributer</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="currency" class="col-sm-2 col-form-label">Currency *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="currency" id="currency" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="rate" class="col-sm-2 col-form-label">Rate *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" step="0.01" name="rate" id="rate" required>
                    </div>
                </div>

                <fieldset class="border p-3 mb-3">
                    <legend class="w-auto">Range</legend>
                    <div class="form-group row">
                        <label for="min_amount" class="col-sm-2 col-form-label">Minimum Amount *</label>
                        <div class="col-sm-4">
                            <input class="form-control" type="number" step="0.01" name="min_amount" id="min_amount"
                                required>
                        </div>
                        <label for="max_amount" class="col-sm-2 col-form-label">Maximum Amount *</label>
                        <div class="col-sm-4">
                            <input class="form-control" type="number" step="0.01" name="max_amount" id="max_amount"
                                required>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="border p-3 mb-3">
                    <legend class="w-auto">Charge</legend>
                    <div class="form-group row">
                        <label for="fixed_charge" class="col-sm-2 col-form-label">Fixed Charge *</label>
                        <div class="col-sm-4">
                            <input class="form-control" type="number" step="0.01" name="fixed_charge" id="fixed_charge"
                                required>
                        </div>
                        <label for="percent_charge" class="col-sm-2 col-form-label">Percentage Charge (%) *</label>
                        <div class="col-sm-4">
                            <input class="form-control" type="number" step="0.01" name="percent_charge"
                                id="percent_charge" required>
                        </div>
                    </div>
                </fieldset>

                <div class="form-group row">
                    <label for="instructions" class="col-sm-2 col-form-label">Deposit Instructions *</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" name="instructions" id="instructions" rows="4"
                            required></textarea>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']); ?>
                        <a href="<?= base_url('backend/Gateway/manual') ?>"
                            class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div><!-- end col -->
    </div>
</div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <style>
    .select2-container--default .select2-results__option--highlighted {
        background-color: #0056b3 !important;
        color: white !important;
    }
    </style>

    <script>
    $(document).ready(function() {
        $('#role').select2();
    });
    </script>
<!-- Include CKEditor library before initializing -->
<script src="https://cdn.ckeditor.com/4.20.2/standard/ckeditor.js"></script>
<script>
    // Replace the textarea with CKEditor instance
    CKEDITOR.replace('instructions', {
        // optional configuration
        height: 200
    });
</script>