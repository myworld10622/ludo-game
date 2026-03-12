module.exports = (sequelize, DataTypes) => {
    const BetIncomeLog = sequelize.define("tbl_bet_income_log", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        bet_user_id: {
            type: DataTypes.INTEGER
        },
        to_user_id: {
            type: DataTypes.INTEGER
        },
        bet_amount: {
            type: DataTypes.FLOAT
        },
        bonus: {
            type: DataTypes.FLOAT
        },
        level: {
            type: DataTypes.INTEGER
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
        },
        updated_date: {
            type: DataTypes.DATE,
            allowNull: false
        }
    }, {
        tableName: 'tbl_bet_income_log',
        timestamps: false,
    })

    return BetIncomeLog;
}