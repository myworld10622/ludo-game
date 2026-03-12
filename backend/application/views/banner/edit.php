<script src="https://cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/banner/update', [
                    'autocomplete' => false,
                    'id' => 'edit_banner',
                    'method' => 'post'
                ]) ?>

                <div class="form-group row"><label for="banner" class="col-sm-2 col-form-label">Banner *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="banner" value="<?= $banner->banner ?>">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>
                </div>
                <div class="form-group row"><label for="image1" class="col-sm-2 col-form-label">Image 1 *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="image1" value="<?= $banner->image1 ?>">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>
                </div>
                <div class="form-group row"><label for="image2" class="col-sm-2 col-form-label">Image 2 *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="image2" value="<?= $banner->image2 ?>">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>
                </div>
                <div class="form-group row"><label for="image3" class="col-sm-2 col-form-label">Image 3 *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="image3" value="<?= $banner->image3 ?>">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>
                </div>
                <div class="form-group row"><label for="image4" class="col-sm-2 col-form-label">Image 4 *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="image4" value="<?= $banner->image4 ?>">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>
                </div>
                <div class="form-group row"><label for="image5" class="col-sm-2 col-form-label">Image 5 *</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" name="image5" value="<?= $banner->image5 ?>">
                        <small class="form-text">Recommended size: 2 MB</small>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/Banner') ?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div><!-- end col -->
    </div>