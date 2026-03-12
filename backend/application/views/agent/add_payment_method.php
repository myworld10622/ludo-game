
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/Agent/insert_payment_method', [
                    'autocomplete' => false, 'id' => 'add_payment_methods', 'method' => 'post'
                ])
                ?>
                <input type="hidden" name="user_id" value="<?= $user_id ?>">


                <div class="form-group">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="name" class="col-sm-2 col-form-label">Name *</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="input" name="name" id="name" placeholder="Enter Details" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="image" class="col-sm-2 col-form-label">Scanner *</label>
                            <div class="col-sm-10">
                                <input class="form-control" type="file" name="image" id="image" required>
                            </div>
                        </div>
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
