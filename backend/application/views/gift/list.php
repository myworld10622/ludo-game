<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Name</th>
                            <th>Image</th>
                            <th>Coin</th>
                            <th>Added Date and Time</th>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($AllGift as $key => $Gift) {
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= $Gift->name ?></td>
                                <td><img src="<?= base_url('data/post/' . $Gift->image) ?>" width="400px" height="400px"></td>
                                <td><?= $Gift->coin ?></td>
                                <td><?= date("d-m-Y h:i:s A", strtotime($Gift->added_date)) ?></td>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>

                                    <td>
                                        <a href="<?= base_url('backend/Gift/edit/' . $Gift->id) ?>" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>
                                        | <a href="<?= base_url('backend/Gift/delete/' . $Gift->id) ?>" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-times"></span></a>
                                    </td>
                                <?php } ?>

                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- end col -->
</div>