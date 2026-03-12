module.exports = (sequelize, DataTypes) => {
    const threeDiceMap = sequelize.define("tbl_three_dice_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        three_dice_id: {
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
        tableName: 'tbl_three_dice_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return threeDiceMap;
}