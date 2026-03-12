module.exports = (sequelize, DataTypes) => {
    const RedBlackMap = sequelize.define("tbl_red_black_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        red_black_id: {
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
        tableName: 'tbl_red_black_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return RedBlackMap;
}