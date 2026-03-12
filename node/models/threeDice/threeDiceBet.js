module.exports = (sequelize, DataTypes) => {
    const threeDiceBet = sequelize.define("tbl_three_dice_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        three_dice_id: {
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
            type: DataTypes.FLOAT
        },
        comission_amount: {
            type: DataTypes.FLOAT
        },
        minus_unutilized_wallet: {
            type: DataTypes.FLOAT,
            defaultValue: 0
        },
        minus_winning_wallet: {
            type: DataTypes.FLOAT,
            defaultValue: 0
        },
        minus_bonus_wallet: {
            type: DataTypes.FLOAT,
            defaultValue: 0
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_three_dice_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    threeDiceBet.associate = (models) => {
        threeDiceBet.belongsTo(models.ThreeDice, { foreignKey: 'three_dice_id' });
        threeDiceBet.belongsTo(models.user, { foreignKey: 'user_id', as: 'users' });
    };

    return threeDiceBet;
}