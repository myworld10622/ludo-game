module.exports = (sequelize, DataTypes) => {
    const RummyPointTableMaster = sequelize.define("tbl_rummy_table_master", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        point_value: {
            type: DataTypes.DOUBLE(15,2),
        },
        boot_value: {
            type: DataTypes.DOUBLE(15,2),
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        updated_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        isDeleted: {
            type: DataTypes.TINYINT,
            allowNull: false,
            defaultValue: 0
        }
    }, {
        tableName: 'tbl_rummy_table_master',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date',
        defaultScope: {
            where: {
                isDeleted: 0
            }
        },
        scopes: {
            withDeleted: {
                where: {}
            }
        },
        indexes: [
            {
                name: 'idx_isDeleted',
                fields: ['isDeleted']
            }
        ]
    })

    RummyPointTableMaster.associate = (models) => {
        RummyPointTableMaster.hasMany(models.RummyPointTable, { foreignKey: 'boot_value', sourceKey: 'boot_value' });
    };

    return RummyPointTableMaster;
}