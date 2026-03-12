<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to false, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') or define('SHOW_DEBUG_BACKTRACE', false);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE') or define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') or define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE') or define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE') or define('DIR_WRITE_MODE', 0755);

/******************API ERROR CODES **************************/
define('HTTP_OK', 200);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_BLANK', 407);
define('HTTP_INVALID', 411);
define('HTTP_NOT_ACCEPTABLE', 406);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ') or define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE') or define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE') or define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE') or define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE') or define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE') or define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT') or define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT') or define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS') or define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR') or define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG') or define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE') or define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS') or define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') or define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT') or define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE') or define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN') or define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX') or define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code
define('PROJECT_NAME', 'Lets Card');
define('COMPANY_NAME', 'ASAG Androapps Technology Pvt Ltd.');
define('SUPERADMIN', 0);
define('DISTRIBUTOR', 3);
define('SUBADMIN', 1);
define('ROLEAGENT', 2);
define('KEY', '2018');
define('URL_ENCRYPT_KEY', 'Housie432423');
define('URL_ENCRYPT_IV', 'DHSKJHD^*$#^IDK*');
define('CIPHER', 'AES-128-ECB');

// RAZOR PAY
// define('API_KEY','rzp_live_EpsOYgHl8D1rbt');         //test
// define('API_SECRET','dP51DUiV9KIU1tzSQFFot43z');

define('API_KEY', 'rzp_live_TzyvQmD1j6o3jP');     //live
define('API_SECRET', 'Qz3zWugj0fTckHFBjd8IjnVO');

define('CLIENT_TEST_URL', 'https://test.cashfree.com'); //test
define('CLIENT_LIVE_URL', 'https://api.cashfree.com'); //live

define('PAYTM_TEST_URL', 'https://securegw-stage.paytm.in'); //test
define('PAYTM_LIVE_URL', 'https://securegw.paytm.in'); //live

define('SMS_API_KEY', 'a7b3595b-9204-11e7-94da-0200cd936042');

define('DEFAULT_OTP', '9999');
define('NO_CHAAL_PERCENT', 25); //DROP PERCENT IF NO CHAAL
define('CHAAL_PERCENT', 50); //DROP PERCENT IF CHAAL
define('MAX_POINTS', 80); //DROP PERCENT IF CHAAL

define('MAX_POINT', 101); //RummyPool Points

define('RUMMY_CARDS', 13); //RummyPool Cards
define('RUMMY_CACHETA_CARDS', 10); //RummyPool Cards


define('ANDER', 0);
define('BAHAR', 1);

define('DOWN', 0);
define('UP', 1);

define('UP_DOWN_MULTIPLY', 2);
define('UP_DOWN_TIE_MULTIPLY', 5);

define('DRAGON', 0);
define('TIGER', 1);
define('TIE', 2);

define('HEAD', 0);
define('TAIL', 1);

define('DRAGON_MULTIPLY', 2);
// define('TIGER_MULTIPLY', 2);
define('TIE_MULTIPLY', 9);

define('DRAGON_TIME_FOR_BET', 10);
define('DRAGON_TIME_FOR_START_NEW_GAME', 5);

define('HIGH_CARD', 1);
define('PAIR', 2);
define('COLOR', 3);
define('SEQUENCE', 4);
define('PURE_SEQUENCE', 5);
define('SET', 6);

define('HIGH_CARD_MULTIPLY', 3);
define('PAIR_MULTIPLY', 4);
define('COLOR_MULTIPLY', 5);
define('SEQUENCE_MULTIPLY', 6);
define('PURE_SEQUENCE_MULTIPLY', 10);
define('SET_MULTIPLY', 0.2);

define('TOYOTA', 1);
define('MAHINDRA', 2);
define('AUDI', 3);
define('BMW', 4);
define('MERCEDES', 5);
define('PORSCHE', 6);
define('LAMBORGHINI', 7);
define('FERRARI', 8);

define('TOYOTA_MULTIPLY', 5);
define('MAHINDRA_MULTIPLY', 5);
define('AUDI_MULTIPLY', 5);
define('BMW_MULTIPLY', 5);
define('MERCEDES_MULTIPLY', 10);
define('PORSCHE_MULTIPLY', 15);
define('LAMBORGHINI_MULTIPLY', 25);
define('FERRARI_MULTIPLY', 40);

// define('TIGER', 1);
define('SNAKE', 2);
define('SHARK', 3);
define('FOX', 4);
define('CHEETAH', 5);
define('BEAR', 6);
define('WHALE', 7);
define('LION', 8);

