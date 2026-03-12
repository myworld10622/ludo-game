module.exports = (sequelize, DataTypes) => {
    const BacarratMap = sequelize.define("tbl_baccarat_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        baccarat_id: {
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
        tableName: 'tbl_baccarat_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return BacarratMap;
}