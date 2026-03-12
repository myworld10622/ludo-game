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
                            <th>Amount</th>
                            <th>Winnig Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach ($AllUsers as $key => $Games) {
                                $bet='';
                                switch ($Games->bet) {
                                    case '1':
                                        $bet='High Card';
                                        break;
                                    case '2':
                                        $bet='Pair';
                                        break;
                                    case '3':
                                        $bet='Color';
                                        break;
                                    case '4':
                                        $bet='Sequence';
                                        break;
                                    case '5':
                                        $bet='Pure Sequence';
                                        break;
                                    case '6':
                                        $bet='Set';
                                        break;
                                }
                        ?>
                        <tr>
                            <td><?= $Games->jackpot_id ?></td>
                            <td><?= $Games->user_name ?></td>
                            <td><?= $Games->amount ?><?= '('.$bet.')' ?></td>
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