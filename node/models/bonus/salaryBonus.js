module.exports = (sequelize, DataTypes) => {
    const SalaryBonus = sequelize.define("tbl_daily_salary_bonus_master", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        active_users: {
            type: DataTypes.INTEGER,
        },
        max_active_users: {
            type: DataTypes.INTEGER,
        },
        daily_salary_bonus: {
            type: DataTypes.FLOAT
        },
        max_recharge: {
            type: DataTypes.INTEGER,
        },
        max_bet: {
            type: DataTypes.INTEGER,
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
        tableName: 'tbl_daily_salary_bonus_master',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    return SalaryBonus;
}