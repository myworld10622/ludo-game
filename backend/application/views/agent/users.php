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
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Wallet</th>
                            <th>Password</th>
                            <th>Created Date</th>
                         
                        
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach($AllAgent as $agent) { ?> 
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= $agent->name ?></td>
                                <td><?= $agent->email ?></td>
                                <td><?= $agent->mobile ?></td>
                                <td><?= $agent->wallet ?></td>
                                <td><?= $agent->password ?></td>
                                <td><?= $agent->added_date ?></td>
                              
                      
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</div>