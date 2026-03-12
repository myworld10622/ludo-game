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
                                <th>Round</th>
                                <th>Winner User Count</th>
                                <th>Table Players Info</th>
                                <th>Deal Info</th>
                                <th>Added date</th>
                                <th>Start date</th>
                                <th>Start time</th>

                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                    <th>Action</th>
                                <?php } ?>

                                </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0;
                            foreach ($AllRoundMaster as $key => $RoundMaster) {
                                $i++;
                            ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $RoundMaster->tournament_id ?></td>
                                    <td><?= $RoundMaster->round ?></td>
                                    <td><?= $RoundMaster->winner_user_count ?></td>
                                    <td><?= $RoundMaster->table_players_info ?></td>
                                    <td><?= $RoundMaster->deal_info ?></td>
                                    <td><?= date("d-m-Y h:i:s A", strtotime($RoundMaster->added_date)) ?></td>
                                    <td><?= $RoundMaster->start_date ?></td>
                                    <td><?= $RoundMaster->start_time ?></td>
                                    <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                        <td>
                                            <?php if($RoundMaster->is_completed == 0) { ?>
                                            <a href="<?= base_url('backend/tournamentMaster/editRound/' . $RoundMaster->id) ?>" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>
                                            <a href="<?= base_url('backend/tournamentMaster/deleteRound/' . $RoundMaster->id) ?>" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-times"></span></a>
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