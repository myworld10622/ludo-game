<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Self Bonus</th>
                            <th>Upline Bonus</th>
                            <th>No Of Deposit Count</th>
                            <th>Added Date and Time</th>
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
                            <td><?= $value->min ?></td>
                            <td><?= $value->max ?></td>
                            <td><?= $value->self_bonus ?></td>
                            <td><?= $value->upline_bonus ?></td>
                            <td><?= $value->deposit_count ?></td>
                            <td><?= date("d-m-Y h:i:s A", strtotime($value->added_date)) ?></td>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>

                            <td>
                                <a href="<?= base_url('backend/DepositBonus/edit/' . $value->id) ?>"
                                    class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit"><span
                                        class="fa fa-edit"></span></a>
                                | <a href="<?= base_url('backend/DepositBonus/delete/' . $value->id) ?>"
                                    class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete">
                                    <span class="fa fa-trash"></span>
                                </a>
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