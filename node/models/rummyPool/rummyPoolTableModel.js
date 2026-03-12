module.exports = (sequelize, DataTypes) => {
    const RummyPoolTable = sequelize.define("tbl_rummy_pool_table", {
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
        user_amount: {
            type: DataTypes.FLOAT
        },
        commission_amount: {
            type: DataTypes.FLOAT
        },
        winner_id: {
            type: DataTypes.INTEGER
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
        start_seat_no: {
            type: DataTypes.TINYINT,
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
        tableName: 'tbl_rummy_pool_table',
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

    RummyPoolTable.associate = (models) => {
        RummyPoolTable.belongsTo(models.RummyPoolTableMaster, {
            foreignKey: 'boot_value',
            targetKey: 'boot_value'
        });
        RummyPoolTable.hasMany(models.user, { foreignKey: 'rummy_pool_table_id', sourceKey: 'id' });
    };

    return RummyPoolTable;
}