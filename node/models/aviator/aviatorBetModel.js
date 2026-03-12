module.exports = (sequelize, DataTypes) => {

    const AviatorBet = sequelize.define("tbl_aviator_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        bet: {
            type: DataTypes.INTEGER,
        },
        bet_status: {
            type: DataTypes.INTEGER,
        },
        aviator_id: {
            type: DataTypes.INTEGER,
        },
        user_id: {
            type: DataTypes.INTEGER
        },
        user_amount: {
            type: DataTypes.FLOAT,
            allowNull: true
        },
        comission_amount: {
            type: DataTypes.FLOAT,
            allowNull: true
        },
        winning_amount: {
            type: DataTypes.TEXT,
            allowNull: true
        },
        amount: {
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
        tableName: 'tbl_aviator_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    return AviatorBet

}