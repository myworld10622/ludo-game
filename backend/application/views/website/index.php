    <div class="container-main">
        <div class="banner banner-bg" style="color:white">
            <div class="text">
                <h1><?= t('hero_title') ?></h1>
                <p><?= t('hero_subtitle') ?></p>
                <a href="<?= base_url('game.apk') ?>" download class="download_btn download_copy"><?= t('download_apk') ?></a>
            </div>
        </div>
        <div class="section_1">
            <div class="container">
                <div class="left">
                    <div class="text">
                        <h2><?= t('homepage_title') ?></h2>
                        <p><?= t('homepage_social') ?></p>
                        <div class="share_small">
                            <button class="social_share" data-type="fb"><i class="fa share-fb"></i></button>
                            <button class="social_share" data-type="twitter"><i class="share-twitter"></i></button>
                            <button class="social_share" data-type="vk"><i class="share-vk"></i></button>
                        </div>
                    </div>
                </div>
                <div class="right">
                    <div class="text">
                        <p><?= t('homepage_about_1') ?></p>
                        <p><?= t('homepage_about_2') ?></p>
                        <p><?= t('homepage_about_3') ?></p>
                        <!-- <div class="download">
                            <a href="home/download">
                                <img src="#" data-src="<?= base_url('assets/website/images/button-android.png') ?>"
                                    class="lazy" height="60" alt="">
                            </a>
                            <a href="home/download">
                                <img src="#" data-src="<?= base_url('assets/website/images/button-apkpure.png') ?>"
                                    class="lazy" height="60" alt="">
                            </a>
                        </div> -->
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
        <div class="container section_2">
            <div class="icon">
                <img src="<?= LOGO.$Setting->logo ?>" alt="<?= PROJECT_NAME ?> icon">
                <div><?= t('section_cards_title') ?></div>
                <p class="name"><?= PROJECT_NAME ?></p>
            </div>
            <div class="divider">
                <div class="number">1</div>
            </div>
            <div class="left">
                <div class="text">
                    <div class="title"><?= t('feature_realtime_title') ?></div>
                    <p><?= t('feature_realtime_text') ?></p>
                    <div class="title"><?= t('feature_practice_title') ?></div>
                    <p><?= t('feature_practice_text') ?></p>
                    <div class="title"><?= t('feature_money_title') ?></div>
                    <p><?= t('feature_money_text') ?></p>
                </div>
            </div>
            <div class="right">
             <img class="img_bg lazy" src="#" data-src="<?= IMAGE_URL.$banner->image1 ?>"
                    alt="<?= PROJECT_NAME ?> feature image1">
            </div>
            <div class="clear"></div>
        </div>
        <div class="container section_3">
            <div class="left">
                <div class="number">2</div>
           <img class="img_bg lazy" src="#" data-src="<?= IMAGE_URL.$banner->image2 ?>"
                    alt="<?= PROJECT_NAME ?> feature image2">
                <div class="text">
                    <div class="title"><?= t('feature_signup_title') ?></div>
                    <p><?= t('feature_signup_text') ?></p>
                </div>
            </div>
            <div class="right">
                <div class="number">3</div>
                <img class="img_bg lazy" src="#" data-src="<?= IMAGE_URL.$banner->image3 ?>"
                    alt="<?= PROJECT_NAME ?> feature image3">
                <div class="text">
                    <div class="title"><?= t('feature_bonus_title') ?></div>
                    <p><?= t('feature_bonus_text') ?></p>
                </div>
            </div>
            <div class="left">
                <div class="number">4</div>
           <img class="img_bg lazy" src="#" data-src="<?= IMAGE_URL.$banner->image4 ?>"
                    alt="<?= PROJECT_NAME ?> feature image4">
                <div class="text">
                    <div class="title"><?= t('feature_cash_title') ?></div>
                    <p><?= t('feature_cash_text') ?></p>
                </div>
            </div>
            <div class="right">
                <div class="number">5</div>
           <img class="img_bg lazy" src="#" data-src="<?= IMAGE_URL.$banner->image5 ?>"
                    alt="<?= PROJECT_NAME ?> feature image5">
                <div class="text">
                    <div class="title"><?= t('feature_anytime_title') ?></div>
                    <p><?= t('feature_anytime_text') ?></p>
                </div>
            </div>
            <div class="clear"></div>
        </div>
        <div class="section_4">
            <div class="container">
                <img src="#" data-src="<?= IMAGE_URL.$banner->image1 ?>" class="lazy" height="200" alt="">
                <img src="#" data-src="<?= IMAGE_URL.$banner->image2 ?>" class="lazy" height="200" alt="">
                <img src="#" data-src="<?= IMAGE_URL.$banner->image3 ?>" class="lazy" height="200" alt="">
                <img src="#" data-src="<?= IMAGE_URL.$banner->image4 ?>" class="lazy" height="200" alt="">
            </div>
        </div>
    </div>
