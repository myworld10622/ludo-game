module.exports = (sequelize, DataTypes) => {
    const SlotGameBet = sequelize.define("tbl_slot_game_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true,
            allowNull: false
        },
        slot_game_id: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        user_id: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        bet: {
            type: DataTypes.TINYINT,
            defaultValue: 0
        },
        amount: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        winning_amount: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        user_amount: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        comission_amount: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        minus_unutilized_wallet: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        minus_winning_wallet: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        minus_bonus_wallet: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_slot_game_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false // No automatic updates for updated_at
    });

    SlotGameBet.associate = (models) => {
        SlotGameBet.belongsTo(models.SlotGame, { foreignKey: 'slot_game_id' });
    };

    return SlotGameBet;
};
