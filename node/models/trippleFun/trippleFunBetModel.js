module.exports = (sequelize, DataTypes) => {
  const TrippleFunBet = sequelize.define(
    "tbl_tripple_fun_bet",
    {
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
      },
      tripple_fun_id: {
        type: DataTypes.INTEGER,
      },
      user_id: {
        type: DataTypes.INTEGER,
      },
      bet: {
        type: DataTypes.TINYINT,
      },
      amount: {
        type: DataTypes.INTEGER,
      },
      winning_amount: {
        type: DataTypes.FLOAT,
      },
      user_amount: {
        type: DataTypes.FLOAT,
      },
      comission_amount: {
        type: DataTypes.FLOAT,
      },
      minus_unutilized_wallet: {
        type: DataTypes.FLOAT,
      },
      minus_winning_wallet: {
        type: DataTypes.FLOAT,
      },
      minus_bonus_wallet: {
        type: DataTypes.FLOAT,
      },
      added_date: {
        type: DataTypes.DATE,
        allowNull: false,
        defaultValue: DataTypes.NOW,
      },
    },
    {
      tableName: "tbl_tripple_fun_bet",
      timestamps: true,
      createdAt: "added_date",
      updatedAt: false,
    }
  );

  TrippleFunBet.associate = (models) => {
    TrippleFunBet.belongsTo(models.TrippleFun, {
      foreignKey: "tripple_fun_id",
    });
  };

  return TrippleFunBet;
};
