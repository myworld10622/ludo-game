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
                                    $bet='TOYOTA';
                                    break;
                                case '2':
                                    $bet='MAHINDRA';
                                    break;
                                case '3':
                                    $bet='AUDI';
                                    break;
                                case '4':
                                    $bet='BMW';
                                    break;
                                case '5':
                                    $bet='MERCEDES';
                                    break;
                                case '6':
                                    $bet='PORSCHE';
                                    break;
                                case '7':
                                    $bet='LAMBORGHINI';
                                    break;  
                                case '8':
                                    $bet='FERRARI';
                                    break;
                            }
                        ?>
                        <tr>
                            <td><?= $Games->car_roulette_id ?></td>
                            <td><?= $Games->user_name ?></td>
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