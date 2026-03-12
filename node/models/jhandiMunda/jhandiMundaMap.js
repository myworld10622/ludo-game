module.exports = (sequelize, DataTypes) => {
    const JhandiMunMap = sequelize.define("tbl_jhandi_munda_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        jhandi_munda_id: {
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
        tableName: 'tbl_jhandi_munda_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    return JhandiMunMap;
}