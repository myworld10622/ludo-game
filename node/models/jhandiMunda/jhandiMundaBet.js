module.exports = (sequelize, DataTypes) => {
    const JhandiMundaBet = sequelize.define("tbl_jhandi_munda_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        jhandi_munda_id: {
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
        tableName: 'tbl_jhandi_munda_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    JhandiMundaBet.associate = (models) => {
        JhandiMundaBet.belongsTo(models.JhandiMunda, { foreignKey: 'jhandi_munda_id' });
        JhandiMundaBet.belongsTo(models.user, { foreignKey: 'user_id', as: 'users' });
    };

    return JhandiMundaBet;
}