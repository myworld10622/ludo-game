<b style="color:red">Note: Tournament which are in red color have not added round, Please add rounds to live the tournaments</b>.
<br>
<br>
<div class="row">
    
    <div class="col-12">
        <div class="card">
            <div class="row">
                <div class="card-body table-responsive">
                    <table id="datatable" class="table table-bordered dt-responsive table-bordered nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Name</th>
                                <th>Registration Start Date</th>
                                <th>Registration Start Time</th>
                                <th>Start Date</th>
                                <th>Start Time</th>
                                <th>Total Pass</th>
                                <th>Registration Fee</th>
                                <th>Winning Amount</th>
                                <th>Max Player</th>
                                <th>Total Round</th>
                                <th>Is Mega Tournament</th>
                                <th>Is Winner Get Pass</th>
                                <th>Pass of Tournament Id</th>
                                <th>Total Pass Count </th>
                                <th>Is Completed</th>
                                <th>Added Date</th>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                    <th>Action</th>
                                <?php } ?>

                                </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0;
                            foreach ($AllTournamentMaster as $key => $TournamentMaster) {
                                $i++;
                            ?>
                            <?php 
                            $color = "";
                            if($TournamentMaster->round_count == 0) {
                                $color = "background-color:red";
                            }
                             ?>
                                <tr style="<?= $color?>">
                                    <td><?= $i ?></td>
                                    <td><?= $TournamentMaster->name ?></td>
                                    <td><?= $TournamentMaster->registration_start_date ?></td>
                                    <td><?= $TournamentMaster->registration_start_time ?></td>
                                    <td><?= $TournamentMaster->start_date ?></td>
                                    <td><?= $TournamentMaster->start_time ?></td>
                                    <td><?= $TournamentMaster->max_entry_pass ?></td>
                                    <td><?= $TournamentMaster->registration_fee ?></td>
                                    <td><?= $TournamentMaster->winning_amount ?></td>
                                    <td><?= $TournamentMaster->max_player ?></td>
                                    <td><?= $TournamentMaster->total_round ?></td>
                                    <td><?= $TournamentMaster->is_mega_tournament ?></td>
                                    <td><?= $TournamentMaster->is_winner_get_pass ?></td>
                                    <td><?= $TournamentMaster->pass_of_tournament_id ?></td>
                                    <td><?= $TournamentMaster->total_pass_count ?></td>
                                    <td><?= $TournamentMaster->is_completed ?></td>
                                    <td><?= date("d-m-Y h:i:s A", strtotime($TournamentMaster->added_date)) ?></td>
                                    <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                        <td>
                                            <a href="<?= base_url('backend/tournamentMaster/prize/' . $TournamentMaster->id) ?>" value="$TournamentMaster->id" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Prize"><span class="fa fa-trophy" title="Prize"></span></a>
                                            <a href="<?= base_url('backend/tournamentMaster/round/' . $TournamentMaster->id) ?>" value="$TournamentMaster->id" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Round"><span class="fa fa-trophy" title="Round"></span></a>
                                            <?php if($TournamentMaster->is_completed == 0) { ?>
                                            <a href="<?= base_url('backend/tournamentMaster/edit/' . $TournamentMaster->id) ?>" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit" title="Edit"></span></a>
                                            <a href="<?= base_url('backend/tournamentMaster/delete/' . $TournamentMaster->id) ?>" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-times" title="Delete"></span></a>
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