module.exports = (sequelize, DataTypes) => {
    const IconRouletteMap = sequelize.define("tbl_icon_roulette_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        icon_roulette_id: {
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
        tableName: 'tbl_icon_roulette_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return IconRouletteMap;
}