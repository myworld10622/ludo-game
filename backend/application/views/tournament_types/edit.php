<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo form_open_multipart('backend/TournamentTypes/update', [
                    'autocomplete' => false,
                    'id' => 'edit_tournament_types',
                    'method' => 'post'
                ], [
                    'type' => $this->url_encrypt->encode('tbl_rummy_tournament_types'),
                    'id' => $TournamentTypes->id
                ]);
                ?>

                <!-- Name Input -->
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">Name*</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="text" name="name" value="<?= $TournamentTypes->name ?>"
                            required id="name">
                    </div>
                </div>

                <!-- Image Input -->
                <div class="form-group row">
                    <label for="image" class="col-sm-2 col-form-label">Image*</label>
                    <div class="col-sm-10">
                        <!-- Display the current image if it exists -->
                        <?php if (!empty($TournamentTypes->image)): ?>
                            <img src="<?= base_url('uploads/images/' . $TournamentTypes->image) ?>" alt="Current Image"
                                width="100">
                            <br><br>
                        <?php endif; ?>
                        <input class="form-control" type="file" name="image" id="image">
                        <input type="hidden" name="existing_image" value="<?= $TournamentTypes->image ?>">
                        <!-- Pass the current image name -->
                    </div>
                </div>

                <!-- Submit and Cancel Buttons -->
                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1']);
                        ?>
                        <a href="<?= base_url('backend/TournamentTypes') ?>"
                            class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </div>
                <?php
                echo form_close();
                ?>
            </div>
        </div>
    </div>
</div>