<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body table-responsive">
                <table id="datatable" class="table table-bordered"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo' && $_ENV['ENVIRONMENT'] != 'fame') { ?>
                                <th>Sr. No.</th>
                                <th>Name</th>
                                <th>Image</th>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 0;
                        foreach ($AllPaymentMethod as $paymentMethod) {
                            $i++;
                        ?>
                            <tr>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo' && $_ENV['ENVIRONMENT'] != 'fame') { ?>
                                    <td><?= $i ?></td>
                                    <td><?= $paymentMethod->name ?></td>
                                    <td><img src="<?= base_url(BANNER_URL . $paymentMethod->image) ?>" height="50" width="50"></td>
                                    <td>
                                        <a href="<?= base_url('backend/Agent/delete_payment_method/' . $paymentMethod->id . '/' . $paymentMethod->user_id) ?>" onclick="return confirm('Are You Sure Want To Remove This Image?')" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title=""
                                            data-original-title="Delete"><span class="fa fa-trash"></span></a>
                                    </td>
                                 
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>