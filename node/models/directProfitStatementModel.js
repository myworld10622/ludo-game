module.exports = (sequelize, DataTypes) => {

    const DirectProfitStatement = sequelize.define("tbl_direct_admin_profit_statement", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },

        admin_commission: {
            type: DataTypes.FLOAT,
        },
        source: {
            type: DataTypes.TEXT,
        },
        source_id: {
            type: DataTypes.INTEGER,
        },
        admin_coin: {
            type: DataTypes.FLOAT
        },
        added_date: {
            type: DataTypes.DATE
        },
    }, {
        tableName: 'tbl_direct_admin_profit_statement',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    return DirectProfitStatement

}