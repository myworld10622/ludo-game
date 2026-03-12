<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/Gateway/storeDistributorManualWithdraw', [
                    'autocomplete' => false,
                    'id' => 'add_gateway_withdrwa',
                    'method' => 'post'
                ]);
                ?>

                <div class="form-group row">
                    <label for="gateway_id" class="col-sm-2 col-form-label">Gateway Name *</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="gateway_id" id="gateway_id" required>
                            <option value="">-- Select Gateway --</option>
                            <?php foreach ($ManualGateway as $gateway): ?>
                                <option value="<?= $gateway->id ?>">
                                    <?= $gateway->name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="number" class="col-sm-2 col-form-label">Number *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" step="0.01" name="number" id="number" required>
                    </div>
                </div>


                <div class="form-group mb-0">
                    <div>
                        <?php echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']); ?>
                        <a href="<?= base_url('backend/Gateway/distributorGatewayWithdraw') ?>"
                            class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div><!-- end col -->
    </div>
</div>