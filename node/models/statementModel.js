module.exports = (sequelize, DataTypes) => {

    const Statement = sequelize.define("tbl_statement", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        amount: {
            type: DataTypes.FLOAT,
        }
        ,
        admin_commission: {
            type: DataTypes.FLOAT,
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        source: {
            type: DataTypes.TEXT,
        },
        source_id: {
            type: DataTypes.INTEGER,
        },
        current_wallet: {
            type: DataTypes.FLOAT
        }
        ,
        admin_coin: {
            type: DataTypes.FLOAT
        }
        ,
        added_date: {
            type: DataTypes.DATE
        },
    }, {
        tableName: 'tbl_statement',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    return Statement

}