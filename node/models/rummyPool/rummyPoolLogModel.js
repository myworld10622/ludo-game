module.exports = (sequelize, DataTypes) => {
    const RummyPoolLog = sequelize.define("tbl_rummy_pool_log", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        game_id: {
            type: DataTypes.INTEGER,
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        action: {
            type: DataTypes.INTEGER,
        },
        json: {
            type: DataTypes.TEXT,
        },
        seen: {
            type: DataTypes.TINYINT,
        },
        amount: {
            type: DataTypes.BIGINT,
        },
        timeout: {
            type: DataTypes.TINYINT,
        },
        points: {
            type: DataTypes.INTEGER,
        },
        total_points: {
            type: DataTypes.INTEGER,
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_rummy_pool_log',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
        indexes: [
            {
                name: 'idx_game_id',
                fields: ['game_id']
            }
        ]
    });

    RummyPoolLog.associate = (models) => {
        RummyPoolLog.belongsTo(models.user, { foreignKey: 'user_id', as: 'tbl_users' });
        RummyPoolLog.belongsTo(models.RummyPool, { foreignKey: 'game_id' });
    };

    return RummyPoolLog;
}