define('TIGER_MULTIPLY', 5);
define('SNAKE_MULTIPLY', 5);
define('SHARK_MULTIPLY', 5);
define('FOX_MULTIPLY', 5);
define('CHEETAH_MULTIPLY', 10);
define('BEAR_MULTIPLY', 15);
define('WHALE_MULTIPLY', 25);
define('LION_MULTIPLY', 40);

define('GREEN', 10);
define('VIOLET', 11);
define('RED', 12);

define('LEAST', 17);
define('RANDOM', 18);
define('BIG', 15);
define('SMALL', 16);

define('COLOR_1MIN_TIME_FOR_BET', 63);
define('COLOR_1MIN_TIME_FOR_START_NEW_GAME', 5);

define('COLOR_3MIN_TIME_FOR_BET', 183);
define('COLOR_3MIN_TIME_FOR_START_NEW_GAME', 5);

define('COLOR_5MIN_TIME_FOR_BET', 303);
define('COLOR_5MIN_TIME_FOR_START_NEW_GAME', 5);

define('NUMBER_MULTIPLE', 9);
define('VIOLET_MULTIPLE', 4.5);
define('GREEN_RED_HALF_MULTIPLE', 1.5);
define('GREEN_RED_MULTIPLE', 2);
define('SMALL_BIG_MULTIPLE', 2);


define('RB_RED', 1);
define('RB_BLACK', 2);
define('RB_PAIR', 3);
define('RB_COLOR', 4);
define('RB_SEQUENCE', 5);
define('RB_PURE_SEQUENCE', 6);
define('RB_SET', 7);

define('RB_RED_MULTIPLE', 2);
define('RB_BLACK_MULTIPLE', 2);
define('RB_PAIR_MULTIPLE', 3.5);
define('RB_COLOR_MULTIPLE', 10);
define('RB_SEQUENCE_MULTIPLE', 10);
define('RB_PURE_SEQUENCE_MULTIPLE', 15);
define('RB_SET_MULTIPLE', 10);

define('HEART', 1);
define('SPADE', 2);
define('DIAMOND', 3);
define('CLUB', 4);
define('FACE', 5);
define('FLAG', 6);

define('ONE_DICE', 0);
define('TWO_DICE', 3);
define('THREE_DICE', 5);
define('FOUR_DICE', 10);
define('FIVE_DICE', 20);
define('SIX_DICE', 100);

define('PLAYER', 0);
define('BANKER', 1);
// define('TIE', 2);
define('PLAYER_PAIR', 3);
define('BANKER_PAIR', 4);

define('PLAYER_MULTIPLE', 2);
define('BANKER_MULTIPLE', 1.95);
define('TIE_MULTIPLE', 8);
define('PLAYER_PAIR_MULTIPLE', 11);
define('BANKER_PAIR_MULTIPLE', 11);

// ROULETTE

define('R_TWELFTH_1ST', 37);
define('R_TWELFTH_2ND', 38);
define('R_TWELFTH_3RD', 39);
define('R_EIGHTEENTH_1ST', 40);
define('R_EIGHTEENTH_2ND', 41);
define('R_EVEN', 42);
define('R_ODD', 43);
define('R_RED', 44);
define('R_BLACK', 45);
define('R_ROW_1', 46);
define('R_ROW_2', 47);
define('R_ROW_3', 48);
define('R_1_2', 49);
define('R_2_3', 50);
define('R_4_5', 51);
define('R_5_6', 52);
define('R_7_8', 53);
define('R_8_9', 54);
define('R_10_11', 55);
define('R_11_12', 56);
define('R_13_14', 57);
define('R_14_15', 58);
define('R_16_17', 59);
define('R_17_18', 60);
define('R_19_20', 61);
define('R_20_21', 62);
define('R_22_23', 63);
define('R_23_24', 64);
define('R_25_26', 65);
define('R_26_27', 66);
define('R_28_29', 67);
define('R_29_30', 68);
define('R_31_32', 69);
define('R_32_33', 70);
define('R_34_35', 71);
define('R_35_36', 72);
define('R_0_1', 73);
define('R_0_2', 74);
define('R_0_3', 75);
define('R_1_4', 76);
define('R_2_5', 77);
define('R_3_6', 78);
define('R_4_7', 79);
define('R_5_8', 80);
define('R_6_9', 81);
define('R_7_10', 82);
define('R_8_11', 83);
define('R_9_12', 84);
define('R_10_13', 85);
define('R_11_14', 86);
define('R_12_15', 87);
define('R_13_16', 88);
define('R_14_17', 89);
define('R_15_18', 90);
define('R_16_19', 91);
define('R_17_20', 92);
define('R_18_21', 93);
define('R_19_22', 94);
define('R_20_23', 95);
define('R_21_24', 96);
define('R_22_25', 97);
define('R_23_26', 98);
define('R_24_27', 99);
define('R_25_28', 100);
define('R_26_29', 101);
define('R_27_30', 102);
define('R_28_31', 103);
define('R_29_32', 104);
define('R_30_33', 105);
define('R_31_34', 106);
define('R_32_35', 107);
define('R_33_36', 108);
define('R_0_1_2', 109);
define('R_0_2_3', 110);
define('R_1_2_4_5', 111);
define('R_2_3_5_6', 112);
define('R_4_5_7_8', 113);
define('R_5_6_8_9', 114);
define('R_7_8_10_11', 115);
define('R_8_9_11_12', 116);
define('R_10_11_13_14', 117);
define('R_11_12_14_15', 118);
define('R_13_14_16_17', 119);
define('R_14_15_17_18', 120);
define('R_16_17_19_20', 121);
define('R_17_18_20_21', 122);
define('R_19_20_22_23', 123);
define('R_20_21_23_24', 124);
define('R_22_23_25_26', 125);
define('R_23_24_26_27', 126);
define('R_25_26_28_29', 127);
define('R_26_27_29_30', 128);
define('R_28_29_31_32', 129);
define('R_29_30_32_33', 130);
define('R_31_32_34_35', 131);
define('R_32_33_35_36', 132);


