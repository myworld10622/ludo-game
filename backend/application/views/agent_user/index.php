<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="row">
                <div class="card-body table-responsive">

                    <table class="table table-bordered nowrap"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Name</th>
                                <!-- <th>Email</th> -->
                                <th>Mobile</th>
                                <th>Wallet</th>
                                <th>Password</th>
                                <th>Created Date</th>

                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($AllAgent as $agent) { ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= $agent->name ?></td>
                                    <!-- <td><?= $agent->email ?></td> -->
                                    <td><?= $agent->mobile ?></td>
                                    <td><?= $agent->wallet ?></td>
                                    <td><?= $agent->password ?></td>
                                    <td><?= $agent->added_date ?></td>

                                    <td>
                                        <a href="<?= base_url('backend/user/view/' . $agent->id) ?>" class="btn btn-info"
                                            data-toggle="tooltip" data-placement="top" title="View Wins">
                                            <span class="fa fa-eye"></span></a>
                                        | <a href="<?= base_url('backend/user/LadgerReports/' . $agent->id) ?>"
                                            class="btn btn-info" data-toggle="tooltip" data-placement="top"
                                            title="View Ladger Report"><span class="ti-wallet"></span></a>
                                        | <a href="<?= base_url('backend/AgentUser/edit_wallet/' . $agent->id) ?>"
                                            class="btn btn-info" data-toggle="tooltip" data-placement="top"
                                            title="Add Wallet"><span class="fa fa-credit-card"></span></a>

                                        | <a href="<?= base_url('backend/AgentUser/deduct_wallet/' . $agent->id) ?>"
                                            class="btn btn-danger" data-toggle="tooltip" data-placement="top"
                                            title="Deduct Wallet"><span class="fa fa-credit-card"></span></a>

                                        | <a href="<?= base_url('backend/AgentUser/edit_user/' . $agent->id) ?>"
                                            class="btn btn-info" data-toggle="tooltip" data-placement="top"
                                            title="Edit"><span class="fa fa-edit"></span></a>


                                        <!-- | <a href="<?= base_url('backend/AgentUser/delete/' . $agent->id) ?>" class="btn btn-danger"
                            data-toggle="tooltip" data-placement="top" title="Delete" onclick="return confirm('Are You Sure Want To Delete <?= $agent->name ?>)"><span
                                class="fa fa-trash" ></span></a> -->
                                    </td>

                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>