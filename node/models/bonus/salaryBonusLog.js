module.exports = (sequelize, DataTypes) => {
    const SalaryBonusLog = sequelize.define("tbl_daily_salary_bonus", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        user_id: {
            type: DataTypes.INTEGER
        },
        to_user_id: {
            type: DataTypes.INTEGER
        },
        bet_amount: {
            type: DataTypes.FLOAT
        },
        recharge_amount: {
            type: DataTypes.FLOAT
        },
        bonus: {
            type: DataTypes.FLOAT
        },
        level: {
            type: DataTypes.INTEGER
        },
        active_users: {
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
        tableName: 'tbl_daily_salary_bonus',
        timestamps: false,
    })

    return SalaryBonusLog;
}