define('R_NUMBER_MULTIPLE', 36);
define('R_COLOR_MULTIPLE', 2);
define('R_ODD_EVEN_MULTIPLE', 2);
define('R_TWELFTH_MULTIPLE', 3);
define('R_EIGHTEENTH_MULTIPLE', 2);
define('R_ROW_MULTIPLE', 3);
define('R_TWO_SPLIT_MULTIPLE', 18);
define('R_FOUR_SPLIT_MULTIPLE', 9);

// Target
define('TARGET_MULTIPLE', 9);

define('TARGET_TIME_FOR_BET', 18);
define('TARGET_TIME_FOR_START_NEW_GAME', 20);


// Ander Bahar Plus
define('JACK', 11);
define('QUEEN', 12);
define('KING', 13);
define('ACE', 14);

define('AB_RED', 15);
define('AB_BLACK', 16);

define('AB_HEART', 17);
define('AB_SPADE', 18);
define('AB_DIAMOND', 19);
define('AB_CLUB', 20);

define('A_6', 21);
define('AB_SEVEN', 22);
define('AB_8_K', 23);

define('ABPLUS_NUMBER_MULTIPLY', 12);
define('ABPLUS_WIN_MULTIPLY', 2);
define('ABPLUS_COLOR_MULTIPLY', 2);
define('ABPLUS_SHAPE_MULTIPLY', 4);
define('ABPLUS_UP_DOWN_MULTIPLY', 2);

define('GOLDEN_ODD', 11);
define('GOLDEN_EVEN', 12);
define('GOLDEN_ODD_EVEN_MULTIPLY', 2);

define('APP_URL', './');
define('BANNER_URL', 'uploads/banner/');
define('LOGO', 'uploads/logo/');
define('QR_IMAGE', 'uploads/logo/');
define('IMAGE_URL', 'uploads/images/');

// define('SUBADMIN_MANAGEMENT', true);
define('USER_MANAGEMENT', true);
define('SUB_ADMIN_MANAGEMENT', true);
define('AGENT', true);
define('USER_CATEGORY', true);
define('WITHDRAWL_DASHBOARD', true);
define('CHIPS_MANAGEMENT', true);
define('GIFT_MANAGEMENT', true);
define('PURCHASE_HISTORY', true);
define('LEAD_BOARD', true);
define('NOTIFICATION', true);
define('IMAGE_NOTIFICATION', true);
define('WELCOME_BONUS', true);
define('SETTING', true);
define('REEDEM_MANAGEMENT', true);
define('WITHDRAWAL_LOG', true);
define('COMISSION', true);
define('BANNER', true);
define('APPBANNER', true);
define('ADD_CASH', true);
define('REPORT', true);
define('INCOME_DEPOSIT_BONUS', false);
define('INCOME_BET_BONUS', false);
define('INCOME_DAILY_SALARY_BONUS', false);
define('INCOME_DAILY_ATTENDANCE_BONUS', false);
define('TOURNAMENT_MANAGEMENT', false);
define('TOURNAMENT_TYPES', false);
define('TOURNAMENT_MASTER', false);

