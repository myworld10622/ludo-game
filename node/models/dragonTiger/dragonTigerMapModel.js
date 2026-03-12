module.exports = (sequelize, DataTypes) => {
    const DragonTigerMap = sequelize.define("tbl_dragon_tiger_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        dragon_tiger_id: {
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
        tableName: 'tbl_dragon_tiger_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return DragonTigerMap;
}