module.exports = (sequelize, DataTypes) => {
    const ColorPredictionBet3Min = sequelize.define("tbl_color_prediction_3_min_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        color_prediction_id: {
            type: DataTypes.INTEGER,
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        bet: {
            type: DataTypes.TINYINT,
            comment: "GREEN - 10, VIOLET - 11, RED - 12"
        },
        amount: {
            type: DataTypes.INTEGER,
        },
        winning_amount: {
            type: DataTypes.FLOAT,
        },
        user_amount: {
            type: DataTypes.FLOAT
        },
        comission_amount: {
            type: DataTypes.FLOAT
        },
        minus_unutilized_wallet: {
            type: DataTypes.FLOAT
        },
        minus_winning_wallet: {
            type: DataTypes.FLOAT
        },
        minus_bonus_wallet: {
            type: DataTypes.FLOAT
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_color_prediction_3_min_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    ColorPredictionBet3Min.associate = (models) => {
        ColorPredictionBet3Min.belongsTo(models.ColorPrediction3Min, { foreignKey: 'color_prediction_id' });
    };

    return ColorPredictionBet3Min;

}