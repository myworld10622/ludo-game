<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/user/update', ['autocomplete' => false, 'id' => 'edit_user'
                    ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_users')])
                ?>
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $User[0]->name ?>" name="name" readonly
                            required id="name">
                        <input type="hidden" value="<?= $User[0]->id ?>" name="user_id" id="user_id">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="wallet" class="col-sm-2 col-form-label">Wallet *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0"
                            value="<?= ($User[0]->wallet > 100000000) ? 100000000 : $User[0]->wallet ?>" name="wallet"
                            readonly id="wallet">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="amount" class="col-sm-2 col-form-label">Add Amount To Wallet *</label>
                    <div class="col-sm-5">
                        <input class="form-control" type="number" min="0" max="100000000" name="amount" id="amount"
                            placeholder="Enter Amount" required>
                        <span id="walletError" class="text-danger"></span>
                    </div>

                    <div class="col-sm-5">
                        Bonus:- &nbsp;
                        <input type="radio" name="bonus" value="1" id="yes" /> <label for="yes">Yes</label>&nbsp;
                        <input type="radio" name="bonus" value="0" id="no" checked /> <label for="no">No</label>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1', 'id' => 'submitBtn']);
                        echo form_reset(['class' => 'btn btn-secondary waves-effect', 'value' => 'Cancel']);
                        ?>
                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div><!-- end col -->
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let walletInput = document.getElementById("wallet");
    let amountInput = document.getElementById("amount");
    let errorSpan = document.getElementById("walletError");
    let submitBtn = document.getElementById("submitBtn");

    function validateWallet() {
        let walletValue = parseInt(walletInput.value) || 0;
        let addAmount = parseInt(amountInput.value) || 0;
        let total = walletValue + addAmount;

        if (total > 100000000) {
            errorSpan.innerText = "Total wallet amount cannot exceed 10 Crore.";
            submitBtn.disabled = true; // Form submit disable
        } else {
            errorSpan.innerText = "";
            submitBtn.disabled = false;
        }
    }

    amountInput.addEventListener("input", validateWallet);
});
</script>