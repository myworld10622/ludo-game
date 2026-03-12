module.exports = (sequelize, DataTypes) => {
    const SlotGameMap = sequelize.define("tbl_slot_game_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        slot_game_id: {
            type: DataTypes.INTEGER,
        },
        card: {
            type: DataTypes.STRING,
            defaultValue: ''
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_slot_game_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return SlotGameMap;
}