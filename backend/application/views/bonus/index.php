<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. no.</th>
                            <th>User Id</th>
                            <th>Username</th>
                            <th>Coins</th>
                            <th>Date and Time</th>
                            
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bonus as $key => $value) { ?>
                        <tr>
                            <td><?= $value->id ?></td>
                            <td><?= $value->user_id ?></td>
                            <td><?= $value->name ?></td>
                            <td><?= $value->coin ?></td>
                            <td><?= $value->added_date ?></td>
                            
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- end col -->
</div>