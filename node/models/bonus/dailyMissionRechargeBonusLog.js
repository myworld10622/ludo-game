module.exports = (sequelize, DataTypes) => {
    const DailyMissionRechargeBonusLog = sequelize.define("tbl_daily_mission_recharge_log", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        user_id: {
            type: DataTypes.INTEGER
        },
        recharge: {
            type: DataTypes.FLOAT
        },
        coin: {
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
        tableName: 'tbl_daily_mission_recharge_log',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    return DailyMissionRechargeBonusLog;
}