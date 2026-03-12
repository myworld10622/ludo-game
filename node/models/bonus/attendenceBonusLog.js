module.exports = (sequelize, DataTypes) => {
    const AttendenceBonusLog = sequelize.define("tbl_attendance_bonus_log", {
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
        day: {
            type: DataTypes.INTEGER
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
        }
    }, {
        tableName: 'tbl_attendance_bonus_log',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    return AttendenceBonusLog;
}