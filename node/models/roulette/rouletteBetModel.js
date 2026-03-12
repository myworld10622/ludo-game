module.exports = (sequelize, DataTypes) => {
    const RouletteBet = sequelize.define("tbl_roulette_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        roulette_id: {
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
            type: DataTypes.DOUBLE
        },
        minus_winning_wallet: {
            type: DataTypes.DOUBLE
        },
        minus_bonus_wallet: {
            type: DataTypes.DOUBLE
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_roulette_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    RouletteBet.associate = (models) => {
        RouletteBet.belongsTo(models.Roulette, { foreignKey: 'roulette_id' });
    };

    return RouletteBet;

}