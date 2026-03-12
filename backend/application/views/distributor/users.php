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
                            <!-- <th>Employee ID</th> -->
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Wallet</th>
                            <th>Password</th>
                            <th>Added Date and Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach($AllAgent as $agent) { ?> 
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= $agent->first_name ?></td>
                                <td><?= $agent->last_name ?></td>
                                <!-- <td><?= $agent->id ?></td> -->
                                <td><?= $agent->email_id ?></td>
                                <td><?= $agent->mobile ?></td>
                                <td><?= $agent->wallet ?></td>
                                <td><?= $agent->password ?></td>
                                <td><?= $agent->created_date ?></td>

                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</div>
