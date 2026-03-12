<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/ImageNotification/insert', [
                    'autocomplete' => false,
                    'id' => 'add_banner',
                    'method' => 'post'
                ])
                    ?>

                <div class="form-group row"><label for="image" class="col-sm-2 col-form-label">POP Image *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="image" id="image" required>
                        <small class="form-text">Recommended size: 2 MB</small>
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