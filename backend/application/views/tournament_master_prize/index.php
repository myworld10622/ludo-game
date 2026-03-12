<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="row">
                <div class="card-body table-responsive">
                    <table id="datatable" class="table table-bordered dt-responsive table-bordered nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Tournament Id</th>
                                <th>From Position</th>
                                <th>To Position</th>
                                <th>Players</th>
                                <th>Winning Price</th>
                                <th>Given in Round</th>
                                <th>Added Date</th>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                    <th>Action</th>
                                <?php } ?>

                                </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0;
                            foreach ($AllPrizeMaster as $key => $PrizeMaster) {
                                $i++;
                            ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $PrizeMaster->tournament_id ?></td>
                                    <td><?= $PrizeMaster->from_position ?></td>
                                    <td><?= $PrizeMaster->to_position ?></td>
                                    <td><?= $PrizeMaster->players ?></td>
                                    <td><?= $PrizeMaster->winning_price ?></td>
                                    <td><?= $PrizeMaster->given_in_round ?></td>
                                    <td><?= date("d-m-Y h:i:s A", strtotime($PrizeMaster->added_date)) ?></td>
                                    <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                        <td>
                                        <?php if($PrizeMaster->is_completed == 0) { ?>
                                            <a href="<?= base_url('backend/tournamentMaster/editPrize/' . $PrizeMaster->id) ?>" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit" title="Edit"></span></a>
                                            <a href="<?= base_url('backend/tournamentMaster/deletePrize/' . $PrizeMaster->id) ?>" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-times" title="Delete"></span></a>
                                            <?php } ?>
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