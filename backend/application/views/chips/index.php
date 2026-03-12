<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Units</th>
                            <th>Price</th>
                            <th>Title</th>
                            <th>Added Date and Time</th>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllChips as $key => $Chips) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $Chips->coin ?></td>
                            <td><?= $Chips->price ?></td>
                            <td><?= $Chips->title ?></td>
                            <td><?= date("d-m-Y h:i:s A", strtotime($Chips->added_date)) ?></td>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                            <td>
                                <a href="<?= base_url('backend/Chips/edit/' . $Chips->id) ?>" class="btn btn-info"
                                    data-toggle="tooltip" data-placement="top" title="Edit">
                                    <span class="fa fa-edit"></span>
                                </a> |
                                <a href="<?= base_url('backend/Chips/delete/' . $Chips->id) ?>" class="btn btn-danger"
                                    data-toggle="tooltip" data-placement="top" title="Delete">
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