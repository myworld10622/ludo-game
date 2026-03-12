module.exports = (sequelize, DataTypes) => {
    const RouletteMap = sequelize.define("tbl_roulette_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        roulette_id : {
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
        tableName: 'tbl_roulette_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return RouletteMap;
}