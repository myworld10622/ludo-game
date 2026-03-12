<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php
            echo form_open_multipart('backend/RobotCards/insert', ['autocomplete' => false, 'id' => 'add_robot_card'
                ,'method'=>'post'], ['type' => $this->url_encrypt->encode('tbl_robot_cards')])
            ?>
                <div class="form-group row">
                    <?php foreach ($AllCards as $key => $value) { ?>
                    <div class="col-md-1">
                    <img src="<?= base_url('data/cards/' . strtolower($value->cards) . '.png'); ?>">
                    <input class="form-control" type="checkbox" value="<?= $value->cards ?>"  name="cards[]"
                        id="name">
                    </div>
               <?php } ?>
                </div>

                <div class="form-group mb-0">
                    <div>
                        <?php
                        echo form_submit('submit', 'Submit', ['class' => 'btn btn-primary waves-effect waves-light mr-1 submit']);
                        ?>
                        <a href="<?= base_url('backend/RobotCards') ?>" class="btn btn-secondary waves-effect">Cancel</a>

                    </div>
                </div>
                <?php
            echo form_close();
            ?>
            </div>
        </div><!-- end col -->
    </div>
    <script>
$('input:checkbox').change(function() {
if($('input:checkbox:checked').length > 3) {
  alert('You can not select more than 3 cards')
  $(this).prop('checked', false);
}
})
$('.submit').click(function() {
if($('input:checkbox:checked').length <3) {
//   alert('You need to select 3 cards')
  $('#add_robot_card').submit();
}
})
    </script>