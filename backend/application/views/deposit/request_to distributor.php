<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/Deposit/storeToDistributor', [
                    'autocomplete' => false,
                    'id' => 'add_gateway',
                    'method' => 'post'
                ]);
                ?>

                <div class="form-group row">
                    <label for="gateway_id" class="col-sm-2 col-form-label">Gateway Name *</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="gateway_id" id="gateway_id" required onchange="getGatewayDetails(this.value)">
                            <option value="">-- Select Gateway --</option>
                            <?php foreach ($ManualGateway as $gateway): ?>
                                <option value="<?= $gateway->id ?>" data-number="<?= $gateway->number ?>"> <?= $gateway->name ?> </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group row" id="gateway_number_box" style="display:none;">
                    <label class="col-sm-2 col-form-label">Gateway Number</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="gateway_number" readonly>
                    </div>
                </div>


                <div class="form-group row">
                    <label for="amount" class="col-sm-2 col-form-label">Amount *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" step="1" name="amount" id="amount" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="sender_number" class="col-sm-2 col-form-label">Sender Number *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" step="1" name="sender_number" id="sender_number" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="txn_id" class="col-sm-2 col-form-label">Transaction Id *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" step="1" name="txn_id" id="txn_id" required>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']); ?>
                        <a href="<?= base_url('backend/Deposit/addToDistributor') ?>"
                            class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div><!-- end col -->
    </div>
</div>
<script>
    document.getElementById('gateway_id').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        var number = selectedOption.getAttribute('data-number');
        var box = document.getElementById('gateway_number_box');
        var input = document.getElementById('gateway_number');

        if (number) {
            input.value = number;
            box.style.display = 'flex'; // for Bootstrap row display
        } else {
            box.style.display = 'none';
            input.value = '';
        }
    });
</script>
