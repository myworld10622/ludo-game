module.exports = (sequelize, DataTypes) => {
    const DragonTigerBet = sequelize.define("tbl_dragon_tiger_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        dragon_tiger_id: {
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
        tableName: 'tbl_dragon_tiger_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    // DragonTigerBet.belongsTo(DragonTiger, { foreignKey: 'dragon_tiger_id' });
    DragonTigerBet.associate = (models) => {
        DragonTigerBet.belongsTo(models.DragonTiger, { foreignKey: 'dragon_tiger_id' });
    };

    return DragonTigerBet;

}