<!DOCTYPE html>
<html lang="<?= current_language() ?>">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0,minimal-ui">
    <title><?= PROJECT_NAME ?></title>

    <link rel="shortcut icon" href="<?= base_url(LOGO.$Setting->logo)?>">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/metismenu.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/icons.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/toastr.css') ?>" rel="stylesheet" type="text/css">
    <script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/toastr.min.js') ?>"></script>
    <!-- <link href="<?= base_url('assets/js/toastr.min.js') ?>" rel="stylesheet" type="text/css"> -->
    <style type="text/css">
    .card {
        border: none;
        box-shadow: none !important;
        margin-bottom: 30px;
    }


    .btn-primary.login_bt {
        background: #da1d1d;
        border-color: #da1d1d;
    }

    .preferences-bar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        padding: 16px 24px 0;
    }

    .preference-select {
        border-radius: 999px;
        padding: 8px 12px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(0, 0, 0, 0.25);
        color: #fff;
        min-width: 120px;
    }
    </style>

</head>

<body style="background-image: url('<?= base_url('assets/images/sp_bg.png') ?>');">
    <div class="preferences-bar">
        <select class="preference-select" onchange="if (this.value) { window.location.href = this.value; }" aria-label="<?= t('toggle_language') ?>">
            <?php foreach (supported_languages() as $code => $label) { ?>
                <option value="<?= language_switch_url($code) ?>" <?= current_language() === $code ? 'selected' : '' ?>><?= $label ?></option>
            <?php } ?>
        </select>
        <select class="preference-select" onchange="if (this.value) { window.location.href = this.value; }" aria-label="<?= t('toggle_currency') ?>">
            <?php foreach (supported_currencies() as $code => $details) { ?>
                <option value="<?= currency_switch_url($code) ?>" <?= current_currency() === $code ? 'selected' : '' ?>><?= $details['label'] ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="home-btn d-none d-sm-block"><a href="<?= base_url()?>" class="text-dark"><i
                class="fas fa-home h2"></i></a></div>
    <div class="wrapper-page">
        <div class="card overflow-hidden account-card mx-3">
            <div class=" p-4 text-white text-center position-relative" style="background:#000000;">
                <!-- <h4 class="font-20 m-b-5">Welcome Back !</h4> -->
                <?php if ($_ENV['ENVIRONMENT']!= 'demo') { ?>
                <p class="mb-4" style="color: #f7c5c5a1 !important;"><?= t('login_signin') ?></p><a
                    href="#" class="logo logo-admin"><img src="<?= base_url(LOGO.$Setting->logo) ?>" height="80"
                        alt="logo"></a>
                        <?php }else{ ?>
                            <p class="mb-4" style="color: #f7c5c5a1 !important;"><?= t('login_signin_demo') ?></p>
                            <?php } ?>
            </div>
            <div class="account-card-content">
                <?php
                echo  $this->load->view('src/notification', true, true);
    $form = array(
        'class' => 'form-horizontal m-t-30',
        'id' => 'login',
        'autocomplete' => 'off'
    );
    echo form_open('backend/auth/index', $form);
    ?>

                <div class="form-group"><label for="username"><?= t('login_username') ?></label>
                    <?php
        $email = array(

            'id' => 'email',
            'name' => 'email',
            'type' => 'email',
            'class' => 'form-control',
            'required' => '',
            'value' => set_value('email'),
            'placeholder' => t('login_username')
        );
    echo form_input($email);
    ?>
                </div>
                <div class="form-group"><label for="userpassword"><?= t('login_password') ?></label>
                    <?php
        $password = array(

            'id' => 'password',
            'name' => 'password',
            'type' => 'password',
            'class' => 'form-control',
            'required' => '',
            'value' => set_value('password'),
            'placeholder' => t('login_password')
        );
    echo form_input($password);
    ?>
                </div>
                <div class="form-group row m-t-20">
                    <div class="col-sm-6">
                        <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input"
                                id="customControlInline"> <label class="custom-control-label"
                                for="customControlInline"><?= t('login_remember') ?></label></div>
                    </div>
                    <div class="col-sm-6 text-right">
                        <input type="hidden" name="redirect" value="<?= $this->input->get('redirect') ?>">
                        <?php
            echo form_submit('submit', t('login_button'), array('class' => 'btn btn-primary login_bt w-md waves-effect waves-light'));
    ?>
                        <!-- <button class="btn btn-primary w-md waves-effect waves-light" type="submit">Log In</button></div> -->
                    </div>
                    <div class="form-group m-t-10 mb-0 row">
                        <!-- <div class="col-12 m-t-20"><a href="pages-recoverpw.html"><i class="mdi mdi-lock"></i> Forgot your password?</a></div> -->
                    </div>
                    </form>
                </div>
            </div>
            <?php if ($_ENV['ENVIRONMENT']!= 'demo') { ?>
            <div class="m-t-40 text-center">
                <!-- <p>Don't have an account ? <a href="pages-register.html" class="font-500 text-primary">Signup now</a></p> -->
                
                <p> © <?= date("Y").' '. ((!empty($setting->copyright_project_name))?$setting->copyright_project_name:PROJECT_NAME) ?> <span class="d-none d-sm-inline-block">- Crafted with <i
               class="mdi mdi-heart text-danger"></i> by <?= (!empty($setting->copyright_company_name))?$setting->copyright_company_name:COMPANY_NAME ?></span></p>
            </div>
            <?php } ?>
        </div><!-- end wrapper-page -->
        <!-- jQuery  -->
       <div class="form-group mt-3 px-4"> 
    <div class="d-flex justify-content-between">
            <a href="https://demo-games.androappstech.in/" target="_blank" class="btn btn-success px-4 waves-effect waves-light"><?= t('play_now') ?></a>
            <a href="<?= base_url('download') ?>" class="btn btn-info px-4 waves-effect waves-light"><?= t('download_apk_now') ?></a>
            <a href="install.mobileconfig" class="btn btn-info px-4 waves-effect waves-light"><?= t('download_ios') ?></a>
        </div>
    </div>
    
        <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
        <script src="<?= base_url('assets/js/metisMenu.min.js') ?>"></script>
        <script src="<?= base_url('assets/js/jquery.slimscroll.js') ?>"></script>
        <script src="<?= base_url('assets/js/waves.min.js') ?>"></script>
        <script src="<?= base_url('assets/js/app.js') ?>"></script>
        <script>
            const bc = new BroadcastChannel("my-awesome-site");

bc.onmessage = (event) => {
  if (event.data === `Am I the first?`) {
    bc.postMessage(`No you're not.`);
    alert(`Another tab of this site just got opened`);
  }
  if (event.data === `No you're not.`) {
    alert(`An instance of this site is already running`);
  }
};

bc.postMessage(`Am I the first?`);
        </script>
</body>

</html>
