module.exports = (sequelize, DataTypes) => {
  const Setting = sequelize.define(
    "tbl_setting",
    {
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
      },
      min_redeem: {
        type: DataTypes.INTEGER,
        defaultValue: 0,
      },
      about_us: {
        type: DataTypes.STRING,
      },
      referral_amount: {
        type: DataTypes.INTEGER,
      },
      referral_link: {
        type: DataTypes.STRING,
      },
      referral_id: {
        type: DataTypes.STRING,
      },
      refund_policy: {
        type: DataTypes.STRING,
      },
      level_1: {
        type: DataTypes.INTEGER,
      },
      level_2: {
        type: DataTypes.INTEGER,
      },
      level_3: {
        type: DataTypes.INTEGER,
      },
      level_4: {
        type: DataTypes.INTEGER,
      },
      level_5: {
        type: DataTypes.INTEGER,
      },
      app_version: {
        type: DataTypes.STRING,
      },
      game_for_private: {
        type: DataTypes.STRING,
      },
      joining_amount: {
        type: DataTypes.INTEGER,
      },
      admin_coin: {
        type: DataTypes.FLOAT,
      },
      distribute_precent: {
        type: DataTypes.FLOAT,
      },
      admin_commission: {
        type: DataTypes.DOUBLE,
      },
      game_for_private: {
        type: DataTypes.STRING,
      },
      bonus: {
        type: DataTypes.TINYINT,
      },
      bonus_amount: {
        type: DataTypes.INTEGER,
      },
      upi_id: {
        type: DataTypes.STRING,
      },
      upi_merchant_id: {
        type: DataTypes.STRING,
      },
      upi_secret_key: {
        type: DataTypes.STRING,
      },
      whats_no: {
        type: DataTypes.STRING,
      },
      app_version: {
        type: DataTypes.STRING,
      },
      privacy_policy: {
        type: DataTypes.STRING,
      },
      help_support: {
        type: DataTypes.STRING,
      },
      terms: {
        type: DataTypes.STRING,
      },
      payment_gateway: {
        type: DataTypes.TINYINT,
      },
      neokred_client_secret: {
        type: DataTypes.STRING,
      },
      neokred_project_id: {
        type: DataTypes.STRING,
      },
      symbol: {
        type: DataTypes.TINYINT,
      },
      razor_api_key: {
        type: DataTypes.STRING,
      },
      razor_secret_key: {
        type: DataTypes.STRING,
      },
      cashfree_client_id: {
        type: DataTypes.STRING,
      },
      cashfree_client_secret: {
        type: DataTypes.STRING,
      },
      cashfree_stage: {
        type: DataTypes.STRING,
      },
      paytm_mercent_id: {
        type: DataTypes.STRING,
      },
      paytm_mercent_key: {
        type: DataTypes.STRING,
      },
      share_text: {
        type: DataTypes.STRING,
      },
      bank_detail_field: {
        type: DataTypes.STRING,
      },
      adhar_card_field: {
        type: DataTypes.STRING,
      },
      upi_field: {
        type: DataTypes.STRING,
      },
      app_message: {
        type: DataTypes.TEXT,
      },
      app_url: {
        type: DataTypes.STRING,
      },
      logo: {
        type: DataTypes.STRING,
      },
      payumoney_key: {
        type: DataTypes.STRING,
      },
      payumoney_salt: {
        type: DataTypes.STRING,
      },
      contact_us: {
        type: DataTypes.STRING,
      },
      aviator_bucket: {
        type: DataTypes.FLOAT,
      },
      dragon_tiger_random: {
        type: DataTypes.BOOLEAN,
        default: false,
      },
      ander_bahar_withdraw: {
        type: DataTypes.INTEGER,
      },
      color_prediction_random: {
        type: DataTypes.INTEGER,
      },
      color_prediction_1_min_random: {
        type: DataTypes.INTEGER,
      },
      color_prediction_3_min_random: {
        type: DataTypes.INTEGER,
      },
      color_prediction_5_min_random: {
        type: DataTypes.INTEGER,
      },
      tripple_fun_random: {
        type: DataTypes.INTEGER,
      },
      roulette_random: {
        type: DataTypes.INTEGER,
      },
      teen_patti_random: {
        type: DataTypes.INTEGER,
        defaultValue: 1,
        comment: "1=Random, 2=Optimization",
      },
      point_rummy_random: {
        type: DataTypes.INTEGER,
        defaultValue: 1,
        comment: "1=Random, 2=Optimization",
      },
      up_down_random: {
        type: DataTypes.INTEGER,
      },
      animal_roulette_random: {
        type: DataTypes.INTEGER,
      },
      car_roulette_random: {
        type: DataTypes.INTEGER,
      },
      ander_bahar_random: {
        type: DataTypes.INTEGER,
      },
      red_black_random: {
        type: DataTypes.INTEGER,
      },
      jackpot_random: {
        type: DataTypes.INTEGER,
      },
      head_tail_random: {
        type: DataTypes.INTEGER,
      },
      bacarate_random: {
          type: DataTypes.INTEGER
      },
      jhandi_munda_random: {
          type: DataTypes.INTEGER
      },
      daily_rebate_income: {
        type: DataTypes.INTEGER,
        defaultValue: 0,
      },
      robot_rummy: {
        type: DataTypes.TINYINT,
      },
      seven_up_min_bet: {
        type: DataTypes.INTEGER,
      },
      head_tails_min_bet: {
        type: DataTypes.INTEGER,
      },
      dragon_tiger_min_bet: {
        type: DataTypes.INTEGER,
      },
      animal_roullette_min_bet: {
        type: DataTypes.INTEGER,
      },
      car_roullette_min_bet: {
        type: DataTypes.INTEGER,
      },
      ander_bahar_min_bet: {
        type: DataTypes.INTEGER,
      },
      red_and_black_min_bet: {
        type: DataTypes.INTEGER,
      },
      jackpot_coin: {
        type: DataTypes.INTEGER,
      },
      jackpot_status: {
        type: DataTypes.TINYINT,
      },
    },
    { tableName: "tbl_setting", timestamps: false }
  );

  return Setting;
};
