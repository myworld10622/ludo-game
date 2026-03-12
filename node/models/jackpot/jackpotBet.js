module.exports = (sequelize, DataTypes) => {
    const JackpotBet = sequelize.define("tbl_jackpot_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        jackpot_id: {
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
        tableName: 'tbl_jackpot_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    JackpotBet.associate = (models) => {
        JackpotBet.belongsTo(models.Jackpot, { foreignKey: 'jackpot_id' });
        JackpotBet.belongsTo(models.user, { foreignKey: 'user_id', as: 'users' });
    };

    return JackpotBet;

}