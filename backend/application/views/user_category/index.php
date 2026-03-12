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
                    <div class="col-1">
                        <div class="form-group">
                            <a href="<?= base_url('crm/Attendence') ?>" class="btn btn-info mt-4">Clear</a>
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
                            <th>Name</th>
                            <th>Amount</th>
                            <th>Percentage(%)</th>
                            <th>Added Date and Time</th>
                            <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($AllUserCategories as $key => $AllUserCategory) {
                            $i++;
                        ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= $AllUserCategory->name ?></td>
                                <td><?= $AllUserCategory->amount ?></td>
                                <td><?= $AllUserCategory->percentage ?></td>
                                <td><?= date("d-m-Y h:i:s A", strtotime($AllUserCategory->added_date)) ?></td>
                                <?php if ($_ENV['ENVIRONMENT'] != 'demo') { ?>
                                    <td>
                                        <a href="<?= base_url('backend/UserCategory/edit/' . $AllUserCategory->id) ?>" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>
                                        | <a href="<?= base_url('backend/UserCategory/delete/' . $AllUserCategory->id) ?>" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-times"></span></a>
                                    </td>
                                <?php } ?>
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