module.exports = (sequelize, DataTypes) => {
    const AnderBaharMap = sequelize.define("tbl_ander_baher_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        ander_bahar_id: {
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
        tableName: 'tbl_ander_baher_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
        indexes: [
            {
                name: 'idx_ander_bahar_id',
                fields: ['ander_bahar_id']
            }
        ]
    })

    return AnderBaharMap;
}