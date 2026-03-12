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
                                    case '10':
                                        $bet='Green';
                                        break;
                                    case '11':
                                        $bet='Violet';
                                        break;
                                    case '12':
                                        $bet='Red';
                                        break;
                                    case '15':
                                        $bet='Big';
                                        break;
                                    case '16':
                                        $bet='Small';
                                        break;
                                    default:
                                        $bet=$Games->bet;
                                        break;
                                }
                        ?>
                        <tr>
                            <td><?= $Games->color_prediction_id ?></td>
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