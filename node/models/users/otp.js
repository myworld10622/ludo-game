module.exports = (sequelize, DataTypes) => {
    const Otp = sequelize.define("tbl_otp", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        otp: {
            type: DataTypes.INTEGER,
        },
        mobile: {
            type: DataTypes.STRING(12),
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_otp',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })
    return Otp;
}