define('TEENPATTI', true);
define('POINT_RUMMY', true);
define('RUMMY_POOL', true);
define('RUMMY_DEAL', true);
define('ANDER_BAHAR', true);
define('ANDER_BAHAR_PLUS', true);
define('DRAGON_TIGER', true);
define('AVIATOR', true);
define('AVIATOR_VERTICAL', true);
define('LOTTERY', true);
define('TARGET', true);
define('SEVEN_UP_DOWN', true);
define('SLOT', true);
define('CAR_ROULETTE', true);
define('COLOR_PREDICTION', true);
define('COLOR_PREDICTION_VERTICAL', true);
define('JACKPOT', true);
define('ANIMAL_ROULETTE', true);
define('LUDO', true);
define('LUDO_LOCAL', true);
define('LUDO_COMPUTER', true);
define('BACCARAT', true);
define('POKER', true);
define('RED_VS_BLACK', true);
define('HEAD_TAILS', true);
define('JHANDI_MUNDA', true);
define('ROULETTE', true);
define('RUMMY_TOURNAMENT', true);
define('ICON_ROULETTE', true);

define('TEENPATTI_LOG', 1);
define('POINT_RUMMY_LOG', 2);
define('RUMMY_POOL_LOG', 3);
define('RUMMY_DEAL_LOG', 4);
define('ANDER_BAHAR_LOG', 5);
define('DRAGON_TIGER_LOG', 6);
define('SEVEN_UP_DOWN_LOG', 7);
define('JACKPOT_LOG', 8);
define('CAR_ROULETTE_LOG', 9);
define('COLOR_PREDICTION_LOG', 10);
define('ANIMAL_ROULETTE_LOG', 11);
define('LUDO_LOG', 12);
define('LUDO_LOCAL_LOG', 13);
define('LUDO_COMPUTER_LOG', 14);
define('BACCARAT_LOG', 15);
define('POKER_LOG', 16);
define('RED_VS_BLACK_LOG', 17);
define('HEAD_TAILS_LOG', 18);
define('JHANDI_MUNDA_LOG', 19);
define('ROULETTE_LOG', 20);
// FCM Notification
define('SERVER_KEY', 'AAAA2bfIo_E:APA91bGFMoeE0wGoYy6q95ImATZ8KofZjx0yXi6ARfBkFzyHJ23Vi6tbV-gJ0kSbL_dzshsR_oVSomIsYP60RJAxzu3QeprGe9H62vEIpzBmI9IH5-6b5W-AFE2DjxiRjN8-2EoU7o03');

define('OTP_API_KEY', '');

define('EASY_PE_PAY_ID', '1104121214144057');
define('EASY_PE_SALT', '72900b73ccb34b38');
define('EASY_PE_PREFIX', 'Letscard');

define('XSOLLA_MERCHANT_ID', '467820');
define('XSOLLA_API_KEY', '1697471c65856ea632a43271ef0614057735378a');
define('XSOLLA_PROJECT_ID', '250317');
define('XSOLLA_USER_ID', '300011bf-f05d-40d3-8750-1a9e9fdc6389');
define('XSOLLA_CLIENT_ID', '7967');
define('XSOLLA_CLIENT_SECRET_KEY', 'HdJKFdPSMr8lNN9ABgTG9OsQWMSgMPmf');

define('CONTACT_DETAILS', '<span style="color:red">+91 9969627262/ +91 8591925638<br> or <br>Skype- abhian.androapps<br> or <br>Email at info@androappstech.com<br></span>');

define('REGISTER_BONUS', 'Registration Bonus');
define('REFERRAL_BONUS', 'Referral Bonus');
define('CP', 'Color Prediction');
define('CP1', 'Color Prediction1Min');
define('CP3', 'Color Prediction3Min');
define('CP5', 'Color Prediction5Min');
define('AB', 'Andar Bahar');
define('ABP', 'Andar Bahar Plus');
define('AR', 'Animal Roulette');
define('CR', 'Car Roulette');
define('DT', 'Dragon Tiger');
define('BT', 'Baccarat');
define('GT', 'Golden Wheel');
define('SU', 'Seven Up Down');
define('RT', 'Roulette');
define('RB', 'Red vs Black');
define('JM', 'Jhandi Munda');
define('JT', 'JackPot 3 Patti');
define('HT', 'Head & Tail');
define('TP', 'Teenpatti');
define('LT', 'Lottery');
define('LD', 'Ludo');
define('PR', 'Poker');
define('RMY', 'Rummy Point');
define('RMY_DEAL', 'Rummy Deal');
define('RMY_POOL', 'Rummy Pool');
define('RMYCACHETA', 'Rummy Cacheta');
define('ADDED_ADMIN', 'Admin Added/Deduction');
define('ADMIN_AGENT_TRANSFER', 'Admin Added/Deduction To Agent');
define('AGENT_ADDED', 'Agent Added/Deduction');
define('DEPOSIT', 'Add Cash');
define('WITHDRAW', 'Withdraw');
define('WITHDRAW_REJECTED', 'Withdraw Rejected');
define('SPIN', 'Spin');
define('SL', 'Slot');
define('DAILY_REWARD', 'Daily Reward');