<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body table-responsive">
                <table id="datatable" class="table table-bordered" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>
                                <th>Banner</th>
                                <th>Image 1</th>
                                <th>Image 2</th>
                                <th>Image 3</th>
                                <th>Image 4</th>
                                <th>Image 5</th>
                            <?php } else { ?>
                                <th>Action</th>
                                <th>Banner</th>
                                <th>Image 1</th>
                                <th>Image 2</th>
                                <th>Image 3</th>
                                <th>Image 4</th>
                                <th>Image 5</th>
                            <?php }  ?>

                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') { ?>

                                <td><img src="<?= '../' . BANNER_URL . $banner->banner ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image1 ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image2 ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image3 ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image4 ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image5 ?>" height="50" width="50"></td>
                            <?php } else { ?>
                                <td>
                                    <a href="<?= base_url('backend/banner/edit') ?>" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>
                                </td>
                                <td><img src="<?= '../' . BANNER_URL . $banner->banner ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image1 ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image2 ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image3 ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image4 ?>" height="50" width="50"></td>
                                <td><img src="<?= '../' . IMAGE_URL . $banner->image5 ?>" height="50" width="50"></td>
                            <?php }  ?>

                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- end col -->
</div>