module.exports = (sequelize, DataTypes) => {
    const BetIncomeMaster = sequelize.define("tbl_bet_income_master", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        bonus: {
            type: DataTypes.FLOAT
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        updated_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
    }, {
        tableName: 'tbl_bet_income_master',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    return BetIncomeMaster;
}