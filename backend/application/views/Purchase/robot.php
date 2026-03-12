<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>User Name</th>
                            <th>User ID</th>
                            <th>Bonus</th>
                            <th>Coins</th>
                            <!-- <th>Payment Status</th> -->
                            <th>Added Date and Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllPurchase as $key => $Purchase) {
                            $i++;
                            ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $Purchase->name ?></td>
                            <td><?= $Purchase->user_id ?></td>
                            <td><?= $Purchase->bonus == 0 ? 'No' : 'Yes' ?></td>
                            <td><?= $Purchase->coin ?></td>
                            <!-- <td><?= ($Purchase->payment == 0) ? 'Pending' : 'Done' ?></td> -->
                            <td><?= date("d-m-Y h:i:s A", strtotime($Purchase->added_date)) ?></td>
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
<script>
$(document).ready(function() {
    $('.table').dataTable({
        dom: 'Bfrtip',
        "buttons": [
            'excel'
        ]
    });
})
</script>