<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart(
                    'backend/DepositPercentage/update/' . $deposit->id,
                    [
                        'autocomplete' => false,
                        'id' => 'edit_percentage',
                        'method' => 'post'
                    ],
                    [
                        'id' => $deposit->id,
                    ]
                );
                ?>

                <div class="form-group row">
                    <label for="user_type" class="col-sm-2 col-form-label">User Type *</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="user_type" id="user_type" required>
                            <option value="">-- Select User Type --</option>
                            <option value="0" <?= ($deposit->user_type == '0') ? 'selected' : '' ?>>Admin</option>
                            <option value="2" <?= ($deposit->user_type == '2') ? 'selected' : '' ?>>Agent</option>
                            <option value="3" <?= ($deposit->user_type == '3') ? 'selected' : '' ?>>Distributer</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="percentage" class="col-sm-2 col-form-label">Percentage *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="percentage" required id="percentage"
                            value="<?= htmlspecialchars($deposit->percentage) ?>">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Update', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/DepositPercentage') ?>"
                            class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>