module.exports = (sequelize, DataTypes) => {
    const ColorPredictionMap3Min = sequelize.define("tbl_color_prediction_3_min_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        color_prediction_id: {
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
        tableName: 'tbl_color_prediction_3_min_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return ColorPredictionMap3Min;
}