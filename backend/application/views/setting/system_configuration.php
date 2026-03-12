<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/setting/update', [
                    'autocomplete' => false,
                    'id' => 'edit_setting',
                    'method' => 'post'
                ], [
                    'form_type' => 'System',
                ]) ?>

                <div class="form-group row"><label for="app_version" class="col-sm-2 col-form-label">App Version
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="app_version" value="<?= $Setting->app_version ?>"
                            required>
                    </div>
                </div>

                <div class="form-group row"><label for="update_url" class="col-sm-2 col-form-label">App Update Url
                        *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="update_url" value="<?= $Setting->update_url ?>"
                            required>
                    </div>
                </div>

                <div class="form-group row"><label for="app_url"
                        class="col-sm-2 col-form-label">App*(<?= ini_get('upload_max_filesize') ?>)</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="app_url">
                    </div>
                </div>

                <div class="form-group row"><label for="logo" class="col-sm-2 col-form-label">Logo*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="logo" accept="image/*">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>
                </div>

                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                    <div class="form-group row"><label for="app_message" class="col-sm-2 col-form-label">Copyright Text
                            *</label>
                        <div class="col-sm-4">
                            <input class="form-control" type="text" name="project_name" id="project_name"
                                value="<?= !empty($Setting->copyright_project_name) ? $Setting->copyright_project_name : PROJECT_NAME; ?>"
                                required>
                        </div>
                        Crafted With
                        <div class="col-sm-4">
                            <input class="form-control" type="text" placeholder="Company Name" name="company_name"
                                id="company_name"
                                value="<?= !empty($Setting->copyright_company_name) ? $Setting->copyright_company_name : COMPANY_NAME ?>"
                                required>
                        </div>
                    </div>
                <?php } ?>

                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Contact Us *</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" type="text" name="contact_us" required
                            id="contact_us"><?= $Setting->contact_us ?></textarea>
                    </div>
                </div>

                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">About Us *</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" type="text" name="about_us" required
                            id="about_us"><?= $Setting->about_us ?></textarea>
                    </div>
                </div>

                <div class="form-group row"><label for="refund_policy" class="col-sm-2 col-form-label">Refund Policy
                        *</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" type="text" name="refund_policy" required
                            id="refund_policy"><?= $Setting->refund_policy ?></textarea>
                    </div>
                </div>

                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Terms & Conditions
                        *</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" type="text" name="terms" required
                            id="terms"><?= $Setting->terms ?></textarea>
                    </div>
                </div>

                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Privacy Policy *</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" type="text" name="privacy_policy" required
                            id="privacy_policy"><?= $Setting->privacy_policy ?></textarea>
                    </div>
                </div>

                <div class="form-group row"><label for="name" class="col-sm-2 col-form-label">Help & Support *</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" type="text" name="help_support" required
                            id="help_support"><?= $Setting->help_support ?></textarea>
                    </div>
                </div>

                <div>
                    <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>

                    <?php } else { ?>
                        <?php
                        echo form_submit('submit', 'Update', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        echo form_reset(['class' => 'btn btn-secondary waves-effect', 'value' => 'Cancel']);
                        ?>
                    <?php } ?>

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