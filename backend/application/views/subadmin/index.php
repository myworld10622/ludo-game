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
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Employee ID</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <!-- <th>Wallet</th> -->
                            <!-- <th>Status</th> -->
                            <th>Added Date and Time</th>
                            <?php if ($_ENV['ENVIRONMENT']!= 'demo') { ?>
                            <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach($AllSubAdmin as $subdomain) { ?> 
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= $subdomain->first_name ?></td>
                                <td><?= $subdomain->last_name ?></td>
                                <td><?= $subdomain->id ?></td>
                                <td><?= $subdomain->email_id ?></td>
                                <td><?= $subdomain->mobile ?></td>
                                <td><?= $subdomain->created_date ?></td>
                               <?php if ($_ENV['ENVIRONMENT']!= 'demo') { ?>
                                <td>

                                     <a href="<?= base_url('backend/SubAdmin/edit_subadmin/' . $subdomain->id) ?>" class="btn btn-info"
                                        data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>

                                    | <a href="<?= base_url('backend/SubAdmin/delete/' . $subdomain->id) ?>" class="btn btn-danger"
                                        data-toggle="tooltip" data-placement="top" title="Delete" onclick="return confirm('Are You Sure Want To Delete <?= $subdomain->first_name ?>?')"><span class="fa fa-trash"></span></a>
                                </td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
    <!-- end col -->
</div>

