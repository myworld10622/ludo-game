<div class="container-main">
    <div class="banner banner_small">
        <div class="text">
            <h1><?= t('faq_intro_title') ?></h1>
            <p><?= t('faq_title_full') ?></p>
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
                <div class="title"><b><?= t('faq_q1') ?></b></div>
                <p><?= t('faq_a1') ?></p>
                <div class="title"><b><?= t('faq_q2') ?></b></div>
                <p><?= t('faq_a2') ?></p>
                <div class="title"><b><?= t('faq_q3') ?></b></div>
                <p><?= t('faq_a3') ?></p>
                <div class="title"><b><?= t('faq_q4') ?></b></div>
                <p><?= t('faq_a4') ?></p>
                <div>
                    <img style="display:block; margin:0 auto; width: 320px;"
                        src="<?= base_url('assets/website/images/teen-patti-cash-ranking-of-hands.png') ?>"
                        alt="google play protect <?= PROJECT_NAME ?>">
                </div>
            </div>
        </div>
        <div class="clear"></div>
    </div>
</div>
