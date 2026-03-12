module.exports = (sequelize, DataTypes) => {
    const DailyMissionRechargeBonus = sequelize.define("tbl_daily_mission_bonus_master", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        recharge_amount: {
            type: DataTypes.INTEGER
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
        tableName: 'tbl_daily_mission_bonus_master',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    return DailyMissionRechargeBonus;
}