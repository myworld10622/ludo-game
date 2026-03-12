<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/notification/insert', [
                    'autocomplete' => false,
                    'id' => 'add_Noti'
                    ,
                    'method' => 'post'
                ], ['type' => $this->url_encrypt->encode('tbl_notification')])
                    ?>
                <div class="form-group row"><label for="msg" class="col-sm-2 col-form-label">Msg *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="msg" required id="msg">
                    </div>
                </div>
                <div class="form-group row"><label for="image" class="col-sm-2 col-form-label">Image *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="image" required id="image">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>
                </div>
                <div class="form-group row"><label for="url" class="col-sm-2 col-form-label">Url *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="url" required id="url">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Send', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/Notification') ?>"
                            class="btn btn-secondary waves-effect">Cancel</a>

                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div><!-- end col -->
    </div>