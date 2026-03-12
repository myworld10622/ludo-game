module.exports = (sequelize, DataTypes) => {
    const CarRouletteMap = sequelize.define("tbl_car_roulette_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        car_roulette_id: {
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
        tableName: 'tbl_car_roulette_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return CarRouletteMap;
}