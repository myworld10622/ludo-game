module.exports = (sequelize, DataTypes) => {
    const TeenpattiLog = sequelize.define("tbl_game_log", {
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
        plus: {
            type: DataTypes.TINYINT,
        },
        seen: {
            type: DataTypes.TINYINT,
        },
        amount: {
            type: DataTypes.BIGINT,
        },
        points: {
            type: DataTypes.INTEGER,
        },
        timeout: {
            type: DataTypes.TINYINT,
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_game_log',
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

    TeenpattiLog.associate = (models) => {
        TeenpattiLog.belongsTo(models.user, { foreignKey: 'user_id', as: 'tbl_users' });
        TeenpattiLog.belongsTo(models.Teenpatti, { foreignKey: 'game_id' });
    };

    return TeenpattiLog;
}