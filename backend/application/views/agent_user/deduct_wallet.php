<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/AgentUser/update_deduct_wallet', ['autocomplete' => false, 'id' => 'edit_user'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_users')])
                ?>
                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Name *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" value="<?= $User[0]->name ?>" name="name" readonly
                            required id="name">
                        <input type="hidden" value="<?= $User[0]->id ?>" name="user_id" id="user_id">
                    </div>
                </div>

                <div class="form-group row"><label for="wallet" class="col-sm-2 col-form-label">Wallet *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="number" min="0" value="<?= $User[0]->wallet ?>" name="wallet"
                            disabled id="wallet">
                    </div>
                </div>

                <div class="form-group row"><label for="wallet" class="col-sm-2 col-form-label">Deduct Amount From
                        Wallet *</label>
                    <div class="col-sm-5">
                        <input class="form-control" type="number" min="0" max="<?= $User[0]->wallet ?>" name="amount"
                            id="amount" placeholder="Enter Amount" required>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                            echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
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