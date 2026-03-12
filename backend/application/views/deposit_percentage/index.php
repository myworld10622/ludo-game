<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>User Type</th>
                            <th>Deposit Percentage</th>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <th>Action</th>
                            <?php } ?>

                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($All as $key => $value) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $value->user_type == 0 ? 'Admin' : ($value->user_type == 2 ? 'Agent' : ($value->user_type == 3 ? 'Distributor' : 'Unknown')) ?></td>
                            <td><?= $value->percentage ?></td>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <td>
                                <a href="<?= base_url('backend/DepositPercentage/edit/' . $value->id) ?>"
                                    class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit">
                                    <span class="fa fa-edit"></span></a>
                            </td>
                            <?php } ?>

                        </tr>
                        <?php }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- end col -->
</div>