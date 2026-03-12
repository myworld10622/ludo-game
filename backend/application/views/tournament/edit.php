<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/RummyTournaMent/update', ['autocomplete' => false, 'id' => 'edit_tournament'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_user_category'),
                'id'=> $data->id])
                ?>
             
             <div class="form-group row">
                <label for="name"  class="col-sm-2 col-form-label">Name *</label>
                    <div class="col-md-3">
                        <input class="form-control" type="text" Placeholder="Name"  name="name" value="<?= $data->name ?>" required
                           >
                    </div>
                </div>
             <div class="form-group row">
                <label for="no_of_participant"  class="col-sm-2 col-form-label">No. Of Participant *</label>
                    <div class="col-md-3">
                   
                        <input class="form-control" type="text" Placeholder="No. Of Participant"  name="no_of_participant" value="<?= $data->no_of_participant ?>" required
                           >
                    </div>
                </div>
                <div class="form-group row">
                <label for="registration_fee"  class="col-sm-2 col-form-label" >Registration Fee *</label>
                    <div class="col-md-3">
                        <input class="form-control" type="text" Placeholder="Registration Fee" value="<?= $data->registration_fee ?>"  name="registration_fee" required
                           >
                    </div>
                </div>
                <div class="form-group row">
                <label for="first_price"  class="col-sm-2 col-form-label">First Price *</label>
                    <div class="col-md-3">
                        <input class="form-control" type="text" Placeholder="First Price"  value="<?= $data->first_price ?>" name="first_price" required
                           >
                    </div>
                </div>
                <div class="form-group row">
                <label for="second_price"  class="col-sm-2 col-form-label">Second Price *</label>
                    <div class="col-md-3">
                        <input class="form-control" type="text" Placeholder="Second Price" value="<?= $data->second_price ?>"  name="second_price" required
                           >
                    </div>
                </div>
                <div class="form-group row">
                <label for="third_price"  class="col-sm-2 col-form-label">Third Price *</label>
                    <div class="col-md-3">
                        <input class="form-control" type="text" Placeholder="Third Price" value="<?= $data->third_price ?>" name="third_price" required
                           >
                    </div>
                </div>
                <div class="form-group row">
                <label for="start_time"  class="col-sm-2 col-form-label">Start Time *</label>
                    <div class="col-md-3">
                        <input class="form-control" type="text" Placeholder="Start Time" value="<?= $data->start_time ?>" name="start_time" required
                            id="datetimepicker1">
                    </div>
                </div>


                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Update', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/RummyTournaMent')?>" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>
    <script>
    function updateValue(x) {
        $('#point_value').val(x * 80);
    }
    </script>