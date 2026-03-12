<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <table id="datatable" class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Flag</th>
                            <th>Added Date and Time</th>
                            <th>Action</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllTableMaster as $key => $TableMaster) {
                            $i++;
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $TableMaster->name ?></td>
                            <td><?= $TableMaster->code ?></td>
                            <td>
                                <?php if (!empty($TableMaster->image)): ?>
                                <img src="<?= base_url('uploads/images/' . $TableMaster->image) ?>" alt="Flag"
                                    width="100">
                                <?php else: ?>
                                No image available
                                <?php endif; ?>
                            </td>

                            <td><?= date("d-m-Y h:i:s A", strtotime($TableMaster->added_date)) ?></td>
                            <td>
                                <a href="<?= base_url('backend/Country/edit/' . $TableMaster->id) ?>"
                                    class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit"><span
                                        class="fa fa-edit"></span></a>
                                | <a href="<?= base_url('backend/Country/delete/' . $TableMaster->id) ?>"
                                    class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete">
                                    <span class="fa fa-trash"></span>
                                </a>
                            </td>
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