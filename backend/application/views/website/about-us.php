    <div class="container-main">
        <div class="banner banner_small">
            <div class="text">
                <h1><?= t('nav_about') ?></h1>
                <p><?= t('about_page_subtitle') ?></p>
            </div>
        </div>
        <div class="container" style="width: auto">
            <div class="lift_box">
                <div class="lift_down">
                    <div class="icon">
                   <img src="<?= base_url(LOGO.$Setting->logo) ?>" alt="">
                    </div>
                    <div class="text">
                        <p><?= PROJECT_NAME ?></p>
                        <p><?= t('common_cta') ?></p>
                    </div>
                    <div class="down">
                        <a href="<?= base_url('game.apk') ?>" download><?= t('download_apk') ?></a>
                    </div>
                </div>
            </div>
            <div class="right_box">
                <div class="box">
                    <h3><?= t('nav_about') ?></h3>
                    <?= localize_content($Setting->about_us) ?>
                </div>
            </div>
            <div class="clear"></div>
        </div>
    </div>
