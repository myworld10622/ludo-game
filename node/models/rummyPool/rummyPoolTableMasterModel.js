module.exports = (sequelize, DataTypes) => {
    const RummyPoolTableMaster = sequelize.define("tbl_rummy_pool_table_master", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        boot_value: {
            type: DataTypes.BIGINT,
        },
        pool_point: {
            type: DataTypes.INTEGER,
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
        winning_amount: {
            type: DataTypes.FLOAT,
        },
        name: {
            type: DataTypes.STRING
        },
        founder_id: {
            type: DataTypes.INTEGER
        },
        max_player: {
            type: DataTypes.TINYINT
        },
        invitation_code: {
            type: DataTypes.STRING
        },
        password: {
            type: DataTypes.STRING
        },
        viewer_status: {
            type: DataTypes.TINYINT
        },
        private: {
            type: DataTypes.TINYINT
        },
        no_of_players: {
            type: DataTypes.TINYINT
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
        tableName: 'tbl_rummy_pool_table_master',
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
    });

    RummyPoolTableMaster.associate = (models) => {
        RummyPoolTableMaster.hasMany(models.RummyPoolTable, { foreignKey: 'boot_value', sourceKey: 'boot_value' });
    };

    return RummyPoolTableMaster;
}