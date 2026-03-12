<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<style>
    a{
        color:unset;
        
    }
    a:hover{
        text-decoration: unset;
    }
</style>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body table-responsive">
                <!-- <ul class="nav nav-tabs">
                    <li><a data-toggle="tab" href="#wallet_log">Wallet Log</a></li>
                </ul> -->
                <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#wallet_log">Wallet Log</a></li>
                </ul>
                <div class="tab-content">
                    <br>  
                
                    <div id="wallet_log">
                        <table class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Amount</th>
                                    <!-- <th>Bonus</th> -->
                                    <th>Added Date and Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 0;
                                foreach ($AllWalletLog as $key => $WalletLog) {
                                    $i++;
                                ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= $WalletLog->coin ?></td>
                                        <!-- <td><?= ($WalletLog->bonus) ? 'Yes' : 'No'; ?></td> -->
                                        <td><?= date("d-m-Y H:i:s A", strtotime($WalletLog->added_date)) ?></td>
                                    </tr>
                                <?php }
                                ?>


                            </tbody>
                        </table>
                    </div>                                                              
                        </div>
                    
                </div>
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
                'excel','pdf'
            ]
        });
    })
</script>