<?php
echo form_open_multipart('backend/Gateway/updateManual/' . $ManualGatway->id, [
    'autocomplete' => false,
    'id' => 'edit_gateway',
    'method' => 'post'
]);
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <!-- Gateway Name -->
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">Gateway Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="name" id="name"
                            value="<?= set_value('name', $ManualGatway->name) ?>" required>
                    </div>
                </div>

               <!-- Role -->
                <div class="form-group row">
                    <label for="role" class="col-sm-2 col-form-label">Role *</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="role[]" id="role" multiple required>
                            <option value="">Select Role</option>
                            <?php
                            $roles = [
                                // 0 => 'Super Admin',
                                1 => 'Admin',
                                2 => 'Agent',
                                3 => 'Distributer'
                            ];
                            foreach ($roles as $key => $value): ?>
                                <option value="<?= $key ?>" <?= in_array($key, $ManualGatway->role) ? 'selected' : '' ?>>
                                    <?= $value ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Currency -->
                <div class="form-group row">
                    <label for="currency" class="col-sm-2 col-form-label">Currency *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="currency" id="currency"
                            value="<?= set_value('currency', $ManualGatway->currency) ?>" required>
                    </div>
                </div>

                <!-- Rate -->
                <div class="form-group row">
                    <label for="rate" class="col-sm-2 col-form-label">Rate *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" step="0.01" name="rate" id="rate"
                            value="<?= set_value('rate', $ManualGatway->rate) ?>" required>
                    </div>
                </div>

                <!-- Range -->
                <fieldset class="border p-3 mb-3">
                    <legend class="w-auto">Range</legend>
                    <div class="form-group row">
                        <label for="min_amount" class="col-sm-2 col-form-label">Minimum Amount *</label>
                        <div class="col-sm-4">
                            <input class="form-control" type="number" step="0.01" name="min_amount" id="min_amount"
                                value="<?= set_value('min_amount', $ManualGatway->min_amount) ?>" required>
                        </div>
                        <label for="max_amount" class="col-sm-2 col-form-label">Maximum Amount *</label>
                        <div class="col-sm-4">
                            <input class="form-control" type="number" step="0.01" name="max_amount" id="max_amount"
                                value="<?= set_value('max_amount', $ManualGatway->max_amount) ?>" required>
                        </div>
                    </div>
                </fieldset>

                <!-- Charge -->
                <fieldset class="border p-3 mb-3">
                    <legend class="w-auto">Charge</legend>
                    <div class="form-group row">
                        <label for="fixed_charge" class="col-sm-2 col-form-label">Fixed Charge *</label>
                        <div class="col-sm-4">
                            <input class="form-control" type="number" step="0.01" name="fixed_charge" id="fixed_charge"
                                value="<?= set_value('fixed_charge', $ManualGatway->fixed_charge) ?>" required>
                        </div>
                        <label for="percent_charge" class="col-sm-2 col-form-label">Percentage Charge (%) *</label>
                        <div class="col-sm-4">
                            <input class="form-control" type="number" step="0.01" name="percent_charge"
                                id="percent_charge"
                                value="<?= set_value('percent_charge', $ManualGatway->percent_charge) ?>" required>
                        </div>
                    </div>
                </fieldset>

                <!-- Deposit Instructions -->
                <div class="form-group row">
                    <label for="instructions" class="col-sm-2 col-form-label">Deposit Instructions *</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" name="instructions" id="instructions"
                            rows="4"><?= set_value('instructions', $ManualGatway->instructions) ?></textarea>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="form-group mb-0">
                    <div>
                        <?= form_submit('submit', 'Update', ['class' => 'btn btn-primary waves-effect waves-light mr-1']); ?>
                        <a href="<?= base_url('backend/Gateway/manual') ?>"
                            class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>

            </div>
        </div>
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
<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/4.20.2/standard/ckeditor.js"></script>
<script>
    CKEDITOR.replace('instructions', { height: 200 });
</script>