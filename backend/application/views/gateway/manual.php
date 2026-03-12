<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="row">
                <div class="card-body table-responsive">
                    <table id="datatable" class="table table-bordered dt-responsive table-bordered nowrap"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Added Date</th>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                    <th>Action</th>
                                <?php } ?>

                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0;
                            foreach ($ManualGatway as $key => $manualgatway) {
                                $i++;
                                ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $manualgatway->name ?></td>
                                    <td><?= $manualgatway->status == 1 ? 'Enable' : 'Disable' ?></td>
                                    <td><?= date("d-m-Y h:i:s A", strtotime($manualgatway->created_date)) ?></td>
                                    <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                        <td>
                                            <a href="<?= base_url('backend/Gateway/editManual/' . $manualgatway->id) ?>"
                                                class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit">
                                                <span class="fa fa-edit" title="Edit"></span>
                                            </a>

                                            <!-- Toggle status button -->
                                            <?php if ($manualgatway->status == 0): ?>
                                                <a href="<?= base_url('backend/Gateway/toggleManualStatus/' . $manualgatway->id) ?>"
                                                    class="btn btn-success"
                                                    onclick="return confirm('Are you sure you want to enable this gateway?');"
                                                    data-toggle="tooltip" title="Enable">
                                                    Enable
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= base_url('backend/Gateway/toggleManualStatus/' . $manualgatway->id) ?>"
                                                    class="btn btn-warning"
                                                    onclick="return confirm('Are you sure you want to disable this gateway?');"
                                                    data-toggle="tooltip" title="Disable">
                                                    Disable
                                                </a>
                                            <?php endif; ?>
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
    </div>
    <!-- end col -->
</div>