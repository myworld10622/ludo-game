<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('localization_ci')) {
    function localization_ci()
    {
        return get_instance();
    }
}

if (!function_exists('supported_languages')) {
    function supported_languages()
    {
        return [
            'en' => 'English',
            'hi' => 'Hindi',
            'bn' => 'Bengali',
            'ar' => 'Arabic',
        ];
    }
}

if (!function_exists('supported_currencies')) {
    function supported_currencies()
    {
        return [
            'INR' => ['label' => 'INR', 'symbol' => 'Rs', 'rate' => 1],
            'USD' => ['label' => 'USD', 'symbol' => '$', 'rate' => 0.012],
            'AED' => ['label' => 'AED', 'symbol' => 'AED', 'rate' => 0.044],
            'EUR' => ['label' => 'EUR', 'symbol' => 'EUR', 'rate' => 0.011],
        ];
    }
}

if (!function_exists('current_language')) {
    function current_language()
    {
        $ci = localization_ci();
        $language = $ci->session->userdata('site_language') ?: 'en';

        return array_key_exists($language, supported_languages()) ? $language : 'en';
    }
}

if (!function_exists('current_currency')) {
    function current_currency()
    {
        $ci = localization_ci();
        $currency = strtoupper($ci->session->userdata('site_currency') ?: 'INR');

        return array_key_exists($currency, supported_currencies()) ? $currency : 'INR';
    }
}

if (!function_exists('currency_details')) {
    function currency_details($currencyCode = null)
    {
        $currencies = supported_currencies();
        $currencyCode = $currencyCode ?: current_currency();

        return $currencies[$currencyCode] ?? $currencies['INR'];
    }
}

if (!function_exists('format_money')) {
    function format_money($amount, $precision = 2)
    {
        $currency = currency_details();
        $converted = ((float) $amount) * $currency['rate'];

        return $currency['symbol'] . ' ' . number_format($converted, $precision);
    }
}

if (!function_exists('localize_content')) {
    function localize_content($value)
    {
        if (!is_string($value) || trim($value) === '') {
            return $value;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $language = current_language();

            if (!empty($decoded[$language])) {
                return $decoded[$language];
            }

            if (!empty($decoded['en'])) {
                return $decoded['en'];
            }
        }

        return $value;
    }
}

if (!function_exists('language_switch_url')) {
    function language_switch_url($language)
    {
        return base_url('preferences/language/' . strtolower($language));
    }
}

if (!function_exists('currency_switch_url')) {
    function currency_switch_url($currency)
    {
        return base_url('preferences/currency/' . strtoupper($currency));
    }
}

