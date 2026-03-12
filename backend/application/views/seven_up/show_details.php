<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered dt-responsive nowrap"
                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Game Id</th>
                            <th>User Name</th>
                            <th>User ID</th>
                            <th>Amount</th>
                            <th>Winnig Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach ($AllUsers as $key => $Games) {
                                $bet='';
                                switch ($Games->bet) {
                                    case '0':
                                        $bet='Up';
                                        break;
                                    case '1':
                                        $bet='Down';
                                        break;
                                    case '2':
                                        $bet='Tie';
                                        break;
                                    }
                        ?>
                        <tr>
                            <td><?= $Games->seven_up_id ?></td>
                            <td><?= $Games->user_name ?></td>
                            <td><?= $Games->user_id ?></td>
                            <td><?= $Games->amount ?><?= "(".$bet.")" ?></td>
                            <td><?= $Games->winning_amount ?></td>
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