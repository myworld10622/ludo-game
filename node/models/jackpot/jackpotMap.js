module.exports = (sequelize, DataTypes) => {
    const JackpotMap = sequelize.define("tbl_jackpot_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        jackpot_id: {
            type: DataTypes.INTEGER,
        },
        card: {
            type: DataTypes.STRING,
            allowNull: true,
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_jackpot_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return JackpotMap;
}