if (!function_exists('t')) {
    function t($key, $params = [])
    {
        $translations = [
            'en' => [
                'meta_description' => 'Download and play {project}, enjoy competitive online gameplay, daily rewards, and secure real-cash tournaments.',
                'play_win_title' => 'Play {project}, win bigger every day',
                'nav_home' => 'Home',
                'nav_download' => 'Download',
                'nav_faq' => 'FAQ',
                'nav_about' => 'About Us',
                'nav_privacy' => 'Privacy Policy',
                'nav_terms' => 'Terms & Conditions',
                'nav_refund' => 'Refund Policy',
                'nav_security' => 'Security',
                'nav_contact' => 'Contact Us',
                'toggle_language' => 'Language',
                'toggle_currency' => 'Currency',
                'hero_title' => 'Join {project} and unlock daily cash rewards',
                'hero_subtitle' => 'Fast matches, smooth gameplay, and secure withdrawals built for serious players.',
                'download_apk' => 'Download APK',
                'homepage_title' => '{project} lets you compete for real cash prizes',
                'homepage_social' => 'Follow {project} on social media',
                'homepage_about_1' => '{project} is operated by {company} and built to deliver a smooth, secure, and reliable gaming experience across Android, iOS, and the web.',
                'homepage_about_2' => 'Our goal is to make skill-based gaming simple to access, easy to understand, and exciting for every kind of player.',
                'homepage_about_3' => 'From quick tables to tournaments, the platform is optimized for performance, fairness, and uninterrupted multiplayer gameplay.',
                'section_cards_title' => 'Competitive Indian skill gaming',
                'feature_realtime_title' => 'Real-time gameplay',
                'feature_realtime_text' => 'Play live matches with players across regions and enjoy responsive game action on mobile.',
                'feature_practice_title' => 'Practice before you compete',
                'feature_practice_text' => 'Learn the flow, sharpen your strategy, and build confidence before entering real contests.',
                'feature_money_title' => 'Play for real rewards',
                'feature_money_text' => 'Join contests, climb the leaderboard, and convert your best sessions into real winnings.',
                'feature_signup_title' => 'Simple signup',
                'feature_signup_text' => 'Create your account in minutes and start playing without a complicated onboarding flow.',
                'feature_bonus_title' => 'Daily bonus support',
                'feature_bonus_text' => 'Come back every day to collect bonus value, promotions, and ongoing player offers.',
                'feature_cash_title' => 'Cash tables and tournaments',
                'feature_cash_text' => 'Choose the format that suits your budget and jump straight into active tables.',
                'feature_anytime_title' => 'Play anywhere',
                'feature_anytime_text' => 'Access the platform on the go and keep playing whenever you are ready.',
                'footer_top' => '{project} brings competitive card and board gameplay, real-time action, and rewarding tournaments to players worldwide.',
                'footer_copy' => 'All rights reserved',
                'share_title' => '{project}',
                'share_subtitle' => 'Download and play {project} for exciting online matches, rewards, and secure gameplay.',
                'login_signin' => 'Sign in to continue to {project}.',
                'login_signin_demo' => 'Sign in to continue to Demo.',
                'login_username' => 'Username',
                'login_password' => 'Password',
                'login_remember' => 'Remember me',
                'login_button' => 'Log In',
                'login_profile' => 'Profile',
                'login_logout' => 'Logout',
                'play_now' => 'Play Now',
                'download_apk_now' => 'Download APK Now',
                'download_ios' => 'Download iOS App',
                'admin_dashboard' => 'Dashboard',
                'agent_dashboard' => 'Agent Dashboard',
                'role_subadmin' => 'SUB ADMIN',
                'role_agent' => 'AGENT',
                'top_profile' => 'Profile',
                'top_logout' => 'Logout',
                'support_note' => 'For clarification or support, please connect using the details below. Fraud outside these channels is your own risk.',
                'total_my_coins' => 'Total My Coins',
                'active_user' => 'Active User',
                'active_agent' => 'Active Agent',
                'admin_wallet' => 'Admin Wallet',
                'deduct' => 'Deduct',
                'add' => 'Add',
                'add_admin_wallet' => 'Add to Admin Wallet',
                'deduct_admin_wallet' => 'Deduct from Admin Wallet',
                'enter_amount_add' => 'Enter the amount to add to the admin wallet:',
                'enter_amount_deduct' => 'Enter the amount to deduct from the admin wallet:',
                'amount' => 'Amount',
                'cancel' => 'Cancel',
                'submit' => 'Submit',
                'total_user' => 'Total User',
                'total_deposit' => 'Total Deposit',
                'today_deposit' => 'Today Deposit',
                'pending_deposit' => 'Pending Deposit',
                'rejected_deposit' => 'Rejected Deposit',
                'total_withdraw' => 'Total Withdraw',
                'today_withdraw' => 'Today Withdraw',
                'pending_withdraw' => 'Pending Withdraw',
                'rejected_withdraw' => 'Rejected Withdraw',
                'total_bot_balance' => 'Total Bot Balance',
                'today_new_users' => 'Today New Users',
                'jackpot_status' => 'Jackpot Status',
                'jackpot_coin' => 'Jackpot Coin',
                'search_user_mobile' => 'Search User by Mobile Number',
                'user_name' => 'User Name',
                'user_id' => 'User ID',
                'mobile_length_error' => 'Mobile number must be 10 digits.',
                'status_off' => 'OFF',
                'status_on' => 'ON',
                'amount_validation' => 'Please enter a valid amount greater than 0.',
                'generic_error' => 'Something went wrong. Please try again.',
                'mobile_required' => 'Please enter a mobile number',
                'user_not_found' => 'User not found',
                'status_changed' => 'Status changed successfully',
                'common_help_text' => 'Contact the {project} team for more help',
                'common_cta' => 'Install and play {project} to compete for real cash rewards.',
                'faq_title_full' => 'Frequently Asked Questions',
                'faq_intro_title' => 'FAQ',
                'faq_q1' => 'I cannot install the {project} app',
                'faq_a1' => 'Make sure installation from unknown sources is allowed in your device settings, then download the app again from the official website and open the APK from your downloads folder.',
                'faq_q2' => 'I cannot update {project}',
                'faq_a2' => 'If the latest update does not install correctly, remove the old version and install the newest APK again from the official site.',
                'faq_q3' => 'How do I play {project}?',
                'faq_a3' => '{project} offers fast online matches designed for skill-based play. Join a table, understand the room rules, manage your entry amount, and compete against live players in real time.',
                'faq_q4' => 'What are the rules of {project}?',
                'faq_a4' => 'The goal is to play within the defined room rules, make better decisions than your opponents, and finish with the strongest result in the match or round format you joined.',
                'about_page_subtitle' => 'Learn more about the {project} platform',
                'privacy_page_subtitle' => 'Read how {project} protects player information',
                'terms_page_subtitle' => 'Review the terms that apply to using {project}',
                'refund_page_subtitle' => 'Understand the refund policy for {project}',
                'security_page_subtitle' => 'See how {project} protects gameplay and accounts',
                'contact_page_subtitle' => 'Reach the {project} team for support and business queries',
                'security_heading' => 'Security',
                'security_body_1' => 'At {project}, platform security is a top priority so players can enjoy fair and reliable gameplay.',
                'security_body_2' => 'Transactions are protected, gameplay systems are monitored, and account-level safety checks help reduce fraud risk.',
                'security_body_3' => 'We continuously review our security practices and apply updates whenever improvements are required.',
            ],
            'hi' => [
                'meta_description' => '{project} download karke competitive online gameplay, daily rewards aur secure real-cash tournaments ka maza lijiye.',
                'play_win_title' => '{project} khelo, roz zyada jeeto',
                'nav_home' => 'Home',
                'nav_download' => 'Download',
                'nav_faq' => 'FAQ',
                'nav_about' => 'About Us',
                'nav_privacy' => 'Privacy Policy',
                'nav_terms' => 'Terms & Conditions',
                'nav_refund' => 'Refund Policy',
                'nav_security' => 'Security',
                'nav_contact' => 'Contact Us',
                'toggle_language' => 'Bhasha',
                'toggle_currency' => 'Currency',
                'hero_title' => '{project} join kariye aur daily cash rewards unlock kariye',
                'hero_subtitle' => 'Fast matches, smooth gameplay aur secure withdrawals serious players ke liye tayar kiye gaye hain.',
                'download_apk' => 'APK Download Karein',
                'homepage_title' => '{project} par real cash prizes ke liye compete kariye',
                'homepage_social' => '{project} ko social media par follow kariye',
                'homepage_about_1' => '{project}, {company} dwara operate kiya jata hai aur Android, iOS aur web par smooth, secure aur reliable gaming experience dene ke liye bana hai.',
                'homepage_about_2' => 'Hamari koshish hai ki skill-based gaming har player ke liye easy, samajhne me simple aur exciting bane.',
                'homepage_about_3' => 'Quick tables se lekar tournaments tak, platform performance, fairness aur uninterrupted multiplayer gameplay ke liye optimize kiya gaya hai.',
                'section_cards_title' => 'Competitive Indian skill gaming',
                'feature_realtime_title' => 'Real-time gameplay',
                'feature_realtime_text' => 'Alag-alag regions ke players ke saath live matches khelo aur mobile par responsive gameplay ka maza lo.',
                'feature_practice_title' => 'Compete karne se pehle practice',
                'feature_practice_text' => 'Game flow samjho, strategy improve karo aur real contests se pehle confidence build karo.',
                'feature_money_title' => 'Real rewards ke liye khelo',
                'feature_money_text' => 'Contests join karo, leaderboard par aao aur apni best sessions ko real winnings me badlo.',
                'feature_signup_title' => 'Simple signup',
                'feature_signup_text' => 'Kuch hi minutes me account banao aur bina complicated onboarding ke game start karo.',
                'feature_bonus_title' => 'Daily bonus support',
                'feature_bonus_text' => 'Har din wapas aakar bonus value, promotions aur ongoing player offers collect karo.',
                'feature_cash_title' => 'Cash tables aur tournaments',
                'feature_cash_text' => 'Apne budget ke hisab se format choose karo aur active tables me turant join karo.',
                'feature_anytime_title' => 'Kabhi bhi khelo',
                'feature_anytime_text' => 'Chalte-phirte platform access karo aur jab chaho tab game continue karo.',
                'footer_top' => '{project} players ko competitive card aur board gameplay, real-time action aur rewarding tournaments provide karta hai.',
                'footer_copy' => 'Sabhi adhikar surakshit hain',
                'share_title' => '{project}',
                'share_subtitle' => '{project} download karke exciting online matches, rewards aur secure gameplay ka experience lijiye.',
                'login_signin' => '{project} par continue karne ke liye sign in kariye.',
                'login_signin_demo' => 'Demo par continue karne ke liye sign in kariye.',
                'login_username' => 'Username',
                'login_password' => 'Password',
                'login_remember' => 'Mujhe yaad rakhein',
                'login_button' => 'Log In',
                'login_profile' => 'Profile',
                'login_logout' => 'Logout',
                'play_now' => 'Abhi Khelo',
                'download_apk_now' => 'APK Download Karein',
                'download_ios' => 'iOS App Download Karein',
                'admin_dashboard' => 'Dashboard',
                'agent_dashboard' => 'Agent Dashboard',
                'role_subadmin' => 'SUB ADMIN',
                'role_agent' => 'AGENT',
                'top_profile' => 'Profile',
                'top_logout' => 'Logout',
                'support_note' => 'Clarification ya support ke liye niche diye gaye details par hi connect karein. In channels ke bahar hone wale fraud ki zimmedari aapki hogi.',
                'total_my_coins' => 'Mere Total Coins',
                'active_user' => 'Active User',
                'active_agent' => 'Active Agent',
                'admin_wallet' => 'Admin Wallet',
                'deduct' => 'Deduct',
                'add' => 'Add',
                'add_admin_wallet' => 'Admin Wallet me add karein',
                'deduct_admin_wallet' => 'Admin Wallet se deduct karein',
                'enter_amount_add' => 'Admin wallet me add karne ke liye amount enter karein:',
                'enter_amount_deduct' => 'Admin wallet se deduct karne ke liye amount enter karein:',
                'amount' => 'Amount',
                'cancel' => 'Cancel',
                'submit' => 'Submit',
                'total_user' => 'Total User',
                'total_deposit' => 'Total Deposit',
                'today_deposit' => 'Aaj ka Deposit',
                'pending_deposit' => 'Pending Deposit',
                'rejected_deposit' => 'Rejected Deposit',
                'total_withdraw' => 'Total Withdraw',
                'today_withdraw' => 'Aaj ka Withdraw',
                'pending_withdraw' => 'Pending Withdraw',
                'rejected_withdraw' => 'Rejected Withdraw',
                'total_bot_balance' => 'Total Bot Balance',
                'today_new_users' => 'Aaj ke naye users',
                'jackpot_status' => 'Jackpot Status',
                'jackpot_coin' => 'Jackpot Coin',
                'search_user_mobile' => 'Mobile number se user search karein',
                'user_name' => 'User Name',
                'user_id' => 'User ID',
                'mobile_length_error' => 'Mobile number 10 digits ka hona chahiye.',
                'status_off' => 'OFF',
                'status_on' => 'ON',
                'amount_validation' => '0 se bada valid amount enter karein.',
                'generic_error' => 'Kuch galat ho gaya. Kripya dobara try karein.',
                'mobile_required' => 'Kripya mobile number enter karein',
                'user_not_found' => 'User nahi mila',
                'status_changed' => 'Status successfully change ho gaya',
                'common_help_text' => '{project} team se zyada help ke liye contact kariye',
                'common_cta' => '{project} install karke real cash rewards ke liye compete kariye.',
                'faq_title_full' => 'Frequently Asked Questions',
                'faq_intro_title' => 'FAQ',
                'faq_q1' => 'Main {project} app install nahi kar pa raha hoon',
                'faq_a1' => 'Device settings me unknown sources ya allow install option enable kariye, phir official website se APK dobara download karke downloads folder se open kariye.',
                'faq_q2' => 'Main {project} update nahi kar pa raha hoon',
                'faq_a2' => 'Agar latest update sahi se install nahi hota, to purana version remove karke official site se naya APK dobara install kariye.',
                'faq_q3' => '{project} kaise khela jata hai?',
                'faq_a3' => '{project} fast online matches provide karta hai jo skill-based play ke liye design kiye gaye hain. Table join kariye, room rules samajhiye, entry amount manage kariye aur live players ke saath real time me compete kariye.',
                'faq_q4' => '{project} ke rules kya hain?',
                'faq_a4' => 'Goal ye hota hai ki aap room ke defined rules ke andar better decisions lein, opponents se achha perform karein aur jis match ya round format me aap join hue hain usme strongest result laayein.',
                'about_page_subtitle' => '{project} platform ke baare me aur janiye',
                'privacy_page_subtitle' => 'Janiye {project} player information ko kaise protect karta hai',
                'terms_page_subtitle' => '{project} use karne par lagu terms ko review kariye',
                'refund_page_subtitle' => '{project} ki refund policy samajhiye',
                'security_page_subtitle' => 'Dekhiye {project} gameplay aur accounts ko kaise protect karta hai',
                'contact_page_subtitle' => 'Support aur business queries ke liye {project} team se connect kariye',
                'security_heading' => 'Security',
                'security_body_1' => '{project} par platform security top priority hai taaki players fair aur reliable gameplay enjoy kar saken.',
                'security_body_2' => 'Transactions protected hote hain, gameplay systems monitor kiye jate hain, aur account-level safety checks fraud risk kam karte hain.',
                'security_body_3' => 'Hum apni security practices ko regularly review karte hain aur zarurat padne par updates apply karte hain.',
            ],
            'bn' => [
                'meta_description' => '{project} download kore competitive online gameplay, daily rewards ebong secure real-cash tournaments upobhog korun.',
                'play_win_title' => '{project} khelun, protidin aro beshi jitin',
                'nav_home' => 'Home',
                'nav_download' => 'Download',
                'nav_faq' => 'FAQ',
                'nav_about' => 'About Us',
                'nav_privacy' => 'Privacy Policy',
                'nav_terms' => 'Terms & Conditions',
                'nav_refund' => 'Refund Policy',
                'nav_security' => 'Security',
                'nav_contact' => 'Contact Us',
                'toggle_language' => 'Language',
                'toggle_currency' => 'Currency',
                'hero_title' => '{project} join kore daily cash rewards unlock korun',
                'hero_subtitle' => 'Fast matches, smooth gameplay ebong secure withdrawals serious players der jonno toiri.',
                'download_apk' => 'APK Download',
                'homepage_title' => '{project} e real cash prizes er jonno compete korun',
                'homepage_social' => '{project} ke social media te follow korun',
                'homepage_about_1' => '{project}, {company} dara porichalito ebong Android, iOS ebong web e secure gaming experience deyar jonno toiri.',
                'homepage_about_2' => 'Amader lokkho holo skill-based gaming ke sob player er jonno sohoj, clear ebong exciting kora.',
                'homepage_about_3' => 'Quick tables theke tournaments porjonto, ei platform performance, fairness ebong uninterrupted multiplayer gameplay er jonno optimized.',
                'section_cards_title' => 'Competitive Indian skill gaming',
                'feature_realtime_title' => 'Real-time gameplay',
                'feature_realtime_text' => 'Bibhinno region er player der sathe live matches khelun ebong responsive mobile gameplay upobhog korun.',
                'feature_practice_title' => 'Compete korar age practice',
                'feature_practice_text' => 'Game flow bujhun, strategy better korun ebong real contests er age confidence build korun.',
                'feature_money_title' => 'Real rewards er jonno khelun',
                'feature_money_text' => 'Contests join korun, leaderboard e uthun ebong bhalo sessions ke real winnings e porinoto korun.',
                'feature_signup_title' => 'Simple signup',
                'feature_signup_text' => 'Koyek minute er moddhe account toiri kore quickly game start korun.',
                'feature_bonus_title' => 'Daily bonus support',
                'feature_bonus_text' => 'Prottek din fire eshe bonus value, promotions ebong ongoing player offers collect korun.',
                'feature_cash_title' => 'Cash tables ebong tournaments',
                'feature_cash_text' => 'Apnar budget onujayi format choose kore active tables e join korun.',
                'feature_anytime_title' => 'Jekono somoy khelun',
                'feature_anytime_text' => 'Cholar pothe platform access korun ebong jokhon ichchha tokhon game continue korun.',
                'footer_top' => '{project} players der jonno competitive card ebong board gameplay, real-time action ebong rewarding tournaments niye ashe.',
                'footer_copy' => 'All rights reserved',
                'share_title' => '{project}',
                'share_subtitle' => '{project} download kore exciting online matches, rewards ebong secure gameplay upobhog korun.',
                'login_signin' => '{project} e continue korte sign in korun.',
                'login_signin_demo' => 'Demo te continue korte sign in korun.',
                'login_username' => 'Username',
                'login_password' => 'Password',
                'login_remember' => 'Remember me',
                'login_button' => 'Log In',
                'login_profile' => 'Profile',
                'login_logout' => 'Logout',
                'play_now' => 'Play Now',
                'download_apk_now' => 'APK Download',
                'download_ios' => 'iOS App Download',
                'admin_dashboard' => 'Dashboard',
                'agent_dashboard' => 'Agent Dashboard',
                'role_subadmin' => 'SUB ADMIN',
                'role_agent' => 'AGENT',
                'top_profile' => 'Profile',
                'top_logout' => 'Logout',
                'support_note' => 'Clarification ba support er jonno niche deya details e jogajog korun. Ei channel er baire fraud hole tar daitto apnar.',
                'total_my_coins' => 'My Total Coins',
                'active_user' => 'Active User',
                'active_agent' => 'Active Agent',
                'admin_wallet' => 'Admin Wallet',
                'deduct' => 'Deduct',
                'add' => 'Add',
                'add_admin_wallet' => 'Add to Admin Wallet',
                'deduct_admin_wallet' => 'Deduct from Admin Wallet',
                'enter_amount_add' => 'Admin wallet e add korar amount enter korun:',
                'enter_amount_deduct' => 'Admin wallet theke deduct korar amount enter korun:',
                'amount' => 'Amount',
                'cancel' => 'Cancel',
                'submit' => 'Submit',
                'total_user' => 'Total User',
                'total_deposit' => 'Total Deposit',
                'today_deposit' => 'Today Deposit',
                'pending_deposit' => 'Pending Deposit',
                'rejected_deposit' => 'Rejected Deposit',
                'total_withdraw' => 'Total Withdraw',
                'today_withdraw' => 'Today Withdraw',
                'pending_withdraw' => 'Pending Withdraw',
                'rejected_withdraw' => 'Rejected Withdraw',
                'total_bot_balance' => 'Total Bot Balance',
                'today_new_users' => 'Today New Users',
                'jackpot_status' => 'Jackpot Status',
                'jackpot_coin' => 'Jackpot Coin',
                'search_user_mobile' => 'Mobile number diye user search korun',
                'user_name' => 'User Name',
                'user_id' => 'User ID',
                'mobile_length_error' => 'Mobile number 10 digits hote hobe.',
                'status_off' => 'OFF',
                'status_on' => 'ON',
                'amount_validation' => '0 er beshi valid amount din.',
                'generic_error' => 'Kichu vul hoyeche. Abar try korun.',
                'mobile_required' => 'Mobile number enter korun',
                'user_not_found' => 'User pawa jayni',
                'status_changed' => 'Status successfully change hoyeche',
                'common_help_text' => '{project} team er sathe aro sahajyer jonno jogajog korun',
                'common_cta' => '{project} install kore real cash rewards er jonno compete korun.',
                'faq_title_full' => 'Frequently Asked Questions',
                'faq_intro_title' => 'FAQ',
                'faq_q1' => 'Ami {project} app install korte parchhi na',
                'faq_a1' => 'Device settings e unknown sources install allow korun, tarpor official website theke APK abar download kore downloads folder theke open korun.',
                'faq_q2' => 'Ami {project} update korte parchhi na',
                'faq_a2' => 'Jodi latest update thik moto install na hoy, tahole purono version remove kore official site theke notun APK abar install korun.',
                'faq_q3' => '{project} kivabe khela hoy?',
                'faq_a3' => '{project} skill-based play er jonno design kora fast online matches provide kore. Table join korun, room rules bujhun, entry amount manage korun ebong live players der sathe compete korun.',
                'faq_q4' => '{project} er rules ki?',
                'faq_a4' => 'Main goal holo room er rules mene bhalo decision neya, opponents der cheye bhalo perform kora ebong match ba round e strongest result ana.',
                'about_page_subtitle' => '{project} platform somporke aro janun',
                'privacy_page_subtitle' => '{project} player information kivabe protect kore ta janun',
                'terms_page_subtitle' => '{project} use korar terms review korun',
                'refund_page_subtitle' => '{project} er refund policy bujhun',
                'security_page_subtitle' => '{project} kivabe gameplay ebong accounts protect kore dekhen',
                'contact_page_subtitle' => 'Support ebong business query er jonno {project} team er sathe jogajog korun',
                'security_heading' => 'Security',
                'security_body_1' => '{project} e platform security top priority, jate players fair ebong reliable gameplay pete pare.',
                'security_body_2' => 'Transactions protected, gameplay systems monitored, ebong account-level safety checks fraud risk komay.',
                'security_body_3' => 'Amra security practices niyomito review kori ebong proyojon hole updates apply kori.',
            ],
            'ar' => [
                'meta_description' => 'قم بتنزيل {project} واستمتع بلعب تنافسي عبر الإنترنت ومكافآت يومية وبطولات نقدية آمنة.',
                'play_win_title' => 'العب {project} واربح أكثر كل يوم',
                'nav_home' => 'الرئيسية',
                'nav_download' => 'تنزيل',
                'nav_faq' => 'الأسئلة الشائعة',
                'nav_about' => 'من نحن',
                'nav_privacy' => 'سياسة الخصوصية',
                'nav_terms' => 'الشروط والأحكام',
                'nav_refund' => 'سياسة الاسترداد',
                'nav_security' => 'الأمان',
                'nav_contact' => 'اتصل بنا',
                'toggle_language' => 'اللغة',
                'toggle_currency' => 'العملة',
                'hero_title' => 'انضم إلى {project} واحصل على مكافآت نقدية يومية',
                'hero_subtitle' => 'مباريات سريعة ولعب سلس وعمليات سحب آمنة للاعبين الجادين.',
                'download_apk' => 'تنزيل APK',
                'homepage_title' => 'نافس على جوائز نقدية حقيقية في {project}',
                'homepage_social' => 'تابع {project} على وسائل التواصل',
                'homepage_about_1' => 'يتم تشغيل {project} بواسطة {company} لتقديم تجربة لعب آمنة وسلسة على Android وiOS والويب.',
                'homepage_about_2' => 'هدفنا هو جعل ألعاب المهارة سهلة الوصول وواضحة وممتعة لكل لاعب.',
                'homepage_about_3' => 'من الطاولات السريعة إلى البطولات، تم تحسين المنصة للأداء والعدالة واللعب الجماعي المستمر.',
                'section_cards_title' => 'ألعاب مهارية هندية تنافسية',
                'feature_realtime_title' => 'لعب مباشر',
                'feature_realtime_text' => 'العب مباريات حية مع لاعبين من مناطق مختلفة واستمتع بأداء سريع على الهاتف.',
                'feature_practice_title' => 'تدرّب قبل المنافسة',
                'feature_practice_text' => 'تعلّم طريقة اللعب وطوّر استراتيجيتك وابنِ ثقتك قبل البطولات الحقيقية.',
                'feature_money_title' => 'العب من أجل مكافآت حقيقية',
                'feature_money_text' => 'انضم إلى المسابقات واصعد في لوحة الصدارة وحوّل أفضل جلساتك إلى أرباح حقيقية.',
                'feature_signup_title' => 'تسجيل سهل',
                'feature_signup_text' => 'أنشئ حسابك خلال دقائق وابدأ اللعب بدون خطوات معقدة.',
                'feature_bonus_title' => 'مكافآت يومية',
                'feature_bonus_text' => 'عد يوميًا لتحصل على مكافآت وعروض مستمرة للاعبين.',
                'feature_cash_title' => 'طاولات وبطولات نقدية',
                'feature_cash_text' => 'اختر الصيغة المناسبة لميزانيتك وابدأ اللعب فورًا على الطاولات النشطة.',
                'feature_anytime_title' => 'العب في أي وقت',
                'feature_anytime_text' => 'استخدم المنصة أثناء التنقل وواصل اللعب متى شئت.',
                'footer_top' => 'يقدم {project} للاعبين لعبًا تنافسيًا في الوقت الحقيقي وبطولات مجزية.',
                'footer_copy' => 'جميع الحقوق محفوظة',
                'share_title' => '{project}',
                'share_subtitle' => 'قم بتنزيل {project} واستمتع بالمباريات والمكافآت واللعب الآمن.',
                'login_signin' => 'سجل الدخول للمتابعة إلى {project}.',
                'login_signin_demo' => 'سجل الدخول للمتابعة إلى النسخة التجريبية.',
                'login_username' => 'اسم المستخدم',
                'login_password' => 'كلمة المرور',
                'login_remember' => 'تذكرني',
                'login_button' => 'تسجيل الدخول',
                'login_profile' => 'الملف الشخصي',
                'login_logout' => 'تسجيل الخروج',
                'play_now' => 'العب الآن',
                'download_apk_now' => 'تنزيل APK الآن',
                'download_ios' => 'تنزيل تطبيق iOS',
                'admin_dashboard' => 'لوحة التحكم',
                'agent_dashboard' => 'لوحة الوكيل',
                'role_subadmin' => 'مدير فرعي',
                'role_agent' => 'وكيل',
                'top_profile' => 'الملف الشخصي',
                'top_logout' => 'تسجيل الخروج',
                'support_note' => 'للدعم أو الاستفسار، يرجى التواصل عبر التفاصيل أدناه فقط. أي احتيال خارج هذه القنوات يقع على مسؤوليتك.',
                'total_my_coins' => 'إجمالي رصيدي',
                'active_user' => 'المستخدمون النشطون',
                'active_agent' => 'الوكلاء النشطون',
                'admin_wallet' => 'محفظة الإدارة',
                'deduct' => 'خصم',
                'add' => 'إضافة',
                'add_admin_wallet' => 'إضافة إلى محفظة الإدارة',
                'deduct_admin_wallet' => 'خصم من محفظة الإدارة',
                'enter_amount_add' => 'أدخل المبلغ المراد إضافته إلى محفظة الإدارة:',
                'enter_amount_deduct' => 'أدخل المبلغ المراد خصمه من محفظة الإدارة:',
                'amount' => 'المبلغ',
                'cancel' => 'إلغاء',
                'submit' => 'إرسال',
                'total_user' => 'إجمالي المستخدمين',
                'total_deposit' => 'إجمالي الإيداع',
                'today_deposit' => 'إيداع اليوم',
                'pending_deposit' => 'إيداع معلق',
                'rejected_deposit' => 'إيداع مرفوض',
                'total_withdraw' => 'إجمالي السحب',
                'today_withdraw' => 'سحب اليوم',
                'pending_withdraw' => 'سحب معلق',
                'rejected_withdraw' => 'سحب مرفوض',
                'total_bot_balance' => 'إجمالي رصيد البوت',
                'today_new_users' => 'المستخدمون الجدد اليوم',
                'jackpot_status' => 'حالة الجاكبوت',
                'jackpot_coin' => 'رصيد الجاكبوت',
                'search_user_mobile' => 'ابحث عن مستخدم برقم الجوال',
                'user_name' => 'اسم المستخدم',
                'user_id' => 'معرّف المستخدم',
                'mobile_length_error' => 'يجب أن يكون رقم الجوال 10 أرقام.',
                'status_off' => 'إيقاف',
                'status_on' => 'تشغيل',
                'amount_validation' => 'يرجى إدخال مبلغ صالح أكبر من 0.',
                'generic_error' => 'حدث خطأ ما. حاول مرة أخرى.',
                'mobile_required' => 'يرجى إدخال رقم الجوال',
                'user_not_found' => 'المستخدم غير موجود',
                'status_changed' => 'تم تغيير الحالة بنجاح',
                'common_help_text' => 'تواصل مع فريق {project} للحصول على المزيد من المساعدة',
                'common_cta' => 'قم بتثبيت {project} ونافس على مكافآت نقدية حقيقية.',
                'faq_title_full' => 'الأسئلة الشائعة',
                'faq_intro_title' => 'FAQ',
                'faq_q1' => 'لا أستطيع تثبيت تطبيق {project}',
                'faq_a1' => 'تأكد من السماح بالتثبيت من مصادر غير معروفة في إعدادات جهازك، ثم نزّل التطبيق مرة أخرى من الموقع الرسمي وافتح ملف APK من مجلد التنزيلات.',
                'faq_q2' => 'لا أستطيع تحديث {project}',
                'faq_a2' => 'إذا لم يتم تثبيت آخر تحديث بشكل صحيح، احذف النسخة القديمة ثم ثبّت أحدث APK مرة أخرى من الموقع الرسمي.',
                'faq_q3' => 'كيف ألعب {project}؟',
                'faq_a3' => 'يوفر {project} مباريات سريعة عبر الإنترنت مصممة للعب القائم على المهارة. انضم إلى الطاولة، وافهم قواعد الغرفة، وأدر قيمة الدخول، ونافس لاعبين حقيقيين في الوقت الفعلي.',
                'faq_q4' => 'ما هي قواعد {project}؟',
                'faq_a4' => 'الهدف هو اللعب ضمن قواعد الغرفة المحددة، واتخاذ قرارات أفضل من خصومك، وتحقيق أقوى نتيجة في المباراة أو الجولة التي انضممت إليها.',
                'about_page_subtitle' => 'تعرف أكثر على منصة {project}',
                'privacy_page_subtitle' => 'اعرف كيف يحمي {project} معلومات اللاعبين',
                'terms_page_subtitle' => 'راجع الشروط التي تنطبق على استخدام {project}',
                'refund_page_subtitle' => 'افهم سياسة الاسترداد الخاصة بـ {project}',
                'security_page_subtitle' => 'شاهد كيف يحمي {project} اللعب والحسابات',
                'contact_page_subtitle' => 'تواصل مع فريق {project} للدعم والاستفسارات التجارية',
                'security_heading' => 'الأمان',
                'security_body_1' => 'في {project}، تعد حماية المنصة أولوية قصوى حتى يتمكن اللاعبون من الاستمتاع بلعب عادل وموثوق.',
                'security_body_2' => 'تتم حماية المعاملات، ومراقبة أنظمة اللعب، كما تساعد فحوصات الأمان على مستوى الحساب في تقليل مخاطر الاحتيال.',
                'security_body_3' => 'نقوم بمراجعة ممارسات الأمان باستمرار ونطبق التحديثات كلما دعت الحاجة.',
            ],
        ];

        $language = current_language();
        $text = $translations[$language][$key] ?? ($translations['en'][$key] ?? $key);
        $params = array_merge([
            'project' => PROJECT_NAME,
            'company' => COMPANY_NAME,
            'currency' => current_currency(),
        ], $params);

        foreach ($params as $name => $value) {
            $text = str_replace('{' . $name . '}', $value, $text);
        }

        return $text;
    }
}
