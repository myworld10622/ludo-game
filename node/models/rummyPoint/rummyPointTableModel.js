module.exports = (sequelize, DataTypes) => {
    const RummyPointTable = sequelize.define("tbl_rummy_table", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        boot_value: {
            type: DataTypes.BIGINT,
        },
        no_of_players: {
            type: DataTypes.TINYINT,
        },
        maximum_blind: {
            type: DataTypes.INTEGER,
        },
        chaal_limit: {
            type: DataTypes.BIGINT,
        },
        pot_limit: {
            type: DataTypes.BIGINT,
        },
        private: {
            type: DataTypes.TINYINT
        },
        code: {
            type: DataTypes.STRING(50)
        },
        start_seat_no: {
            type: DataTypes.TINYINT,
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
        tableName: 'tbl_rummy_table',
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

    RummyPointTable.associate = (models) => {
        RummyPointTable.belongsTo(models.RummyPointTableMaster, {
            foreignKey: 'boot_value',
            targetKey: 'boot_value'
        });
        RummyPointTable.hasMany(models.user, { foreignKey: 'rummy_table_id', sourceKey: 'id' });
    };

    return RummyPointTable;
}