<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/Agent/setting_update', [
                    'autocomplete' => false, 'id' => 'edit_setting', 'method' => 'post'
                ])
                ?>
                <div class="form-group row"><label for="agent_deposite_rate" class="col-sm-2 col-form-label">Deposite Rate Per 100 Coins  *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" name="agent_deposite_rate" value="<?= $setting->agent_deposite_rate ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="agent_withdraw_rate" class="col-sm-2 col-form-label">Withdraw Rate Per 100 Coins
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" name="agent_withdraw_rate" value="<?= $setting->agent_withdraw_rate ?>" required>
                    </div>
                </div>

                <div class="form-group row"><label for="agent_acc_details" class="col-sm-2 col-form-label">Account Details
                        *</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" name="agent_acc_details" required><?= $setting->agent_acc_details ?></textarea>
                    </div>
                </div>

                <div>
                    <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>

                    <?php } else { ?>
                        <?php
                        echo form_submit('submit', 'Update', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        echo form_reset(['class' => 'btn btn-secondary waves-effect', 'value' => 'Cancel']);
                        ?>
                    <?php  } ?>

                </div>
            </div>
            <?php
            echo form_close();
            ?>
        </div>
    </div><!-- end col -->
</div>

<script>
    // user for ckeditor.
    CKEDITOR.replace('contact_us');
    CKEDITOR.replace('about_us');
    CKEDITOR.replace('refund_policy');
    CKEDITOR.replace('terms');
    CKEDITOR.replace('privacy_policy');
    CKEDITOR.replace('help_support');
</script>

<script>
    $(document).on('click', '.transfer', function(e) {
        e.preventDefault();
        jQuery.ajax({
            type: 'POST',
            url: '<?= base_url('backend/Setting/Transfer') ?>',
            data: {},
            beforeSend: function() {},
            success: function(response) {
                if (response) {
                    $('#aviator_bucket').val(0)
                }
            },
            error: function(e) {}
        })
    });
</script>