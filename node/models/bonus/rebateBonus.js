module.exports = (sequelize, DataTypes) => {
    const RebateBonus = sequelize.define("tbl_rebate_income", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        user_id: {
            type: DataTypes.INTEGER
        },
        bet_amount: {
            type: DataTypes.FLOAT
        },
        percentage: {
            type: DataTypes.INTEGER
        },
        coin: {
            type: DataTypes.FLOAT
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
        tableName: 'tbl_rebate_income',
        timestamps: false,
    })

    return RebateBonus;
}