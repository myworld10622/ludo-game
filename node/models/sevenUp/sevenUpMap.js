module.exports = (sequelize, DataTypes) => {
    const SevenUpMap = sequelize.define("tbl_seven_up_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        seven_up_id: {
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
        tableName: 'tbl_seven_up_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return SevenUpMap;
}