<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Boot Value</th>
                            <th>Pool value</th>
                            <th>Added Date and Time</th>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllPoolTableMaster as $key => $PoolTableMaster) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $PoolTableMaster->boot_value ?></td>
                            <td><?= $PoolTableMaster->pool_point ?></td>
                            <td><?= date("d-m-Y h:i:s A", strtotime($PoolTableMaster->added_date)) ?></td>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <td>
                                <a href="<?= base_url('backend/PoolTableMaster/edit/' . $PoolTableMaster->id) ?>"
                                    class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit">
                                    <span class="fa fa-edit"></span>
                                </a>
                                |
                                <a href="<?= base_url('backend/PoolTableMaster/delete/' . $PoolTableMaster->id) ?>"
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