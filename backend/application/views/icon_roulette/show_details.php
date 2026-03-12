<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
                            $bet = '';
                            switch ($Games->bet) {
                                case '1':
                                    $bet = 'Umbrella ';
                                    break;
                                case '2':
                                    $bet = 'Football ';
                                    break;
                                case '3':
                                    $bet = 'Sun ';
                                    break;
                                case '4':
                                    $bet = 'Diya ';
                                    break;
                                case '5':
                                    $bet = 'Cow ';
                                    break;
                                case '6':
                                    $bet = 'Bucket ';
                                    break;
                                case '7':
                                    $bet = 'Kite ';
                                    break;
                                case '8':
                                    $bet = 'Top ';
                                    break;
                                case '9':
                                    $bet = 'Rose ';
                                    break;
                                case '10':
                                    $bet = 'Butterfly ';
                                    break;
                                case '11':
                                    $bet = 'Pigeon ';
                                    break;
                                case '12':
                                    $bet = 'Rabbit ';
                                    break;
                            }
                        ?>

                            <tr>
                                <td><?= $Games->icon_roulette_id ?></td>
                                <td><?= $Games->user_name ?></td>
                                <td><?= $Games->amount ?><?= '(' . $bet . ')' ?></td>
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