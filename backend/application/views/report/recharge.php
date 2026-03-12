<section class="section">
    <div class="card">
        <div class="card-body">
            <form>
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <input type="hidden" id="sn_date" value="" name="start_date">
                            <input type="hidden" id="en_date" value="" name="end_date">
                            <label for="crm_attendence_date">Date filter</label>
                            <div id="report" class="form-control" style=" cursor: pointer; ">
                                <i class="fa fa-calendar"></i>&nbsp;
                                <span></span>
                                <i class="fa fa-caret-down"></i>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="col-1">
                        <div class="form-group">
                            <button type="submit" class="btn btn-success mt-4">Search</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

</section>
<div class="row">

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>User ID</th>
                            <th>User Name</th>
                            <th>Coin</th>                       
                            <th>Added Date and Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($Recharge as $key => $Redeem) {
                            $i++;
                        ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= $Redeem->user_id ?></td>
                                <td><?=$Redeem->user_name?></td>
                                <td><?= $Redeem->coin?></td>     
                                <td><?= date("d-m-Y h:i:s A", strtotime($Redeem->added_date)) ?></td>
                               
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
