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
                                <th>Image</th>
                                <th>Added Date</th>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                <th>Action</th>
                                <?php } ?>

                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0;
                            foreach ($AllTournamentTypes as $key => $TournamentType) {
                                $i++;
                                ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= $TournamentType->name ?></td>
                                <td><?= $TournamentType->image ?></td>
                                <td><?= date("d-m-Y h:i:s A", strtotime($TournamentType->added_date)) ?></td>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                <td>
                                    <a href="<?= base_url('backend/tournamentTypes/edit/' . $TournamentType->id) ?>"
                                        class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit">
                                        <span class="fa fa-edit" title="Edit"></span>
                                    </a>
                                    <a href="<?= base_url('backend/tournamentTypes/delete/' . $TournamentType->id) ?>"
                                        class="btn btn-danger" data-toggle="tooltip" data-placement="top"
                                        title="Delete">
                                        <span class="fa fa-trash" title="Delete"></span> <!-- Updated to trash icon -->
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
    </div>
    <!-- end col -->
</div>